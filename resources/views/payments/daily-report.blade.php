@extends('layouts.app')

@section('title', 'Daily Collection Report')

@section('content')
<div class="d-print-none d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-check text-success me-2"></i>Daily Collection Report</h4>
        <p class="text-muted small mb-0">Audit and summarize fee collections for {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Payments
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
    </div>
</div>

{{-- Date filter card --}}
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('payments.daily-report') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-semibold">Select Collection Date:</label>
            </div>
            <div class="col-auto">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-filter me-1"></i> View Report
                </button>
                <a href="{{ route('payments.daily-report', ['date' => date('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary ms-1">Today</a>
                <a href="{{ route('payments.daily-report', ['date' => date('Y-m-d', strtotime('-1 day'))]) }}" class="btn btn-sm btn-outline-secondary ms-1">Yesterday</a>
            </div>
        </form>
    </div>
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-primary text-white">
            <div class="card-body p-3">
                <span class="small text-uppercase opacity-75">Total Amount Collected</span>
                <h3 class="fw-bold mb-0">KSh {{ number_format($report['total'], 2) }}</h3>
                <small class="opacity-75">{{ $report['count'] }} payment transaction(s)</small>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <span class="small text-muted text-uppercase fw-semibold d-block mb-2">Collections by Payment Method</span>
                <div class="row g-2 text-center">
                    @php
                        $methods = ['mpesa' => 'M-Pesa', 'cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque'];
                    @endphp
                    @foreach($methods as $key => $label)
                    <div class="col-3">
                        <div class="p-2 border rounded bg-light">
                            <span class="small text-muted d-block">{{ $label }}</span>
                            <span class="fw-bold text-dark">KSh {{ number_format($report['by_method'][$key] ?? 0, 2) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Printable collection table --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-list-check me-2"></i>Itemized Transaction Log — {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
        </h6>
        <span class="badge bg-secondary">{{ $report['count'] }} Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Receipt No</th>
                        <th>Time</th>
                        <th>Learner Name</th>
                        <th>Adm No</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Cashier / Officer</th>
                        <th class="text-end">Amount (KSh)</th>
                        <th class="text-center d-print-none">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['payments'] as $p)
                    <tr>
                        <td class="fw-bold font-monospace text-primary">{{ $p->receipt_number }}</td>
                        <td>{{ $p->created_at->format('H:i') }}</td>
                        <td class="fw-semibold">
                            <a href="{{ route('learners.show', $p->learner_id) }}" class="text-decoration-none text-dark">
                                {{ $p->learner->full_name }}
                            </a>
                        </td>
                        <td class="font-monospace small">{{ $p->learner->admission_number }}</td>
                        <td>
                            <span class="badge bg-light text-dark border text-uppercase">
                                {{ str_replace('_', ' ', $p->payment_method) }}
                            </span>
                        </td>
                        <td class="small font-monospace">{{ $p->transaction_reference ?: '—' }}</td>
                        <td class="small text-muted">{{ $p->recordedByUser->name ?? 'System' }}</td>
                        <td class="text-end fw-bold text-success font-monospace">
                            {{ number_format($p->amount, 2) }}
                        </td>
                        <td class="text-center d-print-none">
                            <a href="{{ route('payments.receipt', $p->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2" title="View Receipt">
                                <i class="bi bi-receipt"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox display-6 d-block mb-2"></i>
                            No payments recorded on {{ \Carbon\Carbon::parse($date)->format('d F Y') }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($report['count'] > 0)
                <tfoot class="table-light">
                    <tr>
                        <th colspan="7" class="text-end text-uppercase">Daily Total Collection:</th>
                        <th class="text-end text-primary fw-bold fs-6 font-monospace">KSh {{ number_format($report['total'], 2) }}</th>
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

