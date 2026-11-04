@extends('layouts.app')

@section('title', 'Fee Invoices')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Fee Invoices & Billing</h4>
        <p class="text-muted small mb-0">Monitor learner term invoices, payments applied, and outstanding arrears.</p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('fees.invoices') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search invoice no, learner name, admission no..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="term_id" class="form-select form-select-sm">
                    <option value="">-- All Terms --</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}" {{ request('term_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                <a href="{{ route('fees.invoices') }}" class="btn btn-light btn-sm border">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Invoice No.</th>
                    <th>Learner</th>
                    <th>Term / Year</th>
                    <th>Total Billed</th>
                    <th>Discounts</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td class="fw-bold text-primary">
                            <a href="{{ route('fees.invoices.show', $inv->id) }}" class="text-decoration-none">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('learners.show', $inv->learner_id) }}" class="fw-semibold text-dark text-decoration-none">
                                {{ $inv->learner->full_name }}
                            </a>
                            <small class="text-muted d-block">{{ $inv->learner->admission_number }}</small>
                        </td>
                        <td>{{ $inv->term->name ?? '-' }} ({{ $inv->academicYear->name ?? '-' }})</td>
                        <td class="fw-semibold">KSh {{ number_format($inv->total_amount, 2) }}</td>
                        <td>KSh {{ number_format($inv->discount_amount, 2) }}</td>
                        <td class="text-success fw-semibold">KSh {{ number_format($inv->paid_amount, 2) }}</td>
                        <td class="text-danger fw-bold">KSh {{ number_format($inv->balance, 2) }}</td>
                        <td>
                            <span class="badge {{ $inv->status === 'paid' ? 'bg-success' : ($inv->status === 'partial' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('fees.invoices.show', $inv->id) }}" class="btn btn-sm btn-outline-primary">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No invoices found matching criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer bg-white border-top py-2">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection

