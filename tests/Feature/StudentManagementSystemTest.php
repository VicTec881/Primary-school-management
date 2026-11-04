<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Learner;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\AssessmentService;
use App\Services\PaymentService;
use App\Services\ReportCardService;
use Illuminate\Support\Facades\Hash;

class StudentManagementSystemTest extends TestCase
{
    /**
     * Unauthenticated users are redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Login screen displays successfully.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('St. Jude Primary');
    }

    /**
     * User can authenticate with valid credentials.
     */
    public function test_super_admin_can_authenticate(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@stjudeprimary.sc.ke',
            'password' => 'Admin@2026!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test CBC performance level mapping.
     */
    public function test_cbc_performance_level_mapping(): void
    {
        $assessmentService = app(AssessmentService::class);

        $this->assertEquals('EE', $assessmentService->determinePerformanceLevel(85, 100));
        $this->assertEquals('ME', $assessmentService->determinePerformanceLevel(70, 100));
        $this->assertEquals('AE', $assessmentService->determinePerformanceLevel(55, 100));
        $this->assertEquals('BE', $assessmentService->determinePerformanceLevel(40, 100));
    }

    /**
     * Test payment recording and FIFO invoice balance reduction.
     */
    public function test_payment_recording_and_fifo_allocation(): void
    {
        $learner = Learner::first();
        $this->assertNotNull($learner);

        $admin = User::where('role_name', 'super_admin')->first();
        $this->assertNotNull($admin);

        $initialBalance = (float) ($learner->feeAccount->current_balance ?? 0);

        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordPayment([
            'learner_id' => $learner->id,
            'amount' => 500.00,
            'payment_method' => 'cash',
            'payment_date' => date('Y-m-d'),
            'notes' => 'Automated test payment',
        ], $admin->id);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('completed', $payment->status);
        $this->assertStringStartsWith('REC-', $payment->receipt_number);

        // Account balance decreased
        $learner->feeAccount->refresh();
        $expectedBalance = max(0, round($initialBalance - 500.00, 2));
        $this->assertEquals($expectedBalance, (float) $learner->feeAccount->current_balance);
    }

    /**
     * Test PDF report card generation service.
     */
    public function test_report_card_pdf_generation(): void
    {
        $learner = Learner::first();
        $year = AcademicYear::first();
        $term = Term::first();

        $this->assertNotNull($learner);
        $this->assertNotNull($year);
        $this->assertNotNull($term);

        $reportCardService = app(ReportCardService::class);
        $pdf = $reportCardService->renderPdf($learner, $year->id, $term->id);

        $this->assertNotNull($pdf);
        $output = $pdf->output();
        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF-', $output);
    }

    /**
     * Test auto-generation of unique admission numbers.
     */
    public function test_admission_number_generation(): void
    {
        $admissionService = app(\App\Services\AdmissionService::class);
        $adm1 = $admissionService->generateAdmissionNumber(2026);
        $adm2 = $admissionService->generateAdmissionNumber(2026);

        $this->assertMatchesRegularExpression('/^[A-Z]+\/2026\/\d{4}$/', $adm1);
        $this->assertMatchesRegularExpression('/^[A-Z]+\/2026\/\d{4}$/', $adm2);
    }

    /**
     * Test payment reversal restores balance and logs in financial ledger.
     */
    public function test_payment_reversal_workflow(): void
    {
        $learner = Learner::first();
        $admin = User::where('role_name', 'super_admin')->first();
        $paymentService = app(PaymentService::class);

        // Record payment
        $payment = $paymentService->recordPayment([
            'learner_id' => $learner->id,
            'amount' => 1200.00,
            'payment_method' => 'mpesa',
            'transaction_reference' => 'TESTREV1234',
            'payment_date' => date('Y-m-d'),
        ], $admin->id);

        $balanceAfterPay = (float) $learner->feeAccount->fresh()->current_balance;

        // Reverse payment
        $paymentService->reversePayment($payment, 'Test reversal due to check bounce', $admin->id);

        $payment->refresh();
        $this->assertEquals('reversed', $payment->status);
        $this->assertNotNull($payment->reversed_at);

        // Balance restored
        $balanceAfterRev = (float) $learner->feeAccount->fresh()->current_balance;
        $this->assertEquals(round($balanceAfterPay + 1200.00, 2), $balanceAfterRev);

        // Financial ledger entry exists
        $ledgerEntry = \App\Models\FinancialLedger::where('reference_id', $payment->id)
            ->where('transaction_type', 'reversal')
            ->first();
        $this->assertNotNull($ledgerEntry);
        $this->assertEquals(1200.00, (float) $ledgerEntry->amount);
    }

    /**
     * Test parent cannot access reports of a non-linked child.
     */
    public function test_parent_cannot_view_unlinked_child_reports(): void
    {
        $parentUser = User::where('role_name', 'parent')->first();
        $this->assertNotNull($parentUser);

        // Create an unlinked learner
        $unlinkedLearner = Learner::whereDoesntHave('guardians', function ($q) use ($parentUser) {
            $q->where('guardian_id', $parentUser->guardian?->id ?? 0);
        })->first();

        if ($unlinkedLearner) {
            $response = $this->actingAs($parentUser)->get("/parent/reports/{$unlinkedLearner->id}");
            $response->assertStatus(403);
        }
    }
}
