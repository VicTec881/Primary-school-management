<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Learner;
use App\Models\Announcement;
use App\Models\SchoolEvent;
use Illuminate\Support\Facades\Auth;

class ParentPortalController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return view('parent.no-guardian');
        }

        $children = $guardian->learners()->with([
            'currentEnrollment.grade',
            'currentEnrollment.stream',
            'feeAccount',
            'reportCards' => fn($q) => $q->latest()->take(1),
        ])->get();

        $announcements = Announcement::where('is_published', true)
            ->whereIn('target_role', ['all', 'parent'])
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        $events = SchoolEvent::where('start_date', '>=', now())
            ->whereIn('target_audience', ['all', 'parents'])
            ->orderBy('start_date')
            ->take(5)
            ->get();

        return view('parent.dashboard', compact('guardian', 'children', 'announcements', 'events'));
    }

    public function childStatement(int $learnerId)
    {
        $user = Auth::user();
        $guardian = $user->guardian;

        $child = $guardian?->learners()->where('learners.id', $learnerId)->first();
        if (!$child) {
            abort(403, 'Unauthorized access to learner records.');
        }

        $learner = Learner::with([
            'feeAccount',
            'invoices.items',
            'invoices.term',
            'payments.allocations',
            'guardians'
        ])->findOrFail($learnerId);

        return view('fees.statement', compact('learner'));
    }

    public function childReports(int $learnerId)
    {
        $user = Auth::user();
        $guardian = $user->guardian;

        $child = $guardian?->learners()->where('learners.id', $learnerId)->first();
        if (!$child) {
            abort(403, 'Unauthorized access to learner records.');
        }

        $reportCards = $child->reportCards()->with(['term', 'academicYear', 'stream.grade'])->orderByDesc('id')->get();

        return view('parent.reports', compact('child', 'reportCards'));
    }
}

