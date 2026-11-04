<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stream;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\LearnerEnrollment;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request)
    {
        $streams = Stream::with('grade')->get();
        $selectedStreamId = $request->input('stream_id', $streams->first()?->id);
        $date = $request->input('date', Carbon::today()->toDateString());

        $currentYear = AcademicYear::current() ?? AcademicYear::first();
        $currentTerm = Term::current() ?? Term::where('academic_year_id', $currentYear->id)->first();

        // Enrolled learners in this stream
        $enrollments = LearnerEnrollment::with('learner')
            ->where('stream_id', $selectedStreamId)
            ->where('academic_year_id', $currentYear->id)
            ->where('status', 'enrolled')
            ->get();

        // Check if attendance already marked today for this stream
        $session = AttendanceSession::where('stream_id', $selectedStreamId)
            ->whereDate('date', $date)
            ->first();

        $records = $session ? $session->records->keyBy('learner_id') : collect();

        // Summary stats
        $alerts = $this->attendanceService->getFrequentAbsenceAlerts((int)$selectedStreamId, $currentYear->id, $currentTerm->id);

        return view('attendance.index', compact('streams', 'selectedStreamId', 'date', 'enrollments', 'session', 'records', 'alerts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stream_id' => ['required', 'exists:streams,id'],
            'date' => ['required', 'date'],
            'records' => ['required', 'array'],
        ]);

        $this->attendanceService->markAttendance(
            (int) $validated['stream_id'],
            $validated['date'],
            $validated['records'],
            Auth::user()
        );

        return back()->with('success', 'Attendance register recorded successfully.');
    }
}

