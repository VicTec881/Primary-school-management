<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Invoice;
use App\Models\LearnerFeeAccount;
use App\Models\FinancialLedger;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PaymentService
{
    /**
     * Generate the next unique receipt number.
     * e.g., REC-2026-00002
     */
    public function generateReceiptNumber(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        
        return DB::transaction(function () use ($year) {
            $prefix = "REC-{$year}-";
            $maxReceipt = Payment::where('receipt_number', 'LIKE', "{$prefix}%")
                ->orderByDesc('id')
                ->value('receipt_number');

            $seq = 1;
            if ($maxReceipt && preg_match('/-(\d+)$/', $maxReceipt, $matches)) {
                $seq = ((int) $matches[1]) + 1;
            }

            do {
                $candidate = sprintf('REC-%d-%05d', $year, $seq);
                $exists = Payment::where('receipt_number', $candidate)->exists();
                if ($exists) {
                    $seq++;
                }
            } while ($exists);

            return $candidate;
        });
    }

    /**
     * Record a payment and allocate to learner invoices with full auditability.
     */
    public function recordPayment(array $data, ?int $recordedById = null): Payment
    {
        return DB::transaction(function () use ($data, $recordedById) {
            $learner = Learner::findOrFail($data['learner_id']);
            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw new Exception("Payment amount must be greater than zero.");
            }

            // Check reference uniqueness if provided
            $ref = !empty($data['transaction_reference']) ? trim($data['transaction_reference']) : null;
            if ($ref) {
                $duplicate = Payment::where('transaction_reference', $ref)
                    ->where('status', 'completed')
                    ->exists();
                if ($duplicate) {
                    throw new Exception("Duplicate payment reference [{$ref}]. A completed payment with this reference already exists.");
                }
            }

            $receiptNumber = !empty($data['receipt_number']) ? trim($data['receipt_number']) : $this->generateReceiptNumber();

            $payment = Payment::create([
                'receipt_number' => $receiptNumber,
                'learner_id' => $learner->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'], // cash, mpesa, bank_transfer, cheque
                'transaction_reference' => $ref,
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'bank_name' => $data['bank_name'] ?? null,
                'recorded_by' => $recordedById,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            // Allocate payment: FIFO to unpaid/partial invoices
            $remainingPayment = $amount;

            // If a specific invoice was targeted
            if (!empty($data['invoice_id'])) {
                $targetInvoice = Invoice::where('id', $data['invoice_id'])
                    ->where('learner_id', $learner->id)
                    ->first();

                if ($targetInvoice && (float)$targetInvoice->balance > 0) {
                    $allocAmount = min($remainingPayment, (float) $targetInvoice->balance);
                    PaymentAllocation::create([
                        'payment_id' => $payment->id,
                        'invoice_id' => $targetInvoice->id,
                        'amount' => $allocAmount,
                    ]);
                    $targetInvoice->recalculate();
                    $remainingPayment = round($remainingPayment - $allocAmount, 2);
                }
            }

            // Automatic FIFO allocation for remaining amount across pending invoices
            if ($remainingPayment > 0) {
                $unpaidInvoices = Invoice::where('learner_id', $learner->id)
                    ->whereIn('status', ['pending', 'partial'])
                    ->orderBy('issue_date')
                    ->get();

                foreach ($unpaidInvoices as $inv) {
                    if ($remainingPayment <= 0) break;

                    $allocAmount = min($remainingPayment, (float) $inv->balance);
                    PaymentAllocation::create([
                        'payment_id' => $payment->id,
                        'invoice_id' => $inv->id,
                        'amount' => $allocAmount,
                    ]);
                    $inv->recalculate();
                    $remainingPayment = round($remainingPayment - $allocAmount, 2);
                }
            }

            // Update Fee Account
            $account = LearnerFeeAccount::firstOrCreate(['learner_id' => $learner->id]);
            $account->total_paid = round((float) $account->total_paid + $amount, 2);

            $currentBal = round((float) $account->current_balance - $amount, 2);
            if ($currentBal < 0) {
                $account->credit_balance = abs($currentBal);
                $account->current_balance = 0.00;
            } else {
                $account->current_balance = $currentBal;
                $account->credit_balance = 0.00;
            }
            $account->save();

            // Financial Ledger Entry
            FinancialLedger::create([
                'learner_id' => $learner->id,
                'transaction_type' => 'payment',
                'amount' => $amount,
                'balance_after' => $account->current_balance,
                'reference_id' => $payment->id,
                'reference_type' => 'Payment',
                'description' => "Payment received: {$payment->receipt_number} via " . strtoupper($payment->payment_method),
                'recorded_by' => $recordedById,
                'created_at' => now(),
            ]);

            AuditLog::record(
                action: 'payment_recorded',
                model: $payment,
                new: ['receipt' => $receiptNumber, 'amount' => $amount, 'method' => $payment->payment_method],
                userId: $recordedById
            );

            return $payment;
        });
    }

    /**
     * Authorized reversal of a payment.
     * Preserves audit history and never deletes payment record.
     */
    public function reversePayment(Payment $payment, string $reason, int $reversedById): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $reversedById) {
            if ($payment->status === 'reversed') {
                throw new Exception("Payment [{$payment->receipt_number}] is already reversed.");
            }

            $amount = (float) $payment->amount;

            // Remove allocations and recalculate affected invoices
            $allocations = $payment->allocations()->get();
            foreach ($allocations as $allocation) {
                $invoice = $allocation->invoice;
                $allocation->delete();
                $invoice->recalculate();
            }

            // Mark payment as reversed
            $payment->update([
                'status' => 'reversed',
                'reversal_reason' => $reason,
                'reversed_by' => $reversedById,
                'reversed_at' => now(),
            ]);

            // Adjust Learner Fee Account
            $account = LearnerFeeAccount::where('learner_id', $payment->learner_id)->first();
            if ($account) {
                $account->total_paid = max(0, round((float) $account->total_paid - $amount, 2));
                $account->current_balance = round((float) $account->current_balance + $amount, 2);
                $account->credit_balance = 0.00;
                $account->save();

                FinancialLedger::create([
                    'learner_id' => $payment->learner_id,
                    'transaction_type' => 'reversal',
                    'amount' => $amount,
                    'balance_after' => $account->current_balance,
                    'reference_id' => $payment->id,
                    'reference_type' => 'Payment',
                    'description' => "REVERSAL: Receipt {$payment->receipt_number}. Reason: {$reason}",
                    'recorded_by' => $reversedById,
                    'created_at' => now(),
                ]);
            }

            AuditLog::record(
                action: 'payment_reversed',
                model: $payment,
                new: ['receipt' => $payment->receipt_number, 'amount' => $amount, 'reason' => $reason],
                userId: $reversedById
            );

            return $payment;
        });
    }

    /**
     * Daily collection report query.
     */
    public function getDailyCollectionReport(string $date): array
    {
        $payments = Payment::with(['learner', 'recordedByUser'])
            ->whereDate('payment_date', $date)
            ->where('status', 'completed')
            ->orderBy('id')
            ->get();

        $totalCollected = $payments->sum('amount');
        $byMethod = $payments->groupBy('payment_method')->map(fn($group) => $group->sum('amount'));

        return [
            'date' => $date,
            'total' => round($totalCollected, 2),
            'count' => $payments->count(),
            'by_method' => $byMethod,
            'payments' => $payments,
        ];
    }

    /**
     * Arrears report by class or grade.
     */
    public function getArrearsReport(?int $gradeId = null, ?int $streamId = null): array
    {
        $query = Learner::with(['feeAccount', 'currentEnrollment.grade', 'currentEnrollment.stream'])
            ->where('status', 'active')
            ->whereHas('feeAccount', function ($q) {
                $q->where('current_balance', '>', 0);
            });

        if ($streamId) {
            $query->whereHas('currentEnrollment', fn($q) => $q->where('stream_id', $streamId));
        } elseif ($gradeId) {
            $query->whereHas('currentEnrollment', fn($q) => $q->where('grade_id', $gradeId));
        }

        $learners = $query->get()->sortByDesc(fn($l) => (float) $l->feeAccount->current_balance);
        $totalArrears = $learners->sum(fn($l) => (float) $l->feeAccount->current_balance);

        return [
            'total_arrears' => round($totalArrears, 2),
            'learner_count' => $learners->count(),
            'learners' => $learners,
        ];
    }
}

