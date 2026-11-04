<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\LearnerEnrollment;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;

class PromotionService
{
    /**
     * Preview learners in a stream eligible for promotion to the next academic year.
     */
    public function getPromotionPreview(int $fromYearId, int $fromStreamId, int $toYearId, int $toStreamId): array
    {
        $fromStream = Stream::with('grade')->findOrFail($fromStreamId);
        $toStream = Stream::with('grade')->findOrFail($toStreamId);
        $toYear = AcademicYear::findOrFail($toYearId);

        $enrollments = LearnerEnrollment::with('learner')
            ->where('academic_year_id', $fromYearId)
            ->where('stream_id', $fromStreamId)
            ->where('status', 'enrolled')
            ->get();

        $preview = [];
        foreach ($enrollments as $enr) {
            $alreadyPromoted = LearnerEnrollment::where('learner_id', $enr->learner_id)
                ->where('academic_year_id', $toYearId)
                ->exists();

            $preview[] = [
                'learner_id' => $enr->learner_id,
                'admission_number' => $enr->learner->admission_number,
                'learner_name' => $enr->learner->full_name,
                'current_grade' => $fromStream->grade->name,
                'current_stream' => $fromStream->name,
                'destination_grade' => $toStream->grade->name,
                'destination_stream' => $toStream->name,
                'eligible' => !$alreadyPromoted,
                'reason' => $alreadyPromoted ? 'Already enrolled in destination year' : 'Eligible',
            ];
        }

        return [
            'from_stream' => $fromStream->full_name,
            'to_stream' => $toStream->full_name,
            'to_year' => $toYear->name,
            'learners' => $preview,
        ];
    }

    /**
     * Execute promotion transactionally for selected learners.
     * Preserves all historical invoices, payments, assessments, and attendance records.
     */
    public function executePromotion(int $fromYearId, int $toYearId, int $toStreamId, array $learnerIds, ?int $authorizedById = null): array
    {
        return DB::transaction(function () use ($fromYearId, $toYearId, $toStreamId, $learnerIds, $authorizedById) {
            $toStream = Stream::with('grade')->findOrFail($toStreamId);
            $promotedCount = 0;
            $skippedCount = 0;

            foreach ($learnerIds as $learnerId) {
                $learner = Learner::findOrFail($learnerId);

                // Check duplicate promotion in the target year
                $alreadyEnrolled = LearnerEnrollment::where('learner_id', $learner->id)
                    ->where('academic_year_id', $toYearId)
                    ->exists();

                if ($alreadyEnrolled) {
                    $skippedCount++;
                    continue;
                }

                // Update old enrollment status to 'promoted'
                LearnerEnrollment::where('learner_id', $learner->id)
                    ->where('academic_year_id', $fromYearId)
                    ->update(['status' => 'promoted']);

                // Create new enrollment in destination grade and stream
                LearnerEnrollment::create([
                    'learner_id' => $learner->id,
                    'academic_year_id' => $toYearId,
                    'grade_id' => $toStream->grade_id,
                    'stream_id' => $toStream->id,
                    'status' => 'enrolled',
                    'date_enrolled' => now()->toDateString(),
                ]);

                AuditLog::record(
                    action: 'learner_promoted',
                    model: $learner,
                    new: ['to_year' => $toYearId, 'to_stream' => $toStream->full_name],
                    userId: $authorizedById
                );

                $promotedCount++;
            }

            return [
                'promoted_count' => $promotedCount,
                'skipped_count' => $skippedCount,
                'destination_stream' => $toStream->full_name,
            ];
        });
    }
}

