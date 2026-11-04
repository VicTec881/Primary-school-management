<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsService
{
    /**
     * Send an SMS notification to a Kenyan phone number (+254 / 07... / 01...).
     */
    public function sendSms(string $phoneNumber, string $message, ?string $recipientType = null, ?int $recipientId = null): array
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
        $senderId = SchoolSetting::get('sms_sender_id', 'STJUDE');

        // Log initial attempt
        $log = NotificationLog::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'recipient_contact' => $normalizedPhone,
            'channel' => 'sms',
            'subject' => 'SMS Notification',
            'message' => $message,
            'status' => 'queued',
        ]);

        try {
            $apiKey = config('services.africastalking.api_key', env('AT_API_KEY'));
            $username = config('services.africastalking.username', env('AT_USERNAME', 'sandbox'));

            if (!empty($apiKey) && $apiKey !== 'sandbox_key') {
                // Africa's Talking API endpoint
                $endpoint = $username === 'sandbox'
                    ? 'https://api.sandbox.africastalking.com/version1/messaging'
                    : 'https://api.africastalking.com/version1/messaging';

                $response = Http::asForm()->withHeaders([
                    'apiKey' => $apiKey,
                    'Accept' => 'application/json',
                ])->post($endpoint, [
                    'username' => $username,
                    'to' => $normalizedPhone,
                    'message' => $message,
                    'from' => $senderId,
                ]);

                $result = $response->json();
                $log->update([
                    'status' => $response->successful() ? 'sent' : 'failed',
                    'provider_response' => json_encode($result),
                ]);

                return ['success' => $response->successful(), 'response' => $result];
            } else {
                // Simulation mode (for development / local testing)
                Log::info("SIMULATED SMS to {$normalizedPhone} via {$senderId}: {$message}");

                $log->update([
                    'status' => 'sent',
                    'provider_response' => json_encode([
                        'status' => 'success',
                        'mode' => 'simulated',
                        'recipient' => $normalizedPhone,
                        'timestamp' => now()->toIso8601String(),
                    ]),
                ]);

                return ['success' => true, 'response' => ['status' => 'simulated']];
            }
        } catch (Exception $e) {
            Log::error("Failed to send SMS to {$normalizedPhone}: " . $e->getMessage());

            $log->update([
                'status' => 'failed',
                'provider_response' => json_encode(['error' => $e->getMessage()]),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send fee payment receipt confirmation SMS to parent.
     */
    public function sendPaymentReceiptSms(string $phone, string $learnerName, string $receiptNumber, float $amount, float $balance): array
    {
        $schoolName = SchoolSetting::get('school_name', 'St. Jude Primary');
        $msg = "Dear Parent, we have received KSh " . number_format($amount, 2) . " for {$learnerName}. Receipt: {$receiptNumber}. Outstanding fee balance: KSh " . number_format($balance, 2) . ". Thank you for your payment. - {$schoolName}";
        
        return $this->sendSms($phone, $msg, 'Guardian');
    }

    /**
     * Send absence notification SMS to parent.
     */
    public function sendAbsenceAlertSms(string $phone, string $learnerName, string $date): array
    {
        $schoolName = SchoolSetting::get('school_name', 'St. Jude Primary');
        $msg = "Dear Parent, {$learnerName} was marked ABSENT today, {$date}. Please contact the class teacher or school administration if this was unexcused. - {$schoolName}";

        return $this->sendSms($phone, $msg, 'Guardian');
    }

    /**
     * Format phone numbers to standard E.164 (+254...).
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            return '+254' . substr($clean, 1);
        } elseif (str_starts_with($clean, '254')) {
            return '+' . $clean;
        } elseif (str_starts_with($clean, '+254')) {
            return $clean;
        }
        return '+254' . ltrim($clean, '+');
    }
}
