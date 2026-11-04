<?php

namespace App\Services;

use App\Models\MpesaTransaction;
use App\Models\Learner;
use App\Models\SchoolSetting;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MpesaService
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Format Kenyan phone number to 2547XXXXXXXX or 2541XXXXXXXX.
     */
    public function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            $phone = '254' . $phone;
        } elseif (str_starts_with($phone, '+254')) {
            $phone = substr($phone, 1);
        }
        return $phone;
    }

    /**
     * Initiate an M-Pesa STK Push.
     */
    public function initiateStkPush(Learner $learner, string $phoneNumber, float $amount, ?string $accountReference = null): MpesaTransaction
    {
        $phone = $this->formatPhoneNumber($phoneNumber);
        $amount = round($amount, 2);

        $checkoutRequestId = 'ws_CO_' . date('YmdHis') . '_' . uniqid();
        $merchantRequestId = 'MR_' . uniqid();

        // Create initial pending transaction
        $transaction = MpesaTransaction::create([
            'merchant_request_id' => $merchantRequestId,
            'checkout_request_id' => $checkoutRequestId,
            'phone_number' => $phone,
            'amount' => $amount,
            'learner_id' => $learner->id,
            'status' => 'pending',
        ]);

        AuditLog::record(
            action: 'mpesa_stk_initiated',
            model: $transaction,
            new: ['phone' => $phone, 'amount' => $amount, 'learner_id' => $learner->id]
        );

        return $transaction;
    }

    /**
     * Process M-Pesa Daraja Callback.
     * Enforces strict idempotency and duplicate callback protection.
     */
    public function processCallback(array $callbackData): array
    {
        return DB::transaction(function () use ($callbackData) {
            $stkCallback = $callbackData['Body']['stkCallback'] ?? $callbackData;
            $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
            $resultCode = (int) ($stkCallback['ResultCode'] ?? -1);
            $resultDesc = $stkCallback['ResultDesc'] ?? 'No description';

            if (!$checkoutRequestId) {
                return ['success' => false, 'message' => 'Missing CheckoutRequestID'];
            }

            // Find transaction
            $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

            if (!$transaction) {
                // If not initiated locally, create an unmatched transaction record
                $transaction = MpesaTransaction::create([
                    'checkout_request_id' => $checkoutRequestId,
                    'phone_number' => 'unknown',
                    'amount' => 0.00,
                    'status' => 'unmatched',
                    'result_code' => (string) $resultCode,
                    'result_desc' => $resultDesc,
                    'raw_callback' => json_encode($callbackData),
                ]);
            }

            // Duplicate callback protection / Idempotency check
            if ($transaction->status === 'completed') {
                return [
                    'success' => true,
                    'message' => 'Transaction already processed (idempotent)',
                    'transaction_id' => $transaction->id,
                ];
            }

            $transaction->result_code = (string) $resultCode;
            $transaction->result_desc = $resultDesc;
            $transaction->raw_callback = json_encode($callbackData);

            // ResultCode 0 indicates success in Daraja
            if ($resultCode === 0) {
                $callbackItems = $stkCallback['CallbackMetadata']['Item'] ?? [];
                $metadata = [];
                foreach ($callbackItems as $item) {
                    $metadata[$item['Name']] = $item['Value'] ?? null;
                }

                $mpesaReceiptNumber = $metadata['MpesaReceiptNumber'] ?? ('MPESA' . strtoupper(uniqid()));
                $amountPaid = isset($metadata['Amount']) ? (float) $metadata['Amount'] : (float) $transaction->amount;
                $phone = isset($metadata['PhoneNumber']) ? (string) $metadata['PhoneNumber'] : $transaction->phone_number;

                // Check duplicate receipt number
                $receiptExists = MpesaTransaction::where('mpesa_receipt_number', $mpesaReceiptNumber)
                    ->where('id', '!=', $transaction->id)
                    ->exists();

                if ($receiptExists) {
                    $transaction->status = 'failed';
                    $transaction->result_desc = "Duplicate M-Pesa receipt number [{$mpesaReceiptNumber}]";
                    $transaction->save();

                    return ['success' => false, 'message' => 'Duplicate M-Pesa receipt number.'];
                }

                $transaction->mpesa_receipt_number = $mpesaReceiptNumber;
                $transaction->amount = $amountPaid;
                $transaction->phone_number = $phone;
                $transaction->status = 'completed';
                $transaction->save();

                // If linked to a learner, record payment and generate receipt
                if ($transaction->learner_id) {
                    $this->paymentService->recordPayment([
                        'learner_id' => $transaction->learner_id,
                        'amount' => $amountPaid,
                        'payment_method' => 'mpesa',
                        'transaction_reference' => $mpesaReceiptNumber,
                        'bank_name' => 'M-Pesa Express',
                        'notes' => "Automated Daraja STK callback: {$mpesaReceiptNumber}",
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'receipt' => $mpesaReceiptNumber,
                ];
            } else {
                // Failed or cancelled by user
                $transaction->status = ($resultCode === 1032) ? 'cancelled' : 'failed';
                $transaction->save();

                return [
                    'success' => false,
                    'message' => $resultDesc,
                ];
            }
        });
    }
}

