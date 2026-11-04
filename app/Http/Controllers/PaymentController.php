<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Learner;
use App\Models\Grade;
use App\Models\Stream;
use App\Services\PaymentService;
use App\Services\MpesaService;
use Illuminate\Support\Facades\Auth;
use Exception;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected MpesaService $mpesaService
    ) {}

    public function index(Request $request)
    {
        $query = Payment::with(['learner.currentEnrollment.grade', 'recordedByUser']);

        if ($method = $request->input('payment_method')) {
            $query->where('payment_method', $method);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'LIKE', "%{$search}%")
                  ->orWhere('transaction_reference', 'LIKE', "%{$search}%")
                  ->orWhereHas('learner', function ($lq) use ($search) {
                      $lq->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%")
                         ->orWhere('admission_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        $payments = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $learner = null;
        if ($learnerId = $request->input('learner_id')) {
            $learner = Learner::with(['feeAccount', 'invoices' => fn($q) => $q->whereIn('status', ['pending', 'partial'])])->find($learnerId);
        }

        $suggestedReceipt = $this->paymentService->generateReceiptNumber();

        return view('payments.create', compact('learner', 'suggestedReceipt'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'learner_id' => ['required', 'exists:learners,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,mpesa,bank_transfer,cheque'],
            'transaction_reference' => ['nullable', 'string', 'max:50'],
            'payment_date' => ['required', 'date'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $payment = $this->paymentService->recordPayment($validated, Auth::id());
            return redirect()->route('payments.receipt', $payment->id)
                ->with('success', "Payment of KSh " . number_format($payment->amount, 2) . " recorded. Receipt: {$payment->receipt_number}");
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function receipt(int $id)
    {
        $payment = Payment::with([
            'learner.guardians',
            'learner.currentEnrollment.grade',
            'learner.currentEnrollment.stream',
            'allocations.invoice.term',
            'recordedByUser',
            'reversedByUser'
        ])->findOrFail($id);

        return view('payments.receipt', compact('payment'));
    }

    public function reverse(Request $request, int $id)
    {
        $payment = Payment::findOrFail($id);

        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        try {
            $this->paymentService->reversePayment($payment, $request->reason, Auth::id());
            return back()->with('success', "Payment receipt {$payment->receipt_number} has been reversed.");
        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function dailyReport(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $report = $this->paymentService->getDailyCollectionReport($date);

        return view('payments.daily-report', compact('report', 'date'));
    }

    public function arrearsReport(Request $request)
    {
        $gradeId = $request->input('grade_id');
        $streamId = $request->input('stream_id');

        $report = $this->paymentService->getArrearsReport($gradeId ? (int)$gradeId : null, $streamId ? (int)$streamId : null);
        $grades = Grade::with('streams')->get();

        return view('payments.arrears-report', compact('report', 'grades', 'gradeId', 'streamId'));
    }

    /**
     * Trigger M-Pesa STK push.
     */
    public function triggerMpesaStk(Request $request)
    {
        $validated = $request->validate([
            'learner_id' => ['required', 'exists:learners,id'],
            'phone_number' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $learner = Learner::findOrFail($validated['learner_id']);
        $transaction = $this->mpesaService->initiateStkPush($learner, $validated['phone_number'], (float)$validated['amount']);

        return response()->json([
            'success' => true,
            'message' => 'STK push sent to parent phone. Please enter M-Pesa PIN to complete payment.',
            'checkout_request_id' => $transaction->checkout_request_id,
        ]);
    }

    /**
     * Safaricom Daraja STK callback endpoint.
     */
    public function mpesaCallback(Request $request)
    {
        $callbackData = $request->all();
        $result = $this->mpesaService->processCallback($callbackData);

        return response()->json($result);
    }
}

