<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Learner;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Grade;
use App\Services\FeeService;
use App\Services\MpesaService;

class ApiController extends Controller
{
    public function __construct(
        protected FeeService $feeService,
        protected MpesaService $mpesaService
    ) {}

    public function status()
    {
        $year = AcademicYear::current();
        $term = Term::current();

        return response()->json([
            'status' => 'operational',
            'system' => 'Kenyan Primary School SMS API',
            'version' => 'v1.0',
            'academic_year' => $year?->name,
            'current_term' => $term?->name,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function learners(Request $request)
    {
        $query = Learner::with(['currentEnrollment.grade', 'currentEnrollment.stream', 'feeAccount']);

        if ($search = $request->input('search')) {
            $query->where('admission_number', 'LIKE', "%{$search}%")
                  ->orWhere('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%");
        }

        $learners = $query->paginate(20);

        return response()->json($learners);
    }

    public function feeEstimate(Request $request)
    {
        $gradeId = (int) $request->input('grade_id');
        $optionalCategoryIds = (array) $request->input('optional_fee_category_ids', []);

        $estimate = $this->feeService->calculateItemizedFee($gradeId, $optionalCategoryIds);

        return response()->json($estimate);
    }

    public function mpesaCallback(Request $request)
    {
        $result = $this->mpesaService->processCallback($request->all());
        return response()->json($result);
    }
}

