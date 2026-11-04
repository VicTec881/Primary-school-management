<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Stream;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;

class AttendanceService
{
    /**
     * Mark or update attendance register for a stream.
     */
    public function markAttendance(int $streamId, string $date, array $records, ?User $teacher = null, string $sessionType = 'daily'): AttendanceSession
    {
        return DB::transaction(function () use ($streamId, $date, $records, $teacher, $sessionType) {
            $year = AcademicYear::current() ?? AcademicYear::firstOrFail();
            $term = Term::current() ?? Term::where('academic_year_id', $year->id)->firstOrFail();

            $session = AttendanceSession::firstOrCreate(
                [
                    'stream_id' => $streamId,
                    'date' => $date,
                    'session_type' => $sessionType,
                ],
                [
                    'academic_year_id' => $year->id,
                    'term_id' => $term->id,
                    'recorded_by' => $teacher?->id,
                ]
            );

            foreach ($records as $item) {
                if (!isset($item['learner_id'])) {
                    continue;
                }

                $status = in_array($item['status'], ['present', 'absent', 'late', 'excused']) ? $item['status'] : 'present';

                AttendanceRecord::updateOrCreate(
                    [
                        'attendance_session_id' => $session->id,
                        'learner_id' => $item['learner_id'],
                    ],
                    [
                        'status' => $status,
                        'remarks' => $item['remarks'] ?? null,
                    ]
                );
            }

            AuditLog::record(
                action: 'attendance_marked',
                model: $session,
                new: ['stream_id' => $streamId, 'date' => $date, 'count' => count($records)],
                userId: $teacher?->id
            );

            return $session;
        });
    }

    /**
     * Identify frequent absence alerts (> 3 absences in the current term).
     */
    public function getFrequentAbsenceAlerts(int $streamId, ?int $yearId = null, ?int $termId = null, int $threshold = 3): array
    {
        $yearId = $yearId ?? (AcademicYear::current()->id ?? AcademicYear::first()->id);
        $termId = $termId ?? (Term::current()->id ?? Term::where('academic_year_id', $yearId)->first()->id);

        $sessions = AttendanceSession::where('stream_id', $streamId)
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->pluck('id');

        $alerts = AttendanceRecord::with(['learner.guardians'])
            ->whereIn('attendance_session_id', $sessions)
            ->where('status', 'absent')
            ->select('learner_id', DB::raw('count(*) as absence_count'))
            ->groupBy('learner_id')
            ->having('absence_count', '>=', $threshold)
            ->get();

        return $alerts->map(function ($row) {
            return [
                'learner' => $row->learner,
                'absence_count' => $row->absence_count,
                'primary_guardian' => $row->learner->primaryGuardian(),
            ];
        })->toArray();
    }
}

