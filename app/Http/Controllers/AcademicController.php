<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\LearningArea;
use App\Models\TeacherAssignment;
use App\Models\ClassTeacher;
use App\Models\User;
use App\Models\LearnerEnrollment;
use App\Services\PromotionService;
use Illuminate\Support\Facades\Auth;

class AcademicController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {}

    public function classesIndex()
    {
        $grades = Grade::with(['streams.enrollments' => function ($q) {
            $currentYear = AcademicYear::current();
            if ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            }
        }, 'streams.classTeachers.teacher'])->orderBy('level')->get();

        $currentYear = AcademicYear::current();

        return view('academic.classes', compact('grades', 'currentYear'));
    }

    public function classShow(int $streamId)
    {
        $stream = Stream::with('grade')->findOrFail($streamId);
        $currentYear = AcademicYear::current() ?? AcademicYear::first();

        $enrollments = LearnerEnrollment::with(['learner.guardians', 'learner.feeAccount'])
            ->where('stream_id', $stream->id)
            ->where('academic_year_id', $currentYear->id)
            ->where('status', 'enrolled')
            ->orderBy('roll_number')
            ->get();

        $classTeacher = ClassTeacher::with('teacher')
            ->where('academic_year_id', $currentYear->id)
            ->where('stream_id', $stream->id)
            ->first();

        $teachers = User::where('role_name', 'teacher')->get();

        return view('academic.class-show', compact('stream', 'enrollments', 'classTeacher', 'currentYear', 'teachers'));
    }

    public function assignClassTeacher(Request $request, int $streamId)
    {
        $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
        ]);

        $currentYear = AcademicYear::current() ?? AcademicYear::first();

        ClassTeacher::updateOrCreate(
            ['academic_year_id' => $currentYear->id, 'stream_id' => $streamId],
            ['teacher_id' => $request->teacher_id]
        );

        return back()->with('success', 'Class teacher assigned successfully.');
    }

    public function yearsIndex()
    {
        $years = AcademicYear::with('terms')->orderByDesc('name')->get();
        return view('academic.years', compact('years'));
    }

    public function setCurrentYear(int $id)
    {
        AcademicYear::query()->update(['is_current' => false]);
        $year = AcademicYear::findOrFail($id);
        $year->update(['is_current' => true]);

        return back()->with('success', "Academic Year {$year->name} set as current.");
    }

    public function setCurrentTerm(int $id)
    {
        $term = Term::findOrFail($id);
        Term::where('academic_year_id', $term->academic_year_id)->update(['is_current' => false]);
        $term->update(['is_current' => true]);

        return back()->with('success', "Term {$term->name} set as current.");
    }

    public function learningAreasIndex()
    {
        $learningAreas = LearningArea::orderBy('name')->get();
        $teachers = User::where('role_name', 'teacher')->get();
        $streams = Stream::with('grade')->get();
        $currentYear = AcademicYear::current() ?? AcademicYear::first();

        $assignments = TeacherAssignment::with(['stream.grade', 'learningArea', 'teacher'])
            ->where('academic_year_id', $currentYear->id)
            ->get();

        return view('academic.learning-areas', compact('learningAreas', 'teachers', 'streams', 'assignments', 'currentYear'));
    }

    public function assignSubjectTeacher(Request $request)
    {
        $validated = $request->validate([
            'stream_id' => ['required', 'exists:streams,id'],
            'learning_area_id' => ['required', 'exists:learning_areas,id'],
            'teacher_id' => ['required', 'exists:users,id'],
        ]);

        $currentYear = AcademicYear::current() ?? AcademicYear::first();

        TeacherAssignment::updateOrCreate(
            [
                'academic_year_id' => $currentYear->id,
                'stream_id' => $validated['stream_id'],
                'learning_area_id' => $validated['learning_area_id'],
            ],
            [
                'teacher_id' => $validated['teacher_id'],
            ]
        );

        return back()->with('success', 'Teacher assigned to learning area successfully.');
    }

    public function promotionIndex()
    {
        $years = AcademicYear::orderByDesc('name')->get();
        $streams = Stream::with('grade')->get();

        return view('academic.promotion', compact('years', 'streams'));
    }

    public function promotionPreview(Request $request)
    {
        $validated = $request->validate([
            'from_year_id' => ['required', 'exists:academic_years,id'],
            'from_stream_id' => ['required', 'exists:streams,id'],
            'to_year_id' => ['required', 'exists:academic_years,id'],
            'to_stream_id' => ['required', 'exists:streams,id'],
        ]);

        $preview = $this->promotionService->getPromotionPreview(
            $validated['from_year_id'],
            $validated['from_stream_id'],
            $validated['to_year_id'],
            $validated['to_stream_id']
        );

        return response()->json($preview);
    }

    public function executePromotion(Request $request)
    {
        $validated = $request->validate([
            'from_year_id' => ['required', 'exists:academic_years,id'],
            'to_year_id' => ['required', 'exists:academic_years,id'],
            'to_stream_id' => ['required', 'exists:streams,id'],
            'learner_ids' => ['required', 'array', 'min:1'],
        ]);

        $result = $this->promotionService->executePromotion(
            $validated['from_year_id'],
            $validated['to_year_id'],
            $validated['to_stream_id'],
            $validated['learner_ids'],
            Auth::id()
        );

        return back()->with('success', "Successfully promoted {$result['promoted_count']} learners to {$result['destination_stream']}!");
    }
}

