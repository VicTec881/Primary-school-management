<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\LearningArea;
use App\Models\AssessmentType;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\LearnerEnrollment;
use App\Models\AssessmentRecord;
use App\Services\AssessmentService;
use Illuminate\Support\Facades\Auth;
use Exception;

class AssessmentController extends Controller
{
    public function __construct(
        protected AssessmentService $assessmentService
    ) {}

    public function index(Request $request)
    {
        $years = AcademicYear::orderByDesc('name')->get();
        $selectedYearId = $request->input('academic_year_id', AcademicYear::current()?->id ?? $years->first()?->id);
        
        $terms = Term::where('academic_year_id', $selectedYearId)->get();
        $selectedTermId = $request->input('term_id', Term::current()?->id ?? $terms->first()?->id);

        $streams = Stream::with('grade')->get();
        $selectedStreamId = $request->input('stream_id', $streams->first()?->id);

        $learningAreas = LearningArea::where('is_active', true)->get();
        $selectedAreaId = $request->input('learning_area_id', $learningAreas->first()?->id);

        $assessmentTypes = AssessmentType::where('is_active', true)->get();
        $selectedTypeId = $request->input('assessment_type_id', $assessmentTypes->first()?->id);

        // Fetch enrolled learners in this stream
        $enrollments = LearnerEnrollment::with('learner')
            ->where('stream_id', $selectedStreamId)
            ->where('academic_year_id', $selectedYearId)
            ->where('status', 'enrolled')
            ->get();

        // Existing marks
        $existingRecords = AssessmentRecord::where('stream_id', $selectedStreamId)
            ->where('academic_year_id', $selectedYearId)
            ->where('term_id', $selectedTermId)
            ->where('learning_area_id', $selectedAreaId)
            ->where('assessment_type_id', $selectedTypeId)
            ->get()
            ->keyBy('learner_id');

        return view('assessments.index', compact(
            'years', 'terms', 'streams', 'learningAreas', 'assessmentTypes',
            'selectedYearId', 'selectedTermId', 'selectedStreamId', 'selectedAreaId', 'selectedTypeId',
            'enrollments', 'existingRecords'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stream_id' => ['required', 'exists:streams,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'learning_area_id' => ['required', 'exists:learning_areas,id'],
            'assessment_type_id' => ['required', 'exists:assessment_types,id'],
            'marks' => ['required', 'array'],
        ]);

        try {
            $count = $this->assessmentService->bulkRecordAssessments(
                (int) $validated['stream_id'],
                (int) $validated['learning_area_id'],
                (int) $validated['assessment_type_id'],
                (int) $validated['academic_year_id'],
                (int) $validated['term_id'],
                $validated['marks'],
                Auth::user()
            );

            return back()->with('success', "Saved {$count} assessment records successfully!");
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function supportReport(Request $request)
    {
        $streams = Stream::with('grade')->get();
        $selectedStreamId = $request->input('stream_id', $streams->first()?->id);

        $year = AcademicYear::current() ?? AcademicYear::first();
        $term = Term::current() ?? Term::where('academic_year_id', $year->id)->first();

        $supportList = $selectedStreamId ? $this->assessmentService->getLearnersRequiringSupport((int)$selectedStreamId, $year->id, $term->id) : [];

        return view('assessments.support-report', compact('streams', 'selectedStreamId', 'supportList', 'year', 'term'));
    }
}

