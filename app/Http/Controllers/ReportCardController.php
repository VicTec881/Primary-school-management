<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReportCard;
use App\Models\Learner;
use App\Models\Stream;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\LearnerEnrollment;
use App\Services\ReportCardService;
use Illuminate\Support\Facades\Auth;

class ReportCardController extends Controller
{
    public function __construct(
        protected ReportCardService $reportCardService
    ) {}

    public function index(Request $request)
    {
        $years = AcademicYear::orderByDesc('name')->get();
        $selectedYearId = $request->input('academic_year_id', AcademicYear::current()?->id ?? $years->first()?->id);

        $terms = Term::where('academic_year_id', $selectedYearId)->get();
        $selectedTermId = $request->input('term_id', Term::current()?->id ?? $terms->first()?->id);

        $streams = Stream::with('grade')->get();
        $selectedStreamId = $request->input('stream_id');

        $query = ReportCard::with(['learner.currentEnrollment.grade', 'stream.grade', 'academicYear', 'term'])
            ->where('academic_year_id', $selectedYearId)
            ->where('term_id', $selectedTermId);

        if ($selectedStreamId) {
            $query->where('stream_id', $selectedStreamId);
        }

        $reportCards = $query->paginate(15)->withQueryString();

        return view('reports.index', compact('reportCards', 'years', 'terms', 'streams', 'selectedYearId', 'selectedTermId', 'selectedStreamId'));
    }

    public function show(int $id)
    {
        $reportCard = ReportCard::with(['learner', 'academicYear', 'term', 'stream.grade'])->findOrFail($id);
        $user = Auth::user();

        // Authorization check for parent
        if ($user->hasRole('parent')) {
            $isLinked = $user->guardian && $reportCard->learner->guardians()->where('guardian_id', $user->guardian->id)->exists();
            if (!$isLinked) {
                abort(403, 'Unauthorized: You can only view report cards for your linked children.');
            }
        }

        $data = $this->reportCardService->compileReportCardData($reportCard->learner, $reportCard->academic_year_id, $reportCard->term_id);

        return view('reports.show', compact('data', 'reportCard'));
    }

    public function downloadPdf(int $learnerId, Request $request)
    {
        $learner = Learner::findOrFail($learnerId);
        $user = Auth::user();

        // Authorization check for parent
        if ($user->hasRole('parent')) {
            $isLinked = $user->guardian && $learner->guardians()->where('guardian_id', $user->guardian->id)->exists();
            if (!$isLinked) {
                abort(403, 'Unauthorized: You can only view report cards for your linked children.');
            }
        }

        $yearId = $request->input('year_id', AcademicYear::current()?->id ?? AcademicYear::first()->id);
        $termId = $request->input('term_id', Term::current()?->id ?? Term::first()->id);

        $pdf = $this->reportCardService->renderPdf($learner, (int)$yearId, (int)$termId);

        $filename = sprintf('ReportCard_%s_T%d_%d.pdf', str_replace('/', '_', $learner->admission_number), $termId, $yearId);

        return $pdf->stream($filename);
    }

    public function bulkGenerate(Request $request)
    {
        $validated = $request->validate([
            'stream_id' => ['required', 'exists:streams,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $enrollments = LearnerEnrollment::where('stream_id', $validated['stream_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('status', 'enrolled')
            ->get();

        foreach ($enrollments as $enr) {
            $this->reportCardService->compileReportCardData($enr->learner, (int)$validated['academic_year_id'], (int)$validated['term_id']);
        }

        return back()->with('success', "Compiled report cards for {$enrollments->count()} learners in this stream.");
    }
}

