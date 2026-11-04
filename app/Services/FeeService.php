<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\LearnerFeeAccount;
use App\Models\Discount;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\FinancialLedger;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;

class FeeService
{
    /**
     * Retrieve the active fee structure for a given grade, year, and term.
     */
    public function getActiveFeeSchedule(int $gradeId, ?int $yearId = null, ?int $termId = null): ?FeeStructure
    {
        $yearId = $yearId ?? (AcademicYear::current()->id ?? AcademicYear::first()->id);
        $termId = $termId ?? (Term::current()->id ?? Term::where('academic_year_id', $yearId)->first()->id);

        return FeeStructure::with(['items.feeCategory'])
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->where('grade_id', $gradeId)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Preview itemized fees for registration or estimation.
     * Computes charges server-side.
     */
    public function calculateItemizedFee(int $gradeId, array $optionalCategoryIds = [], ?int $yearId = null, ?int $termId = null): array
    {
        $structure = $this->getActiveFeeSchedule($gradeId, $yearId, $termId);

        if (!$structure) {
            return [
                'fee_structure_id' => null,
                'items' => [],
                'mandatory_total' => 0.00,
                'optional_total' => 0.00,
                'grand_total' => 0.00,
            ];
        }

        $items = [];
        $mandatoryTotal = 0.00;
        $optionalTotal = 0.00;

        foreach ($structure->items as $item) {
            $isOpt = (bool) $item->is_optional;
            $isSelected = !$isOpt || in_array($item->fee_category_id, $optionalCategoryIds);

            $itemData = [
                'id' => $item->id,
                'fee_category_id' => $item->fee_category_id,
                'name' => $item->name,
                'amount' => (float) $item->amount,
                'is_optional' => $isOpt,
                'is_selected' => $isSelected,
            ];

            if (!$isOpt) {
                $mandatoryTotal += (float) $item->amount;
            } elseif ($isSelected) {
                $optionalTotal += (float) $item->amount;
            }

            $items[] = $itemData;
        }

        return [
            'fee_structure_id' => $structure->id,
            'title' => $structure->title,
            'items' => $items,
            'mandatory_total' => round($mandatoryTotal, 2),
            'optional_total' => round($optionalTotal, 2),
            'grand_total' => round($mandatoryTotal + $optionalTotal, 2),
        ];
    }

    /**
     * Generate an invoice for a learner for a specific term and grade.
     * Prevents duplicate invoice generation.
     */
    public function generateTermInvoice(
        Learner $learner,
        int $gradeId,
        int $yearId,
        int $termId,
        array $optionalCategoryIds = [],
        ?int $createdById = null
    ): Invoice {
        return DB::transaction(function () use ($learner, $gradeId, $yearId, $termId, $optionalCategoryIds, $createdById) {
            // Check for duplicate invoice
            $existing = Invoice::where('learner_id', $learner->id)
                ->where('academic_year_id', $yearId)
                ->where('term_id', $termId)
                ->first();

            if ($existing) {
                return $existing;
            }

            $calculation = $this->calculateItemizedFee($gradeId, $optionalCategoryIds, $yearId, $termId);
            $totalAmount = $calculation['grand_total'];

            $invoiceNumber = sprintf('INV-%s-%04d-T%s', date('Y'), $learner->id, $termId);

            $term = Term::find($termId);
            $dueDate = $term ? $term->start_date->copy()->addDays(25) : now()->addDays(25);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'learner_id' => $learner->id,
                'academic_year_id' => $yearId,
                'term_id' => $termId,
                'fee_structure_id' => $calculation['fee_structure_id'],
                'total_amount' => $totalAmount,
                'discount_amount' => 0.00,
                'paid_amount' => 0.00,
                'balance' => $totalAmount,
                'status' => 'pending',
                'issue_date' => now()->toDateString(),
                'due_date' => $dueDate->toDateString(),
            ]);

            // Save line items
            foreach ($calculation['items'] as $item) {
                if ($item['is_selected']) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'fee_category_id' => $item['fee_category_id'],
                        'name' => $item['name'],
                        'amount' => $item['amount'],
                        'is_optional' => $item['is_optional'],
                    ]);
                }
            }

            // Update Fee Account
            $feeAccount = LearnerFeeAccount::firstOrCreate(
                ['learner_id' => $learner->id],
                [
                    'opening_balance' => 0.00,
                    'total_billed' => 0.00,
                    'total_paid' => 0.00,
                    'total_discounted' => 0.00,
                    'current_balance' => 0.00,
                    'credit_balance' => 0.00,
                ]
            );

            $feeAccount->total_billed = round((float) $feeAccount->total_billed + $totalAmount, 2);
            $feeAccount->current_balance = round((float) $feeAccount->current_balance + $totalAmount, 2);
            $feeAccount->save();

            // Post to ledger
            FinancialLedger::create([
                'learner_id' => $learner->id,
                'transaction_type' => 'charge',
                'amount' => $totalAmount,
                'balance_after' => $feeAccount->current_balance,
                'reference_id' => $invoice->id,
                'reference_type' => 'Invoice',
                'description' => "Term fee billing {$invoice->invoice_number}",
                'recorded_by' => $createdById,
                'created_at' => now(),
            ]);

            AuditLog::record(
                action: 'invoice_generated',
                model: $invoice,
                new: ['total' => $totalAmount, 'invoice_number' => $invoiceNumber],
                userId: $createdById
            );

            return $invoice;
        });
    }

    /**
     * Apply an authorized fee waiver or discount to an invoice.
     */
    public function applyDiscount(Invoice $invoice, float $amount, string $reason, ?int $authorizedById = null): Discount
    {
        return DB::transaction(function () use ($invoice, $amount, $reason, $authorizedById) {
            if ($amount <= 0) {
                throw new Exception("Discount amount must be greater than zero.");
            }

            if ($amount > (float) $invoice->balance) {
                throw new Exception("Discount amount (KSh {$amount}) cannot exceed invoice balance (KSh {$invoice->balance}).");
            }

            $discount = Discount::create([
                'learner_id' => $invoice->learner_id,
                'invoice_id' => $invoice->id,
                'reason' => $reason,
                'amount' => $amount,
                'authorized_by' => $authorizedById,
            ]);

            $invoice->recalculate();

            // Update account
            $account = LearnerFeeAccount::where('learner_id', $invoice->learner_id)->first();
            if ($account) {
                $account->total_discounted = round((float) $account->total_discounted + $amount, 2);
                $account->current_balance = round((float) $account->current_balance - $amount, 2);
                $account->save();

                FinancialLedger::create([
                    'learner_id' => $invoice->learner_id,
                    'transaction_type' => 'discount',
                    'amount' => $amount,
                    'balance_after' => $account->current_balance,
                    'reference_id' => $discount->id,
                    'reference_type' => 'Discount',
                    'description' => "Fee discount/waiver: {$reason}",
                    'recorded_by' => $authorizedById,
                    'created_at' => now(),
                ]);
            }

            AuditLog::record(
                action: 'fee_discount_applied',
                model: $discount,
                new: ['amount' => $amount, 'reason' => $reason],
                userId: $authorizedById
            );

            return $discount;
        });
    }

    /**
     * Recalculate and reconcile a learner's fee balance.
     */
    public function recalculateAccount(Learner $learner): LearnerFeeAccount
    {
        return DB::transaction(function () use ($learner) {
            $account = LearnerFeeAccount::firstOrCreate(['learner_id' => $learner->id]);

            $invoices = Invoice::where('learner_id', $learner->id)->where('status', '!=', 'cancelled')->get();
            $totalBilled = (float) $invoices->sum('total_amount');
            $totalDiscounted = (float) $invoices->sum('discount_amount');

            $validPayments = $learner->payments()->where('status', 'completed')->get();
            $totalPaid = (float) $validPayments->sum('amount');

            $netCharges = (float) $account->opening_balance + $totalBilled - $totalDiscounted;
            $currentBalance = round($netCharges - $totalPaid, 2);
            $creditBalance = $currentBalance < 0 ? abs($currentBalance) : 0.00;

            $account->update([
                'total_billed' => $totalBilled,
                'total_discounted' => $totalDiscounted,
                'total_paid' => $totalPaid,
                'current_balance' => max(0, $currentBalance),
                'credit_balance' => $creditBalance,
            ]);

            return $account;
        });
    }
}

