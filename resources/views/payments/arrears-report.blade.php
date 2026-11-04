@extends('layouts.app')

@section('title', 'Fee Arrears & Defaulters Report')

@section('content')
<div class="d-print-none d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-bar-graph text-danger me-2"></i>Fee Arrears & Defaulters</h4>
        <p class="text-muted small mb-0">Identify learners with outstanding fee balances for prompt recovery and communication.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Payments
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Arrears List
        </button>
    </div>
</div>

{{-- Filters Card --}}
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('payments.arrears-report') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Filter by Grade</label>
                <select name="grade_id" class="form-select form-select-sm" id="gradeSelect" onchange="this.form.submit()">
                    <option value="">-- All Grades --</option>
                    @foreach($grades as $g)
                    <option value="{{ $g->id }}" {{ $gradeId == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Filter by Stream</label>
                <select name="stream_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Streams --</option>
                    @foreach($grades as $g)
                        <optgroup label="{{ $g->name }}">
                            @foreach($g->streams as $s)
                            <option value="{{ $s->id }}" {{ $streamId == $s->id ? 'selected' : '' }}>{{ $g->name }} - {{ $s->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <a href="{{ route('payments.arrears-report') }}" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="bi bi-x-circle me-1"></i> Clear Filter
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 border-start border-danger border-4">
            <div class="card-body p-3">
                <span class="small text-muted text-uppercase fw-semibold">Total Outstanding Arrears</span>
                <h3 class="fw-bold text-danger mb-0">KSh {{ number_format($report['total_arrears'], 2) }}</h3>
                <small class="text-muted">Accumulated unpaid balance</small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0 border-start border-warning border-4">
            <div class="card-body p-3">
                <span class="small text-muted text-uppercase fw-semibold">Learners in Arrears</span>
                <h3 class="fw-bold text-dark mb-0">{{ $report['learner_count'] }} <span class="fs-6 fw-normal text-muted">learners</span></h3>
                <small class="text-muted">Learners currently with balance > 0</small>
            </div>
        </div>
    </div>
</div>

{{-- Arrears Table --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-people-fill me-2 text-danger"></i>Learner Balances Breakdown
        </h6>
        <span class="badge bg-danger">{{ $report['learner_count'] }} in Arrears</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>#</th>
                        <th>Admission No</th>
                        <th>Learner Name</th>
                        <th>Class / Stream</th>
                        <th class="text-end">Total Billed</th>
                        <th class="text-end">Total Paid</th>
                        <th class="text-end">Balance Due (KSh)</th>
                        <th class="text-center d-print-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['learners'] as $index => $learner)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="font-monospace fw-semibold">{{ $learner->admission_number }}</td>
                        <td class="fw-semibold">
                            <a href="{{ route('learners.show', $learner->id) }}" class="text-decoration-none text-dark">
                                {{ $learner->full_name }}
                            </a>
                        </td>
                        <td>
                            {{ $learner->currentEnrollment->grade->name ?? '—' }} 
                            {{ $learner->currentEnrollment->stream->name ?? '' }}
                        </td>
                        <td class="text-end small">KSh {{ number_format($learner->feeAccount->total_invoiced ?? 0, 2) }}</td>
                        <td class="text-end small text-success">KSh {{ number_format($learner->feeAccount->total_paid ?? 0, 2) }}</td>
                        <td class="text-end fw-bold text-danger font-monospace fs-6">
                            KSh {{ number_format($learner->feeAccount->current_balance ?? 0, 2) }}
                        </td>
                        <td class="text-center d-print-none">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('fees.statement', $learner->id) }}" class="btn btn-outline-secondary" title="Fee Statement">
                                    <i class="bi bi-file-text"></i>
                                </a>
                                @if(Auth::user()->hasRole(['super_admin', 'bursar']))
                                <a href="{{ route('payments.create', ['learner_id' => $learner->id]) }}" class="btn btn-outline-success" title="Record Payment">
                                    <i class="bi bi-cash"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle-fill display-5 text-success d-block mb-2"></i>
                            <h6 class="fw-bold">No Outstanding Arrears Found!</h6>
                            <p class="small text-muted mb-0">All learners in the selected scope are fully paid up.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($report['learner_count'] > 0)
                <tfoot class="table-light">
                    <tr>
                        <th colspan="6" class="text-end text-uppercase">Total Arrears:</th>
                        <th class="text-end text-danger fw-bold fs-5 font-monospace">KSh {{ number_format($report['total_arrears'], 2) }}</th>
                        <th class="d-print-none"></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    body { background: #fff !important; }
    #sidebar, .navbar, .d-print-none, .btn { display: none !important; }
    .wrapper { display: block !important; }
    #content { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
@endsection
