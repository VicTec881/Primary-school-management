<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('parent')) {
            return redirect()->route('parent.dashboard');
        }

        $years = AcademicYear::orderByDesc('name')->get();
        $selectedYearId = $request->input('academic_year_id', AcademicYear::current()?->id ?? $years->first()?->id);
        
        $terms = Term::where('academic_year_id', $selectedYearId)->orderBy('term_number')->get();
        $selectedTermId = $request->input('term_id', Term::current()?->id ?? $terms->first()?->id);

        $metrics = $this->dashboardService->getDashboardMetrics($selectedYearId, $selectedTermId);

        return view('dashboard.index', compact('metrics', 'years', 'terms', 'selectedYearId', 'selectedTermId'));
    }
}

