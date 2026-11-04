<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\AssessmentRecord;
use App\Models\AssessmentType;
use App\Models\LearningArea;
use App\Models\GradingScale;
use App\Models\Stream;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;

class AssessmentService
{
    /**
     * Map a raw score and max score to a CBC performance level (EE, ME, AE, BE).
     */
    public function determinePerformanceLevel(float $rawScore, float $maxScore = 100.00): string
    {
        $percentage = ($maxScore > 0) ? ($rawScore / $maxScore) * 100 : 0;
        
        $scale = GradingScale::where('is_default', true)->first();
        if ($scale) {
            $detail = $scale->getLevelForScore($percentage);
            if ($detail) {
                return $detail->code;
            }
        }

        // Fallback CBC levels
        return match (true) {
            $percentage >= 80.0 => 'EE',
            $percentage >= 65.0 => 'ME',
            $percentage >= 50.0 => 'AE',
            default => 'BE',
        };
    }

    /**
     * Verify if a teacher is authorized to assess a stream and learning area.
     */
    public function canTeacherAssess(User $teacher, int $streamId, int $learningAreaId, int $academicYearId): bool
    {
        if ($teacher->isSuperAdmin() || $teacher->isHeadteacher()) {
            return true;
        }

        return TeacherAssignment::where('academic_year_id', $academicYearId)
            ->where('stream_id', $streamId)
            ->where('learning_area_id', $learningAreaId)
            ->where('teacher_id', $teacher->id)
            ->exists();
    }

    /**
     * Record or update an assessment record for a learner.
     */
    public function recordAssessment(array $data, ?User $assessor = null): AssessmentRecord
    {
        return DB::transaction(function () use ($data, $assessor) {
            $learner = Learner::findOrFail($data['learner_id']);
            $streamId = (int) $data['stream_id'];
            $learningAreaId = (int) $data['learning_area_id'];
            $academicYearId = (int) $data['academic_year_id'];
            $termId = (int) $data['term_id'];
            $assessmentTypeId = (int) $data['assessment_type_id'];

            if ($assessor && !$this->canTeacherAssess($assessor, $streamId, $learningAreaId, $academicYearId)) {
                throw new Exception("Unauthorized: You are not assigned to teach this learning area in this class.");
            }

            $rawScore = isset($data['raw_score']) ? (float) $data['raw_score'] : null;
            $maxScore = isset($data['max_score']) ? (float) $data['max_score'] : 100.00;

            if ($rawScore !== null && ($rawScore < 0 || $rawScore > $maxScore)) {
                throw new Exception("Score must be between 0 and {$maxScore}.");
            }

            $performanceLevel = !empty($data['performance_level'])
                ? strtoupper(trim($data['performance_level']))
                : ($rawScore !== null ? $this->determinePerformanceLevel($rawScore, $maxScore) : null);

            $existing = AssessmentRecord::where('learner_id', $learner->id)
                ->where('academic_year_id', $academicYearId)
                ->where('term_id', $termId)
                ->where('learning_area_id', $learningAreaId)
                ->where('assessment_type_id', $assessmentTypeId)
                ->first();

            $oldData = $existing ? $existing->toArray() : null;

            $record = AssessmentRecord::updateOrCreate(
                [
                    'learner_id' => $learner->id,
                    'academic_year_id' => $academicYearId,
                    'term_id' => $termId,
                    'learning_area_id' => $learningAreaId,
                    'assessment_type_id' => $assessmentTypeId,
                ],
                [
                    'stream_id' => $streamId,
                    'raw_score' => $rawScore,
                    'max_score' => $maxScore,
                    'performance_level' => $performanceLevel,
                    'rubric_comments' => $data['rubric_comments'] ?? null,
                    'teacher_id' => $assessor ? $assessor->id : ($data['teacher_id'] ?? null),
                ]
            );

            AuditLog::record(
                action: $existing ? 'assessment_updated' : 'assessment_created',
                model: $record,
                old: $oldData,
                new: $record->toArray(),
                userId: $assessor?->id
            );

            return $record;
        });
    }

    /**
     * Bulk save assessments for a whole class stream.
     */
    public function bulkRecordAssessments(int $streamId, int $learningAreaId, int $assessmentTypeId, int $yearId, int $termId, array $learnerMarks, ?User $assessor = null): int
    {
        return DB::transaction(function () use ($streamId, $learningAreaId, $assessmentTypeId, $yearId, $termId, $learnerMarks, $assessor) {
            if ($assessor && !$this->canTeacherAssess($assessor, $streamId, $learningAreaId, $yearId)) {
                throw new Exception("Unauthorized: You are not assigned to assess this learning area in this stream.");
            }

            $count = 0;
            foreach ($learnerMarks as $markData) {
                if (!isset($markData['learner_id']) || $markData['raw_score'] === null || $markData['raw_score'] === '') {
                    continue;
                }

                $this->recordAssessment([
                    'learner_id' => $markData['learner_id'],
                    'stream_id' => $streamId,
                    'academic_year_id' => $yearId,
                    'term_id' => $termId,
                    'learning_area_id' => $learningAreaId,
                    'assessment_type_id' => $assessmentTypeId,
                    'raw_score' => (float) $markData['raw_score'],
                    'max_score' => (float) ($markData['max_score'] ?? 100.00),
                    'rubric_comments' => $markData['rubric_comments'] ?? null,
                ], $assessor);

                $count++;
            }

            return $count;
        });
    }

    /**
     * Find learners in a class/grade who require additional learning support (BE or AE).
     */
    public function getLearnersRequiringSupport(int $streamId, int $yearId, int $termId): array
    {
        $records = AssessmentRecord::with(['learner', 'learningArea'])
            ->where('stream_id', $streamId)
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->whereIn('performance_level', ['BE', 'AE'])
            ->get();

        $grouped = [];
        foreach ($records as $r) {
            $learnerId = $r->learner_id;
            if (!isset($grouped[$learnerId])) {
                $grouped[$learnerId] = [
                    'learner' => $r->learner,
                    'areas_needing_support' => [],
                ];
            }
            $grouped[$learnerId]['areas_needing_support'][] = [
                'learning_area' => $r->learningArea->name,
                'score' => $r->raw_score,
                'level' => $r->performance_level,
                'comments' => $r->rubric_comments,
            ];
        }

        return array_values($grouped);
    }
}

