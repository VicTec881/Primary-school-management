@extends('layouts.app')

@section('title', 'Payments & Receipts')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Payments & Receipt Registry</h4>
        <p class="text-muted small mb-0">Record manual school fee payments, print official receipts, and audit collections.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('payments.daily-report') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-calendar-event me-1"></i> Daily Collection
        </a>
        <a href="{{ route('payments.arrears-report') }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-bar-graph me-1"></i> Arrears Report
        </a>
        @if(Auth::user()->hasRole(['super_admin', 'bursar']))
        <a href="{{ route('payments.create') }}" class="btn btn-success btn-sm fw-semibold">
            <i class="bi bi-plus-lg me-1"></i> Record Payment
        </a>
        @endif
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('payments.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search receipt no, reference, learner name..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">-- All Payment Methods --</option>
                    <option value="mpesa" {{ request('payment_method') == 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cheque" {{ request('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="reversed" {{ request('status') == 'reversed' ? 'selected' : '' }}>Reversed</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                <a href="{{ route('payments.index') }}" class="btn btn-light btn-sm border">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Payment Date</th>
                    <th>Learner</th>
                    <th>Amount (KSh)</th>
                    <th>Method</th>
                    <th>Transaction Ref</th>
                    <th>Recorded By</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td class="fw-bold text-primary">
                            <a href="{{ route('payments.receipt', $p->id) }}" class="text-decoration-none">
                                {{ $p->receipt_number }}
                            </a>
                        </td>
                        <td>{{ $p->payment_date->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('learners.show', $p->learner_id) }}" class="fw-semibold text-dark text-decoration-none">
                                {{ $p->learner->full_name }}
                            </a>
                            <small class="text-muted d-block">{{ $p->learner->admission_number }}</small>
                        </td>
                        <td class="fw-bold text-success fs-6">KSh {{ number_format($p->amount, 2) }}</td>
                        <td>
                            <span class="badge {{ $p->payment_method === 'mpesa' ? 'bg-success' : 'bg-secondary' }}">
                                {{ strtoupper($p->payment_method) }}
                            </span>
                        </td>
                        <td><code>{{ $p->transaction_reference ?? '-' }}</code></td>
                        <td><small class="text-muted">{{ $p->recordedByUser->name ?? 'System' }}</small></td>
                        <td>
                            <span class="badge {{ $p->status === 'completed' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($p->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('payments.receipt', $p->id) }}" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-printer me-1"></i>Receipt
                            </a>
                            @if(Auth::user()->hasRole(['super_admin', 'bursar']) && $p->status === 'completed')
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reverseModal_{{ $p->id }}">
                                    Reverse
                                </button>

                                <!-- Reversal Modal -->
                                <div class="modal fade text-start" id="reverseModal_{{ $p->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('payments.reverse', $p->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold text-danger">Authorize Payment Reversal</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-muted mb-3">
                                                        You are about to reverse Receipt <strong>{{ $p->receipt_number }}</strong> of <strong>KSh {{ number_format($p->amount, 2) }}</strong> for <strong>{{ $p->learner->full_name }}</strong>.
                                                        This will restore the invoice balance and append a reversal entry to the financial audit ledger.
                                                    </p>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Mandatory Reversal Reason *</label>
                                                        <input type="text" name="reason" class="form-control" required placeholder="e.g. Wrong learner credited, bounced cheque, bank recall">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger btn-sm fw-semibold">Confirm Reversal</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No payments recorded matching criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="card-footer bg-white border-top py-2">
        {{ $payments->links() }}
    </div>
    @endif
</div>
@endsection

