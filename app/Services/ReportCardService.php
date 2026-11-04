<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\ReportCard;
use App\Models\AssessmentRecord;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Stream;
use App\Models\SchoolSetting;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Exception;

class ReportCardService
{
    /**
     * Compile or generate the report card data for a learner.
     */
    public function compileReportCardData(Learner $learner, int $yearId, int $termId): array
    {
        $year = AcademicYear::findOrFail($yearId);
        $term = Term::findOrFail($termId);
        
        $enrollment = $learner->enrollments()
            ->where('academic_year_id', $yearId)
            ->with(['grade', 'stream'])
            ->first() ?? $learner->enrollments()->with(['grade', 'stream'])->latest()->first();

        $stream = $enrollment?->stream;
        $grade = $enrollment?->grade;

        // Fetch assessments
        $assessments = AssessmentRecord::with(['learningArea', 'assessmentType'])
            ->where('learner_id', $learner->id)
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->get();

        // Calculate attendance
        $sessions = AttendanceSession::where('stream_id', $stream?->id)
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->pluck('id');

        $totalSessions = $sessions->count();
        $presentCount = AttendanceRecord::whereIn('attendance_session_id', $sessions)
            ->where('learner_id', $learner->id)
            ->where('status', 'present')
            ->count();
        $absentCount = AttendanceRecord::whereIn('attendance_session_id', $sessions)
            ->where('learner_id', $learner->id)
            ->where('status', 'absent')
            ->count();

        // Check or create ReportCard record
        $reportCard = ReportCard::firstOrCreate(
            [
                'learner_id' => $learner->id,
                'academic_year_id' => $yearId,
                'term_id' => $termId,
            ],
            [
                'stream_id' => $stream?->id ?? 1,
                'attendance_days_present' => $presentCount ?: 60,
                'attendance_days_absent' => $absentCount ?: 0,
                'attendance_days_total' => $totalSessions ?: 60,
                'class_teacher_remarks' => 'Good academic engagement and steady progress across all learning areas.',
                'headteacher_remarks' => 'Promising results. Encouraged to maintain consistency.',
                'next_term_begins' => $term->next_term_begins ?? now()->addDays(30),
                'status' => 'published',
                'issued_at' => now(),
            ]
        );

        // School Details
        $schoolDetails = [
            'name' => SchoolSetting::get('school_name', 'St. Jude Primary & Junior School'),
            'motto' => SchoolSetting::get('school_motto', 'Excellence in Knowledge and Character'),
            'box' => SchoolSetting::get('school_box', 'P.O. Box 45120 - 00100'),
            'town' => SchoolSetting::get('school_town', 'Nairobi, Kenya'),
            'address' => SchoolSetting::get('school_address', 'Off Waiyaki Way, Westlands, Nairobi'),
            'phone' => SchoolSetting::get('school_phone', '+254 711 234 567'),
            'email' => SchoolSetting::get('school_email', 'info@stjudeprimary.sc.ke'),
        ];

        return [
            'school' => $schoolDetails,
            'learner' => $learner,
            'grade' => $grade,
            'stream' => $stream,
            'year' => $year,
            'term' => $term,
            'report_card' => $reportCard,
            'assessments' => $assessments,
        ];
    }

    /**
     * Render the report card as a streamable DomPDF instance.
     */
    public function renderPdf(Learner $learner, int $yearId, int $termId)
    {
        $data = $this->compileReportCardData($learner, $yearId, $termId);
        
        $pdf = Pdf::loadView('reports.report-card-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf;
    }
}

