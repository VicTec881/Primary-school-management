<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\SchoolSetting;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\LearningArea;
use App\Models\StaffProfile;
use App\Models\ClassTeacher;
use App\Models\TeacherAssignment;
use App\Models\Learner;
use App\Models\Guardian;
use App\Models\LearnerEnrollment;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LearnerFeeAccount;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\FinancialLedger;
use App\Models\GradingScale;
use App\Models\GradingScaleDetail;
use App\Models\AssessmentType;
use App\Models\AssessmentRecord;
use App\Models\ReportCard;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\SchoolEvent;
use App\Models\Announcement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ROLES & PERMISSIONS
        $roles = [
            'super_admin' => ['display_name' => 'Super Administrator', 'description' => 'Full administrative control over school settings, users, and audit logs'],
            'headteacher' => ['display_name' => 'Headteacher', 'description' => 'Academic supervision, approval of reports and overall school monitoring'],
            'bursar' => ['display_name' => 'Bursar / Accounts Officer', 'description' => 'Fee structures, invoices, payment receipts, accounting reports, and reconciliations'],
            'registrar' => ['display_name' => 'Registrar / Admissions Officer', 'description' => 'Learner admissions, class allocations, transfers, and student documentation'],
            'teacher' => ['display_name' => 'Teacher', 'description' => 'Attendance registers, CBE competency assessments, and report card remarks'],
            'parent' => ['display_name' => 'Parent / Guardian', 'description' => 'Access to linked children records, fee statements, report cards, and announcements'],
        ];

        $roleModels = [];
        foreach ($roles as $name => $meta) {
            $roleModels[$name] = Role::firstOrCreate(['name' => $name], $meta);
        }

        $permissions = [
            ['name' => 'manage_settings', 'display_name' => 'Manage School Settings', 'group' => 'admin'],
            ['name' => 'manage_users', 'display_name' => 'Manage Staff & Users', 'group' => 'admin'],
            ['name' => 'view_audit_logs', 'display_name' => 'View System Audit Logs', 'group' => 'admin'],
            ['name' => 'manage_admissions', 'display_name' => 'Manage Admissions & Learners', 'group' => 'learners'],
            ['name' => 'view_learners', 'display_name' => 'View Learner Profiles', 'group' => 'learners'],
            ['name' => 'manage_classes', 'display_name' => 'Manage Grades & Streams', 'group' => 'academic'],
            ['name' => 'manage_assessments', 'display_name' => 'Manage CBE Assessments', 'group' => 'academic'],
            ['name' => 'view_report_cards', 'display_name' => 'View & Print Report Cards', 'group' => 'academic'],
            ['name' => 'approve_report_cards', 'display_name' => 'Approve Report Cards', 'group' => 'academic'],
            ['name' => 'manage_attendance', 'display_name' => 'Record Daily Attendance', 'group' => 'attendance'],
            ['name' => 'manage_fees', 'display_name' => 'Configure Fee Structures', 'group' => 'finance'],
            ['name' => 'record_payments', 'display_name' => 'Record Payments & Issue Receipts', 'group' => 'finance'],
            ['name' => 'reverse_payments', 'display_name' => 'Reverse Payments & Waivers', 'group' => 'finance'],
            ['name' => 'view_finance_reports', 'display_name' => 'View Financial Summaries', 'group' => 'finance'],
            ['name' => 'parent_portal_access', 'display_name' => 'Parent Portal Access', 'group' => 'portal'],
        ];

        foreach ($permissions as $p) {
            $perm = Permission::firstOrCreate(['name' => $p['name']], $p);
            $roleModels['super_admin']->permissions()->syncWithoutDetaching([$perm->id]);
        }

        // Assign specific permissions to roles
        $roleModels['headteacher']->givePermission('view_learners');
        $roleModels['headteacher']->givePermission('manage_classes');
        $roleModels['headteacher']->givePermission('manage_assessments');
        $roleModels['headteacher']->givePermission('view_report_cards');
        $roleModels['headteacher']->givePermission('approve_report_cards');
        $roleModels['headteacher']->givePermission('manage_attendance');
        $roleModels['headteacher']->givePermission('view_finance_reports');

        $roleModels['bursar']->givePermission('view_learners');
        $roleModels['bursar']->givePermission('manage_fees');
        $roleModels['bursar']->givePermission('record_payments');
        $roleModels['bursar']->givePermission('reverse_payments');
        $roleModels['bursar']->givePermission('view_finance_reports');

        $roleModels['registrar']->givePermission('manage_admissions');
        $roleModels['registrar']->givePermission('view_learners');
        $roleModels['registrar']->givePermission('manage_classes');

        $roleModels['teacher']->givePermission('view_learners');
        $roleModels['teacher']->givePermission('manage_assessments');
        $roleModels['teacher']->givePermission('view_report_cards');
        $roleModels['teacher']->givePermission('manage_attendance');

        $roleModels['parent']->givePermission('parent_portal_access');

        // 2. DEFAULT USERS
        $usersData = [
            [
                'name' => 'Dr. Bernard Ouma',
                'email' => 'admin@stjudeprimary.sc.ke',
                'phone' => '+254711000100',
                'role_name' => 'super_admin',
                'password' => Hash::make('Admin@2026!'),
                'is_active' => true,
            ],
            [
                'name' => 'Grace Nduta (Headteacher)',
                'email' => 'headteacher@stjudeprimary.sc.ke',
                'phone' => '+254722000200',
                'role_name' => 'headteacher',
                'password' => Hash::make('Head@2026!'),
                'is_active' => true,
            ],
            [
                'name' => 'Peter Kamau (Bursar)',
                'email' => 'bursar@stjudeprimary.sc.ke',
                'phone' => '+254733000300',
                'role_name' => 'bursar',
                'password' => Hash::make('Bursar@2026!'),
                'is_active' => true,
            ],
            [
                'name' => 'Lucy Achieng (Registrar)',
                'email' => 'registrar@stjudeprimary.sc.ke',
                'phone' => '+254744000400',
                'role_name' => 'registrar',
                'password' => Hash::make('Registrar@2026!'),
                'is_active' => true,
            ],
            [
                'name' => 'James Otieno (Tr. Math/Sci)',
                'email' => 'teacher.otieno@stjudeprimary.sc.ke',
                'phone' => '+254755000501',
                'role_name' => 'teacher',
                'password' => Hash::make('Teacher@2026!'),
                'is_active' => true,
            ],
            [
                'name' => 'Mary Wambui (Tr. Lang/CRE)',
                'email' => 'teacher.wambui@stjudeprimary.sc.ke',
                'phone' => '+254755000502',
                'role_name' => 'teacher',
                'password' => Hash::make('Teacher@2026!'),
                'is_active' => true,
            ],
            [
                'name' => 'David Mwangi (Parent)',
                'email' => 'parent.mwangi@gmail.com',
                'phone' => '+254766000600',
                'role_name' => 'parent',
                'password' => Hash::make('Parent@2026!'),
                'is_active' => true,
            ],
        ];

        $userModels = [];
        foreach ($usersData as $ud) {
            $user = User::updateOrCreate(['email' => $ud['email']], $ud);
            $user->roles()->sync([$roleModels[$ud['role_name']]->id]);
            $userModels[$ud['role_name']][] = $user;
        }

        // Staff profiles for teachers
        foreach ([$userModels['teacher'][0], $userModels['teacher'][1]] as $idx => $tUser) {
            StaffProfile::updateOrCreate(
                ['user_id' => $tUser->id],
                [
                    'tsc_number' => 'TSC-' . (548900 + $idx),
                    'national_id' => '2890451' . $idx,
                    'phone' => $tUser->phone,
                    'designation' => 'Senior Teacher I',
                    'date_of_joining' => Carbon::parse('2022-01-10'),
                ]
            );
        }

        // 3. SCHOOL SETTINGS
        $settings = [
            'school_name' => 'St. Jude Primary & Junior School',
            'school_motto' => 'Excellence in Knowledge and Character',
            'school_box' => 'P.O. Box 45120 - 00100',
            'school_town' => 'Nairobi, Kenya',
            'school_address' => 'Off Waiyaki Way, Westlands Sub-County, Nairobi County',
            'school_phone' => '+254 711 234 567',
            'school_email' => 'info@stjudeprimary.sc.ke',
            'currency_code' => 'KES',
            'currency_symbol' => 'KSh',
            'timezone' => 'Africa/Nairobi',
            'admission_prefix' => 'KPS',
            'admission_format' => '{PREFIX}/{YEAR}/{SEQ:4}',
            'mpesa_paybill' => '247247',
            'mpesa_account_name' => 'ST JUDE PRIMARY SCHOOL',
            'mpesa_consumer_key' => 'mock_daraja_consumer_key',
            'mpesa_consumer_secret' => 'mock_daraja_consumer_secret',
            'mpesa_passkey' => 'mock_daraja_passkey',
            'sms_sender_id' => 'STJUDE_SCH',
        ];

        foreach ($settings as $key => $val) {
            SchoolSetting::set($key, $val, 'general', 'string');
        }

        // 4. ACADEMIC YEARS & TERMS
        $year2025 = AcademicYear::updateOrCreate(
            ['name' => '2025'],
            ['start_date' => '2025-01-06', 'end_date' => '2025-10-31', 'is_current' => false]
        );

        $year2026 = AcademicYear::updateOrCreate(
            ['name' => '2026'],
            ['start_date' => '2026-01-05', 'end_date' => '2026-10-30', 'is_current' => true]
        );

        $term1 = Term::updateOrCreate(
            ['academic_year_id' => $year2026->id, 'term_number' => 1],
            [
                'name' => 'Term 1 2026',
                'start_date' => '2026-01-05',
                'end_date' => '2026-04-03',
                'next_term_begins' => '2026-05-04',
                'is_current' => true,
            ]
        );

        Term::updateOrCreate(
            ['academic_year_id' => $year2026->id, 'term_number' => 2],
            [
                'name' => 'Term 2 2026',
                'start_date' => '2026-05-04',
                'end_date' => '2026-08-07',
                'next_term_begins' => '2026-08-31',
                'is_current' => false,
            ]
        );

        Term::updateOrCreate(
            ['academic_year_id' => $year2026->id, 'term_number' => 3],
            [
                'name' => 'Term 3 2026',
                'start_date' => '2026-08-31',
                'end_date' => '2026-10-30',
                'next_term_begins' => '2027-01-04',
                'is_current' => false,
            ]
        );

        // 5. GRADES & STREAMS
        $gradesList = [
            ['name' => 'Grade 1', 'code' => 'G1', 'level' => 1, 'stage' => 'Lower Primary'],
            ['name' => 'Grade 2', 'code' => 'G2', 'level' => 2, 'stage' => 'Lower Primary'],
            ['name' => 'Grade 3', 'code' => 'G3', 'level' => 3, 'stage' => 'Lower Primary'],
            ['name' => 'Grade 4', 'code' => 'G4', 'level' => 4, 'stage' => 'Upper Primary'],
            ['name' => 'Grade 5', 'code' => 'G5', 'level' => 5, 'stage' => 'Upper Primary'],
            ['name' => 'Grade 6', 'code' => 'G6', 'level' => 6, 'stage' => 'Upper Primary'],
            ['name' => 'Grade 7', 'code' => 'G7', 'level' => 7, 'stage' => 'Junior School'],
        ];

        $streamModels = [];
        foreach ($gradesList as $gData) {
            $grade = Grade::updateOrCreate(['code' => $gData['code']], $gData);
            foreach (['Red', 'Blue'] as $sName) {
                $streamModels[$gData['code'] . '_' . $sName] = Stream::updateOrCreate(
                    ['grade_id' => $grade->id, 'name' => $sName],
                    ['capacity' => 40]
                );
            }
        }

        // Class teacher assignments
        ClassTeacher::updateOrCreate(
            ['academic_year_id' => $year2026->id, 'stream_id' => $streamModels['G1_Red']->id],
            ['teacher_id' => $userModels['teacher'][0]->id]
        );
        ClassTeacher::updateOrCreate(
            ['academic_year_id' => $year2026->id, 'stream_id' => $streamModels['G2_Red']->id],
            ['teacher_id' => $userModels['teacher'][1]->id]
        );

        // 6. LEARNING AREAS (Kenyan CBC Subjects)
        $learningAreasList = [
            ['name' => 'English Language Activities', 'code' => 'ENG', 'stage' => 'All'],
            ['name' => 'Kiswahili Language Activities', 'code' => 'KISW', 'stage' => 'All'],
            ['name' => 'Mathematics Activities', 'code' => 'MATH', 'stage' => 'All'],
            ['name' => 'Science & Technology', 'code' => 'SCI', 'stage' => 'All'],
            ['name' => 'Social Studies', 'code' => 'SS', 'stage' => 'All'],
            ['name' => 'Christian Religious Education', 'code' => 'CRE', 'stage' => 'All'],
            ['name' => 'Creative Arts & Sports', 'code' => 'CAS', 'stage' => 'All'],
            ['name' => 'Agriculture & Nutrition', 'code' => 'AGRI', 'stage' => 'All'],
        ];

        $laModels = [];
        foreach ($learningAreasList as $la) {
            $laModels[$la['code']] = LearningArea::updateOrCreate(['code' => $la['code']], $la);
        }

        // Assign teachers to learning areas in Grade 1 Red
        TeacherAssignment::updateOrCreate(
            [
                'academic_year_id' => $year2026->id,
                'stream_id' => $streamModels['G1_Red']->id,
                'learning_area_id' => $laModels['MATH']->id,
                'teacher_id' => $userModels['teacher'][0]->id,
            ]
        );
        TeacherAssignment::updateOrCreate(
            [
                'academic_year_id' => $year2026->id,
                'stream_id' => $streamModels['G1_Red']->id,
                'learning_area_id' => $laModels['ENG']->id,
                'teacher_id' => $userModels['teacher'][1]->id,
            ]
        );

        // 7. GRADING SCALE (CBC 4-level scale)
        $scale = GradingScale::firstOrCreate(['name' => 'Kenyan CBC 4-Level Performance Scale'], ['is_default' => true]);
        $scaleLevels = [
            ['level' => 4, 'code' => 'EE', 'name' => 'Exceeding Expectations', 'min_score' => 80.00, 'max_score' => 100.00, 'descriptor' => 'Demonstrates deep mastery and consistently applies concepts independently.'],
            ['level' => 3, 'code' => 'ME', 'name' => 'Meeting Expectations', 'min_score' => 65.00, 'max_score' => 79.99, 'descriptor' => 'Successfully grasps key concepts and applies knowledge with minor guidance.'],
            ['level' => 2, 'code' => 'AE', 'name' => 'Approaching Expectations', 'min_score' => 50.00, 'max_score' => 64.99, 'descriptor' => 'Developing understanding; requires targeted practice in core competencies.'],
            ['level' => 1, 'code' => 'BE', 'name' => 'Below Expectations', 'min_score' => 0.00, 'max_score' => 49.99, 'descriptor' => 'Requires immediate structured intervention and foundational remediation.'],
        ];

        foreach ($scaleLevels as $sl) {
            GradingScaleDetail::updateOrCreate(['grading_scale_id' => $scale->id, 'code' => $sl['code']], $sl);
        }

        // 8. ASSESSMENT TYPES
        $aTypes = [
            ['name' => 'Opener Assessment', 'code' => 'OPEN', 'weight' => 20.00],
            ['name' => 'Mid-Term Assessment', 'code' => 'MID', 'weight' => 30.00],
            ['name' => 'End of Term Summative Assessment', 'code' => 'END', 'weight' => 50.00],
        ];
        $aTypeModels = [];
        foreach ($aTypes as $at) {
            $aTypeModels[$at['code']] = AssessmentType::updateOrCreate(['code' => $at['code']], $at);
        }

        // 9. FEE CATEGORIES & STRUCTURES
        $feeCategories = [
            ['name' => 'Tuition Fee', 'code' => 'TUIT', 'is_recurring' => true, 'is_optional' => false],
            ['name' => 'Lunch & Midday Snack', 'code' => 'LUNCH', 'is_recurring' => true, 'is_optional' => false],
            ['name' => 'Activity & Sports Levy', 'code' => 'ACT', 'is_recurring' => true, 'is_optional' => false],
            ['name' => 'CBC Learning Materials & Assessment Booklets', 'code' => 'MAT', 'is_recurring' => true, 'is_optional' => false],
            ['name' => 'School Transport Service', 'code' => 'TRANS', 'is_recurring' => true, 'is_optional' => true],
            ['name' => 'Admission & Registration Fee', 'code' => 'ADM', 'is_recurring' => false, 'is_optional' => false],
        ];

        $fcModels = [];
        foreach ($feeCategories as $fc) {
            $fcModels[$fc['code']] = FeeCategory::updateOrCreate(['code' => $fc['code']], $fc);
        }

        // Fee structure for Grade 1 (Term 1 2026)
        $grade1 = Grade::where('code', 'G1')->first();
        $fsG1 = FeeStructure::updateOrCreate(
            [
                'academic_year_id' => $year2026->id,
                'term_id' => $term1->id,
                'grade_id' => $grade1->id,
                'version' => 1,
            ],
            [
                'title' => 'Grade 1 Term 1 2026 Fee Schedule',
                'is_active' => true,
            ]
        );

        $g1Items = [
            ['fee_category_id' => $fcModels['TUIT']->id, 'name' => 'Tuition Fee', 'amount' => 18000.00, 'is_optional' => false],
            ['fee_category_id' => $fcModels['LUNCH']->id, 'name' => 'Hot Lunch & Fruit', 'amount' => 8500.00, 'is_optional' => false],
            ['fee_category_id' => $fcModels['ACT']->id, 'name' => 'Co-Curricular & Swimming', 'amount' => 3500.00, 'is_optional' => false],
            ['fee_category_id' => $fcModels['MAT']->id, 'name' => 'CBC Learning Workbooks', 'amount' => 4000.00, 'is_optional' => false],
            ['fee_category_id' => $fcModels['TRANS']->id, 'name' => 'School Van (Zone A: Westlands)', 'amount' => 9000.00, 'is_optional' => true],
        ];

        foreach ($g1Items as $item) {
            FeeStructureItem::updateOrCreate(
                ['fee_structure_id' => $fsG1->id, 'name' => $item['name']],
                $item
            );
        }

        // Fee structure for Grade 2 (Term 1 2026)
        $grade2 = Grade::where('code', 'G2')->first();
        $fsG2 = FeeStructure::updateOrCreate(
            [
                'academic_year_id' => $year2026->id,
                'term_id' => $term1->id,
                'grade_id' => $grade2->id,
                'version' => 1,
            ],
            [
                'title' => 'Grade 2 Term 1 2026 Fee Schedule',
                'is_active' => true,
            ]
        );
        foreach ($g1Items as $item) {
            FeeStructureItem::updateOrCreate(
                ['fee_structure_id' => $fsG2->id, 'name' => $item['name']],
                $item
            );
        }

        // 10. GUARDIAN & SAMPLE LEARNERS
        $guardianUser = $userModels['parent'][0];
        $guardian = Guardian::updateOrCreate(
            ['phone' => '+254766000600'],
            [
                'user_id' => $guardianUser->id,
                'first_name' => 'David',
                'last_name' => 'Mwangi',
                'national_id' => '24567891',
                'email' => 'parent.mwangi@gmail.com',
                'occupation' => 'Senior Systems Engineer',
                'address' => 'House 45, Mountain View Estate, Nairobi',
                'emergency_priority' => 1,
            ]
        );

        $learnersData = [
            [
                'admission_number' => 'KPS/2026/0001',
                'first_name' => 'Brian',
                'middle_name' => 'Kariuki',
                'last_name' => 'Mwangi',
                'gender' => 'male',
                'dob' => '2019-03-14',
                'admission_date' => '2026-01-05',
                'nemis_number' => 'NEMIS-2026-0001',
                'status' => 'active',
                'grade_code' => 'G1',
                'stream_name' => 'Red',
            ],
            [
                'admission_number' => 'KPS/2026/0002',
                'first_name' => 'Faith',
                'middle_name' => 'Wanjiku',
                'last_name' => 'Mwangi',
                'gender' => 'female',
                'dob' => '2018-06-22',
                'admission_date' => '2026-01-05',
                'nemis_number' => 'NEMIS-2026-0002',
                'status' => 'active',
                'grade_code' => 'G2',
                'stream_name' => 'Red',
            ],
            [
                'admission_number' => 'KPS/2026/0003',
                'first_name' => 'Ethan',
                'middle_name' => 'Kipchumba',
                'last_name' => 'Kibet',
                'gender' => 'male',
                'dob' => '2019-08-11',
                'admission_date' => '2026-01-05',
                'nemis_number' => 'NEMIS-2026-0003',
                'status' => 'active',
                'grade_code' => 'G1',
                'stream_name' => 'Red',
            ],
        ];

        foreach ($learnersData as $idx => $ld) {
            $gradeCode = $ld['grade_code'];
            $streamName = $ld['stream_name'];
            unset($ld['grade_code'], $ld['stream_name']);

            $learner = Learner::updateOrCreate(['admission_number' => $ld['admission_number']], $ld);

            // Link first two learners to David Mwangi
            if ($idx < 2) {
                $learner->guardians()->syncWithoutDetaching([
                    $guardian->id => [
                        'relationship' => 'Father',
                        'is_primary' => true,
                        'authorized_to_collect' => true,
                    ]
                ]);
            }

            // Enrollment
            $gr = Grade::where('code', $gradeCode)->first();
            $str = Stream::where('grade_id', $gr->id)->where('name', $streamName)->first();
            LearnerEnrollment::updateOrCreate(
                ['learner_id' => $learner->id, 'academic_year_id' => $year2026->id],
                [
                    'grade_id' => $gr->id,
                    'stream_id' => $str->id,
                    'roll_number' => str_pad($idx + 1, 2, '0', STR_PAD_LEFT),
                    'status' => 'enrolled',
                    'date_enrolled' => '2026-01-05',
                ]
            );

            // Create Invoice
            $invNum = 'INV-2026-' . str_pad($learner->id, 5, '0', STR_PAD_LEFT);
            $invoice = Invoice::updateOrCreate(
                ['learner_id' => $learner->id, 'academic_year_id' => $year2026->id, 'term_id' => $term1->id],
                [
                    'invoice_number' => $invNum,
                    'fee_structure_id' => $fsG1->id,
                    'total_amount' => 34000.00, // Mandatory charges
                    'discount_amount' => 0.00,
                    'paid_amount' => 0.00,
                    'balance' => 34000.00,
                    'status' => 'pending',
                    'issue_date' => '2026-01-05',
                    'due_date' => '2026-01-30',
                ]
            );

            // Invoice items
            foreach ($g1Items as $item) {
                if (!$item['is_optional']) {
                    InvoiceItem::updateOrCreate(
                        ['invoice_id' => $invoice->id, 'name' => $item['name']],
                        [
                            'fee_category_id' => $item['fee_category_id'],
                            'amount' => $item['amount'],
                            'is_optional' => false,
                        ]
                    );
                }
            }

            // Fee Account
            $feeAccount = LearnerFeeAccount::updateOrCreate(
                ['learner_id' => $learner->id],
                [
                    'opening_balance' => 0.00,
                    'total_billed' => 34000.00,
                    'total_paid' => 0.00,
                    'total_discounted' => 0.00,
                    'current_balance' => 34000.00,
                    'credit_balance' => 0.00,
                ]
            );

            // Record a payment for learner 1 (Brian Mwangi)
            if ($idx === 0) {
                $payAmount = 25000.00;
                $receiptNumber = 'REC-2026-00001';
                $payment = Payment::updateOrCreate(
                    ['receipt_number' => $receiptNumber],
                    [
                        'learner_id' => $learner->id,
                        'amount' => $payAmount,
                        'payment_method' => 'mpesa',
                        'transaction_reference' => 'SDF9382109',
                        'payment_date' => '2026-01-10',
                        'bank_name' => 'M-Pesa Paybill 247247',
                        'recorded_by' => $userModels['bursar'][0]->id,
                        'status' => 'completed',
                        'notes' => 'Partial fee payment for Term 1 2026',
                    ]
                );

                PaymentAllocation::updateOrCreate(
                    ['payment_id' => $payment->id, 'invoice_id' => $invoice->id],
                    ['amount' => $payAmount]
                );

                $invoice->recalculate();

                $feeAccount->total_paid = $payAmount;
                $feeAccount->current_balance = 34000.00 - $payAmount;
                $feeAccount->save();

                FinancialLedger::create([
                    'learner_id' => $learner->id,
                    'transaction_type' => 'charge',
                    'amount' => 34000.00,
                    'balance_after' => 34000.00,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'Invoice',
                    'description' => 'Term 1 2026 Fee Invoice ' . $invNum,
                    'recorded_by' => $userModels['bursar'][0]->id,
                    'created_at' => Carbon::parse('2026-01-05 09:00:00'),
                ]);

                FinancialLedger::create([
                    'learner_id' => $learner->id,
                    'transaction_type' => 'payment',
                    'amount' => $payAmount,
                    'balance_after' => 9000.00,
                    'reference_id' => $payment->id,
                    'reference_type' => 'Payment',
                    'description' => 'M-Pesa payment ref SDF9382109',
                    'recorded_by' => $userModels['bursar'][0]->id,
                    'created_at' => Carbon::parse('2026-01-10 14:30:00'),
                ]);
            }

            // Assessments for Brian Mwangi
            if ($idx === 0) {
                $sampleMarks = [
                    'MATH' => ['raw' => 88.0, 'level' => 'EE', 'comment' => 'Exceptional proficiency in addition and patterns.'],
                    'ENG' => ['raw' => 78.0, 'level' => 'ME', 'comment' => 'Very fluent reader; working on handwriting.'],
                    'KISW' => ['raw' => 72.0, 'level' => 'ME', 'comment' => 'Anasoma na kuandika sentensi rahisi vyema.'],
                    'SCI' => ['raw' => 85.0, 'level' => 'EE', 'comment' => 'Observant and active during environmental experiments.'],
                    'CRE' => ['raw' => 90.0, 'level' => 'EE', 'comment' => 'Exemplary moral conduct and participation.'],
                    'CAS' => ['raw' => 80.0, 'level' => 'EE', 'comment' => 'Very agile in athletics and creative drawings.'],
                ];

                foreach ($sampleMarks as $code => $m) {
                    AssessmentRecord::updateOrCreate(
                        [
                            'learner_id' => $learner->id,
                            'academic_year_id' => $year2026->id,
                            'term_id' => $term1->id,
                            'learning_area_id' => $laModels[$code]->id,
                            'assessment_type_id' => $aTypeModels['END']->id,
                        ],
                        [
                            'stream_id' => $str->id,
                            'raw_score' => $m['raw'],
                            'max_score' => 100.00,
                            'performance_level' => $m['level'],
                            'rubric_comments' => $m['comment'],
                            'teacher_id' => $userModels['teacher'][0]->id,
                        ]
                    );
                }

                // Sample published report card
                ReportCard::updateOrCreate(
                    [
                        'learner_id' => $learner->id,
                        'academic_year_id' => $year2026->id,
                        'term_id' => $term1->id,
                    ],
                    [
                        'stream_id' => $str->id,
                        'attendance_days_present' => 62,
                        'attendance_days_absent' => 2,
                        'attendance_days_total' => 64,
                        'class_teacher_remarks' => 'Brian is a hardworking, disciplined and curious learner. Promising potential in STEM activities.',
                        'headteacher_remarks' => 'Outstanding academic achievement and commendable character. Keep aiming high.',
                        'next_term_begins' => '2026-05-04',
                        'status' => 'published',
                        'issued_at' => Carbon::parse('2026-04-03 11:00:00'),
                    ]
                );
            }
        }

        // 11. ATTENDANCE SESSIONS & RECORDS
        $session = AttendanceSession::updateOrCreate(
            [
                'academic_year_id' => $year2026->id,
                'term_id' => $term1->id,
                'stream_id' => $streamModels['G1_Red']->id,
                'date' => Carbon::today()->toDateString(),
                'session_type' => 'daily',
            ],
            [
                'recorded_by' => $userModels['teacher'][0]->id,
            ]
        );

        $g1Learners = Learner::whereIn('admission_number', ['KPS/2026/0001', 'KPS/2026/0003'])->get();
        foreach ($g1Learners as $l) {
            AttendanceRecord::updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'learner_id' => $l->id,
                ],
                [
                    'status' => 'present',
                    'remarks' => 'Punctual',
                ]
            );
        }

        // 12. SCHOOL EVENTS & ANNOUNCEMENTS
        SchoolEvent::updateOrCreate(
            ['title' => 'Term 1 Mid-Term Break'],
            [
                'description' => 'All learners proceed for mid-term rest. Classes resume on Tuesday.',
                'start_date' => Carbon::parse('2026-02-18 15:30:00'),
                'end_date' => Carbon::parse('2026-02-23 08:00:00'),
                'event_type' => 'term_date',
                'target_audience' => 'all',
                'created_by' => $userModels['super_admin'][0]->id,
            ]
        );

        SchoolEvent::updateOrCreate(
            ['title' => 'Annual Inter-House Sports Day'],
            [
                'description' => 'Athletics, field games, and parent races at the school main grounds.',
                'start_date' => Carbon::parse('2026-03-12 09:00:00'),
                'end_date' => Carbon::parse('2026-03-12 16:00:00'),
                'event_type' => 'sports',
                'target_audience' => 'all',
                'created_by' => $userModels['super_admin'][0]->id,
            ]
        );

        Announcement::updateOrCreate(
            ['title' => 'Term 1 2026 School Opening & Fee Guidelines'],
            [
                'content' => 'We welcome all new and continuing learners to the 2026 Academic Year. Please ensure all fee payments are made through our official M-Pesa Paybill 247247 or directly to the school bank account.',
                'target_role' => 'all',
                'is_published' => true,
                'published_at' => Carbon::parse('2026-01-04 08:00:00'),
                'created_by' => $userModels['headteacher'][0]->id,
            ]
        );
    }
}
