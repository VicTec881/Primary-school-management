<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\Guardian;
use App\Models\LearnerEnrollment;
use App\Models\LearnerFeeAccount;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\SchoolSetting;
use App\Models\AuditLog;
use App\Models\ImportBatch;
use App\Models\ImportError;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class AdmissionService
{
    public function __construct(
        protected FeeService $feeService
    ) {}

    /**
     * Generate the next unique admission number.
     * Supports formats like: {PREFIX}/{YEAR}/{SEQ:4} => KPS/2026/0004
     */
    public function generateAdmissionNumber(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = SchoolSetting::get('admission_prefix', 'KPS');
        
        return DB::transaction(function () use ($prefix, $year) {
            // Count existing learners for the current year
            $pattern = "{$prefix}/{$year}/%";
            $maxAdmission = Learner::where('admission_number', 'LIKE', $pattern)
                ->orderByDesc('id')
                ->value('admission_number');

            $seq = 1;
            if ($maxAdmission && preg_match('/\/(\d+)$/', $maxAdmission, $matches)) {
                $seq = ((int) $matches[1]) + 1;
            }

            do {
                $candidate = sprintf('%s/%d/%04d', $prefix, $year, $seq);
                $exists = Learner::where('admission_number', $candidate)->exists();
                if ($exists) {
                    $seq++;
                }
            } while ($exists);

            return $candidate;
        });
    }

    /**
     * Register a new learner with full transactional integrity.
     */
    public function registerLearner(array $data, ?int $registeredBy = null): Learner
    {
        return DB::transaction(function () use ($data, $registeredBy) {
            $year = AcademicYear::current() ?? AcademicYear::firstOrFail();
            $term = Term::current() ?? Term::where('academic_year_id', $year->id)->firstOrFail();

            // Admission number
            $admissionNumber = !empty($data['admission_number'])
                ? trim($data['admission_number'])
                : $this->generateAdmissionNumber((int) date('Y', strtotime($data['admission_date'] ?? 'now')));

            if (Learner::where('admission_number', $admissionNumber)->exists()) {
                throw new Exception("Admission number [{$admissionNumber}] already exists.");
            }

            // Create learner
            $learner = Learner::create([
                'admission_number' => $admissionNumber,
                'first_name' => trim($data['first_name']),
                'middle_name' => isset($data['middle_name']) ? trim($data['middle_name']) : null,
                'last_name' => trim($data['last_name']),
                'gender' => strtolower($data['gender']),
                'dob' => $data['dob'],
                'admission_date' => $data['admission_date'] ?? date('Y-m-d'),
                'nemis_number' => !empty($data['nemis_number']) ? trim($data['nemis_number']) : null,
                'assessment_number' => !empty($data['assessment_number']) ? trim($data['assessment_number']) : null,
                'previous_school' => $data['previous_school'] ?? null,
                'status' => 'active',
                'medical_notes' => $data['medical_notes'] ?? null,
                'special_needs' => $data['special_needs'] ?? null,
                'photo_path' => $data['photo_path'] ?? null,
            ]);

            // Create fee account
            LearnerFeeAccount::create([
                'learner_id' => $learner->id,
                'opening_balance' => (float) ($data['opening_balance'] ?? 0.00),
                'total_billed' => 0.00,
                'total_paid' => 0.00,
                'total_discounted' => 0.00,
                'current_balance' => (float) ($data['opening_balance'] ?? 0.00),
                'credit_balance' => 0.00,
            ]);

            // Enroll in grade and stream
            $stream = Stream::with('grade')->findOrFail($data['stream_id']);
            $enrollment = LearnerEnrollment::create([
                'learner_id' => $learner->id,
                'academic_year_id' => $year->id,
                'grade_id' => $stream->grade_id,
                'stream_id' => $stream->id,
                'status' => 'enrolled',
                'date_enrolled' => $data['admission_date'] ?? date('Y-m-d'),
            ]);

            // Link parent / guardian
            if (!empty($data['guardian_phone']) && !empty($data['guardian_name'])) {
                $nameParts = explode(' ', trim($data['guardian_name']), 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? 'Guardian';

                $guardian = Guardian::firstOrCreate(
                    ['phone' => trim($data['guardian_phone'])],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'national_id' => $data['guardian_id_number'] ?? null,
                        'email' => $data['guardian_email'] ?? null,
                        'address' => $data['guardian_address'] ?? null,
                    ]
                );

                $learner->guardians()->syncWithoutDetaching([
                    $guardian->id => [
                        'relationship' => $data['guardian_relationship'] ?? 'Parent',
                        'is_primary' => true,
                        'authorized_to_collect' => true,
                    ]
                ]);
            }

            // Optional second emergency contact
            if (!empty($data['emergency_contact_phone']) && !empty($data['emergency_contact_name'])) {
                $eParts = explode(' ', trim($data['emergency_contact_name']), 2);
                $eGuardian = Guardian::firstOrCreate(
                    ['phone' => trim($data['emergency_contact_phone'])],
                    [
                        'first_name' => $eParts[0],
                        'last_name' => $eParts[1] ?? 'Contact',
                        'emergency_priority' => 2,
                    ]
                );

                $learner->guardians()->syncWithoutDetaching([
                    $eGuardian->id => [
                        'relationship' => $data['emergency_relationship'] ?? 'Emergency Contact',
                        'is_primary' => false,
                        'authorized_to_collect' => false,
                    ]
                ]);
            }

            // Assign automated term invoice based on grade fee structure
            $optionalCategoryIds = $data['optional_fee_category_ids'] ?? [];
            $this->feeService->generateTermInvoice(
                $learner,
                $stream->grade_id,
                $year->id,
                $term->id,
                $optionalCategoryIds,
                $registeredBy
            );

            // Audit Log
            AuditLog::record(
                action: 'admission_created',
                model: $learner,
                new: ['admission_number' => $learner->admission_number, 'stream' => $stream->full_name],
                userId: $registeredBy
            );

            return $learner;
        });
    }

    /**
     * Import existing learner records from CSV/array data safely.
     */
    public function importLearners(array $rows, string $filename, ?int $userId = null): array
    {
        $batch = ImportBatch::create([
            'model_type' => 'learners',
            'filename' => $filename,
            'total_rows' => count($rows),
            'successful_rows' => 0,
            'failed_rows' => 0,
            'status' => 'processing',
            'created_by' => $userId,
        ]);

        $year = AcademicYear::current() ?? AcademicYear::first();
        $term = Term::current() ?? Term::where('academic_year_id', $year->id)->first();
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // header offset

            try {
                DB::transaction(function () use ($row, $rowNum, $batch, $year, $term, $userId, &$successful) {
                    // Required fields validation
                    if (empty($row['first_name']) || empty($row['last_name'])) {
                        throw new Exception("Missing first_name or last_name.");
                    }

                    $adm = !empty($row['admission_number']) ? trim($row['admission_number']) : null;
                    if ($adm && Learner::where('admission_number', $adm)->exists()) {
                        throw new Exception("Duplicate admission number: {$adm}");
                    }

                    if (!$adm) {
                        $adm = $this->generateAdmissionNumber();
                    }

                    // Resolve grade & stream
                    $gradeName = trim($row['grade'] ?? 'Grade 1');
                    $streamName = trim($row['stream'] ?? 'Red');

                    $grade = Grade::where('name', $gradeName)
                        ->orWhere('code', $gradeName)
                        ->first();

                    if (!$grade) {
                        $grade = Grade::where('code', 'G1')->first() ?? Grade::firstOrFail();
                    }

                    $stream = Stream::where('grade_id', $grade->id)
                        ->where('name', 'LIKE', $streamName)
                        ->first() ?? Stream::where('grade_id', $grade->id)->firstOrFail();

                    // Create Learner
                    $dob = !empty($row['dob']) ? date('Y-m-d', strtotime($row['dob'])) : '2019-01-01';
                    $admDate = !empty($row['admission_date']) ? date('Y-m-d', strtotime($row['admission_date'])) : date('Y-m-d');

                    $learner = Learner::create([
                        'admission_number' => $adm,
                        'first_name' => trim($row['first_name']),
                        'middle_name' => !empty($row['middle_name']) ? trim($row['middle_name']) : null,
                        'last_name' => trim($row['last_name']),
                        'gender' => !empty($row['gender']) && strtolower(substr($row['gender'], 0, 1)) === 'f' ? 'female' : 'male',
                        'dob' => $dob,
                        'admission_date' => $admDate,
                        'nemis_number' => !empty($row['nemis_number']) ? trim($row['nemis_number']) : null,
                        'previous_school' => !empty($row['previous_school']) ? trim($row['previous_school']) : null,
                        'status' => 'active',
                    ]);

                    // Opening fee balance
                    $openingBalance = isset($row['fee_balance']) ? (float) $row['fee_balance'] : 0.00;
                    LearnerFeeAccount::create([
                        'learner_id' => $learner->id,
                        'opening_balance' => $openingBalance,
                        'total_billed' => 0.00,
                        'total_paid' => 0.00,
                        'total_discounted' => 0.00,
                        'current_balance' => $openingBalance,
                        'credit_balance' => 0.00,
                    ]);

                    // Enrollment
                    LearnerEnrollment::create([
                        'learner_id' => $learner->id,
                        'academic_year_id' => $year->id,
                        'grade_id' => $grade->id,
                        'stream_id' => $stream->id,
                        'status' => 'enrolled',
                        'date_enrolled' => $admDate,
                    ]);

                    // Guardian if provided
                    if (!empty($row['guardian_phone']) && !empty($row['guardian_name'])) {
                        $parts = explode(' ', trim($row['guardian_name']), 2);
                        $g = Guardian::firstOrCreate(
                            ['phone' => trim($row['guardian_phone'])],
                            ['first_name' => $parts[0], 'last_name' => $parts[1] ?? 'Parent']
                        );
                        $learner->guardians()->syncWithoutDetaching([$g->id => ['relationship' => 'Parent', 'is_primary' => true]]);
                    }

                    // Assign active term invoice
                    $this->feeService->generateTermInvoice($learner, $grade->id, $year->id, $term->id, [], $userId);

                    $successful++;
                });
            } catch (Exception $e) {
                $failed++;
                ImportError::create([
                    'import_batch_id' => $batch->id,
                    'row_number' => $rowNum,
                    'raw_data' => json_encode($row),
                    'error_message' => $e->getMessage(),
                ]);
                $errors[] = "Row {$rowNum}: {$e->getMessage()}";
            }
        }

        $batch->update([
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'status' => 'completed',
        ]);

        return [
            'batch_id' => $batch->id,
            'total' => count($rows),
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}

