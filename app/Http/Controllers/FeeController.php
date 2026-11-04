<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Invoice;
use App\Models\Learner;
use App\Services\FeeService;
use Illuminate\Support\Facades\Auth;
use Exception;

class FeeController extends Controller
{
    public function __construct(
        protected FeeService $feeService
    ) {}

    public function structuresIndex()
    {
        $structures = FeeStructure::with(['grade', 'academicYear', 'term', 'items.feeCategory'])
            ->orderByDesc('id')
            ->get();

        $grades = Grade::orderBy('level')->get();
        $years = AcademicYear::orderByDesc('name')->get();
        $terms = Term::orderBy('term_number')->get();
        $categories = FeeCategory::orderBy('name')->get();

        return view('fees.structures', compact('structures', 'grades', 'years', 'terms', 'categories'));
    }

    public function storeStructure(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'title' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.fee_category_id' => ['required', 'exists:fee_categories,id'],
            'items.*.name' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.is_optional' => ['nullable', 'boolean'],
        ]);

        $structure = FeeStructure::create([
            'academic_year_id' => $validated['academic_year_id'],
            'term_id' => $validated['term_id'],
            'grade_id' => $validated['grade_id'],
            'title' => $validated['title'],
            'is_active' => true,
        ]);

        foreach ($validated['items'] as $item) {
            FeeStructureItem::create([
                'fee_structure_id' => $structure->id,
                'fee_category_id' => $item['fee_category_id'],
                'name' => $item['name'],
                'amount' => $item['amount'],
                'is_optional' => $item['is_optional'] ?? false,
            ]);
        }

        return back()->with('success', 'Fee structure created successfully.');
    }

    public function invoicesIndex(Request $request)
    {
        $query = Invoice::with(['learner.currentEnrollment.grade', 'academicYear', 'term']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($termId = $request->input('term_id')) {
            $query->where('term_id', $termId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('learner', function ($lq) use ($search) {
                      $lq->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%")
                         ->orWhere('admission_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderByDesc('id')->paginate(15)->withQueryString();
        $terms = Term::all();

        return view('fees.invoices', compact('invoices', 'terms'));
    }

    public function invoiceShow(int $id)
    {
        $invoice = Invoice::with([
            'learner.guardians',
            'learner.feeAccount',
            'items.feeCategory',
            'allocations.payment',
            'discounts.authorizer',
            'academicYear',
            'term'
        ])->findOrFail($id);

        return view('fees.invoice-show', compact('invoice'));
    }

    public function applyDiscount(Request $request, int $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->feeService->applyDiscount($invoice, (float) $validated['amount'], $validated['reason'], Auth::id());
            return back()->with('success', 'Fee discount/waiver applied successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function statement(int $learnerId)
    {
        $learner = Learner::with([
            'feeAccount',
            'invoices.items',
            'invoices.term',
            'payments.allocations',
            'guardians'
        ])->findOrFail($learnerId);

        return view('fees.statement', compact('learner'));
    }
}

