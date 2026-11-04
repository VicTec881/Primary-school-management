<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Learner;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\AuditLog;
use App\Services\AdmissionService;
use App\Services\FeeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class LearnerController extends Controller
{
    public function __construct(
        protected AdmissionService $admissionService,
        protected FeeService $feeService
    ) {}

    public function index(Request $request)
    {
        $query = Learner::with([
            'currentEnrollment.grade',
            'currentEnrollment.stream',
            'guardians',
            'feeAccount'
        ]);

        // Filters
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('middle_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('admission_number', 'LIKE', "%{$search}%")
                  ->orWhere('nemis_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('guardians', function ($gq) use ($search) {
                      $gq->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%")
                         ->orWhere('phone', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($gradeId = $request->input('grade_id')) {
            $query->whereHas('currentEnrollment', fn($q) => $q->where('grade_id', $gradeId));
        }

        if ($streamId = $request->input('stream_id')) {
            $query->whereHas('currentEnrollment', fn($q) => $q->where('stream_id', $streamId));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'active');
        }

        $learners = $query->orderBy('admission_number')->paginate(15)->withQueryString();
        $grades = Grade::with('streams')->orderBy('level')->get();

        return view('learners.index', compact('learners', 'grades'));
    }

    public function create()
    {
        $grades = Grade::with('streams')->orderBy('level')->get();
        $suggestedAdmissionNumber = $this->admissionService->generateAdmissionNumber();
        $currentYear = AcademicYear::current() ?? AcademicYear::first();
        $currentTerm = Term::current() ?? Term::first();

        return view('learners.create', compact('grades', 'suggestedAdmissionNumber', 'currentYear', 'currentTerm'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'in:male,female,other'],
            'dob' => ['required', 'date', 'before:today'],
            'admission_date' => ['required', 'date'],
            'stream_id' => ['required', 'exists:streams,id'],
            'nemis_number' => ['nullable', 'string', 'max:50', 'unique:learners,nemis_number'],
            'assessment_number' => ['nullable', 'string', 'max:50', 'unique:learners,assessment_number'],
            'previous_school' => ['nullable', 'string', 'max:100'],
            'medical_notes' => ['nullable', 'string'],
            'special_needs' => ['nullable', 'string'],
            'guardian_name' => ['required', 'string', 'max:100'],
            'guardian_phone' => ['required', 'string', 'max:20'],
            'guardian_id_number' => ['nullable', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email'],
            'guardian_relationship' => ['required', 'string'],
            'guardian_address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'optional_fee_category_ids' => ['nullable', 'array'],
        ]);

        try {
            $learner = $this->admissionService->registerLearner($validated, Auth::id());
            return redirect()->route('learners.show', $learner->id)
                ->with('success', "Learner {$learner->full_name} ({$learner->admission_number}) registered successfully!");
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(int $id)
    {
        $learner = Learner::with([
            'guardians',
            'enrollments.grade',
            'enrollments.stream',
            'enrollments.academicYear',
            'feeAccount',
            'invoices.items',
            'invoices.term',
            'payments',
            'assessments.learningArea',
            'assessments.assessmentType',
            'attendanceRecords.session',
            'documents',
        ])->findOrFail($id);

        return view('learners.show', compact('learner'));
    }

    public function edit(int $id)
    {
        $learner = Learner::with(['currentEnrollment', 'guardians'])->findOrFail($id);
        $grades = Grade::with('streams')->orderBy('level')->get();

        return view('learners.edit', compact('learner', 'grades'));
    }

    public function update(Request $request, int $id)
    {
        $learner = Learner::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'in:male,female,other'],
            'dob' => ['required', 'date'],
            'nemis_number' => ['nullable', 'string', 'max:50', 'unique:learners,nemis_number,' . $learner->id],
            'previous_school' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,transferred,graduated,withdrawn,suspended'],
            'medical_notes' => ['nullable', 'string'],
            'special_needs' => ['nullable', 'string'],
        ]);

        $oldData = $learner->toArray();
        $learner->update($validated);

        AuditLog::record(
            action: 'learner_updated',
            model: $learner,
            old: $oldData,
            new: $learner->toArray(),
            userId: Auth::id()
        );

        return redirect()->route('learners.show', $learner->id)
            ->with('success', 'Learner profile updated successfully.');
    }

    public function destroy(int $id)
    {
        $learner = Learner::findOrFail($id);

        if ($learner->hasFinancialOrAcademicHistory()) {
            return back()->withErrors([
                'error' => 'Data Protection & Security Rule: Cannot delete a learner with historical financial invoices, payments, attendance or academic records. Change their status to Transferred or Archived instead.'
            ]);
        }

        $learner->delete();
        AuditLog::record(action: 'learner_deleted', model: $learner, userId: Auth::id());

        return redirect()->route('learners.index')->with('success', 'Learner record removed successfully.');
    }

    /**
     * AJAX endpoint to calculate fee schedule for a selected grade and optional items.
     */
    public function calculateFeesApi(Request $request)
    {
        $gradeId = (int) $request->input('grade_id');
        $optionalCategoryIds = $request->input('optional_fee_category_ids', []);

        $result = $this->feeService->calculateItemizedFee($gradeId, $optionalCategoryIds);

        return response()->json($result);
    }

    public function importForm()
    {
        return view('learners.import');
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        $importResult = $this->admissionService->importLearners($rows, $file->getClientOriginalName(), Auth::id());

        return redirect()->route('learners.index')
            ->with('success', "Import completed: {$importResult['successful']} learners imported successfully. {$importResult['failed']} failed.");
    }

    public function exportCsv()
    {
        $learners = Learner::with(['currentEnrollment.grade', 'currentEnrollment.stream', 'feeAccount'])
            ->where('status', 'active')
            ->orderBy('admission_number')
            ->get();

        $response = new StreamedResponse(function () use ($learners) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Admission Number', 'Full Name', 'Gender', 'DOB', 'Grade', 'Stream', 'Status', 'Fee Balance (KSh)']);

            foreach ($learners as $l) {
                fputcsv($handle, [
                    $l->admission_number,
                    $l->full_name,
                    ucfirst($l->gender),
                    $l->dob->toDateString(),
                    $l->currentEnrollment?->grade?->name ?? 'Unassigned',
                    $l->currentEnrollment?->stream?->name ?? 'Unassigned',
                    ucfirst($l->status),
                    number_format($l->feeAccount?->current_balance ?? 0, 2),
                ]);
            }
            fclose($handle);
        });

        $filename = 'kenya_primary_sms_learners_' . date('Y_m_d') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}

