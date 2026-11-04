<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\LearnerFeeAccount;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\SchoolEvent;
use App\Models\ReportCard;
use App\Models\AcademicYear;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardMetrics(?int $yearId = null, ?int $termId = null): array
    {
        $yearId = $yearId ?? (AcademicYear::current()->id ?? AcademicYear::first()?->id);
        $termId = $termId ?? (Term::current()->id ?? Term::where('academic_year_id', $yearId)->first()?->id);

        // Learners count
        $totalLearners = Learner::where('status', 'active')->count();

        // Financials (calculated strictly from ledger / accounts)
        $totalBilled = (float) LearnerFeeAccount::sum('total_billed');
        $totalCollected = (float) Payment::where('status', 'completed')->sum('amount');
        $totalOutstanding = (float) LearnerFeeAccount::sum('current_balance');
        $totalDiscounts = (float) LearnerFeeAccount::sum('total_discounted');

        // Recent admissions
        $recentAdmissions = Learner::with('currentEnrollment.grade', 'currentEnrollment.stream')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        // Recent payments
        $recentPayments = Payment::with('learner')
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        // Grade distribution
        $gradeDistribution = Grade::withCount(['enrollments' => function ($q) use ($yearId) {
            if ($yearId) {
                $q->where('academic_year_id', $yearId);
            }
        }])->get()->map(fn($g) => [
            'name' => $g->name,
            'count' => $g->enrollments_count,
        ]);

        // Today's attendance
        $today = Carbon::today()->toDateString();
        $todaySessions = AttendanceSession::whereDate('date', $today)->pluck('id');
        $todayAttendanceRate = 100.0;
        if ($todaySessions->isNotEmpty()) {
            $totalPresent = AttendanceRecord::whereIn('attendance_session_id', $todaySessions)->where('status', 'present')->count();
            $totalRecorded = AttendanceRecord::whereIn('attendance_session_id', $todaySessions)->count();
            if ($totalRecorded > 0) {
                $todayAttendanceRate = round(($totalPresent / $totalRecorded) * 100, 1);
            }
        }

        // Upcoming events
        $upcomingEvents = SchoolEvent::where('start_date', '>=', now())
            ->orderBy('start_date')
            ->take(4)
            ->get();

        // Report card generation progress
        $reportCardsCount = ReportCard::when($yearId, fn($q) => $q->where('academic_year_id', $yearId))
            ->when($termId, fn($q) => $q->where('term_id', $termId))
            ->count();

        return [
            'total_learners' => $totalLearners,
            'total_billed' => $totalBilled,
            'total_collected' => $totalCollected,
            'total_outstanding' => $totalOutstanding,
            'total_discounts' => $totalDiscounts,
            'collection_rate' => $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0,
            'today_attendance_rate' => $todayAttendanceRate,
            'recent_admissions' => $recentAdmissions,
            'recent_payments' => $recentPayments,
            'grade_distribution' => $gradeDistribution,
            'upcoming_events' => $upcomingEvents,
            'report_cards_count' => $reportCardsCount,
        ];
    }
}

