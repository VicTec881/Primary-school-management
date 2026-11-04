@extends('layouts.app')

@section('title', 'Fee Statement - ' . $learner->full_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('learners.show', $learner->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Profile
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Statement
            </button>
        </div>

        <div class="card p-4 shadow-sm bg-white border">
            <!-- Header -->
            <div class="text-center border-bottom pb-3 mb-4">
                <h3 class="fw-bold text-primary mb-1">{{ \App\Models\SchoolSetting::get('school_name', 'St. Jude Primary & Junior School') }}</h3>
                <p class="text-muted small mb-0">{{ \App\Models\SchoolSetting::get('school_motto') }}</p>
                <p class="text-muted small mb-0">{{ \App\Models\SchoolSetting::get('school_box') }}, {{ \App\Models\SchoolSetting::get('school_town') }} | Phone: {{ \App\Models\SchoolSetting::get('school_phone') }}</p>
                <h5 class="fw-bold text-dark mt-3 text-uppercase border-top border-bottom py-2 d-inline-block px-4">OFFICIAL LEARNER FEE STATEMENT</h5>
            </div>

            <!-- Student Summary -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th class="text-muted small" width="130">Learner Name:</th>
                            <td class="fw-bold">{{ $learner->full_name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted small">Admission No:</th>
                            <td class="fw-bold text-primary">{{ $learner->admission_number }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted small">Current Class:</th>
                            <td>{{ $learner->currentEnrollment?->grade?->name ?? '-' }} ({{ $learner->currentEnrollment?->stream?->name ?? '-' }})</td>
                        </tr>
                    </table>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th class="text-muted small">Date of Issue:</th>
                            <td>{{ date('d F Y, H:i') }} EAT</td>
                        </tr>
                        <tr>
                            <th class="text-muted small">Primary Guardian:</th>
                            <td>{{ $learner->primaryGuardian()?->full_name ?? 'None' }} ({{ $learner->primaryGuardian()?->phone ?? '-' }})</td>
                        </tr>
                        <tr>
                            <th class="text-muted small">Account Status:</th>
                            <td>
                                @if((float)($learner->feeAccount?->current_balance ?? 0) <= 0)
                                    <span class="badge bg-success">Fees Cleared</span>
                                @else
                                    <span class="badge bg-danger">Has Outstanding Balance</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Invoices Table -->
            <h6 class="fw-bold text-dark border-bottom pb-2 small text-uppercase">1. Term Fee Invoices (Debits)</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice No.</th>
                            <th>Term / Year</th>
                            <th>Issue Date</th>
                            <th>Gross Billed</th>
                            <th>Discounts</th>
                            <th>Paid Amount</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($learner->invoices as $inv)
                            <tr>
                                <td class="fw-semibold">{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->term->name ?? '-' }} ({{ $inv->academicYear->name ?? '-' }})</td>
                                <td>{{ $inv->issue_date->format('d M Y') }}</td>
                                <td>KSh {{ number_format($inv->total_amount, 2) }}</td>
                                <td>KSh {{ number_format($inv->discount_amount, 2) }}</td>
                                <td class="text-success fw-semibold">KSh {{ number_format($inv->paid_amount, 2) }}</td>
                                <td class="text-end fw-bold {{ (float)$inv->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    KSh {{ number_format($inv->balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-2">No invoices.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Payments Table -->
            <h6 class="fw-bold text-dark border-bottom pb-2 small text-uppercase">2. Recorded Payments & Credits (Receipts)</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt No.</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Transaction Ref</th>
                            <th>Bank / Channel</th>
                            <th>Status</th>
                            <th class="text-end">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($learner->payments as $p)
                            <tr>
                                <td class="fw-bold text-primary">{{ $p->receipt_number }}</td>
                                <td>{{ $p->payment_date->format('d M Y') }}</td>
                                <td>{{ strtoupper($p->payment_method) }}</td>
                                <td><code>{{ $p->transaction_reference ?? '-' }}</code></td>
                                <td>{{ $p->bank_name ?? 'School Cashier' }}</td>
                                <td>
                                    <span class="badge {{ $p->status === 'completed' ? 'bg-success' : 'bg-danger' }}">
                                        {{ ucfirst($p->status) }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-success">KSh {{ number_format($p->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-2">No payments recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Overall Account Balance Box -->
            <div class="row justify-content-end mb-4">
                <div class="col-sm-6">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <tr>
                                <th>Opening Balance:</th>
                                <td class="text-end fw-semibold">KSh {{ number_format($learner->feeAccount?->opening_balance ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Total Term Invoices:</th>
                                <td class="text-end fw-semibold">KSh {{ number_format($learner->feeAccount?->total_billed ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Total Fee Discounts:</th>
                                <td class="text-end text-success fw-semibold">- KSh {{ number_format($learner->feeAccount?->total_discounted ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Total Verified Payments:</th>
                                <td class="text-end text-success fw-semibold">- KSh {{ number_format($learner->feeAccount?->total_paid ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-light fs-5">
                                <th class="text-danger">Current Outstanding Balance:</th>
                                <th class="text-end text-danger fw-bold">KSh {{ number_format($learner->feeAccount?->current_balance ?? 0, 2) }}</th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Signature & Stamp Box -->
            <div class="row pt-5 mt-4 border-top">
                <div class="col-6">
                    <p class="small text-muted mb-4">Issued By: <strong>{{ Auth::user()->name }}</strong> (Accounts Officer)</p>
                    <div style="border-bottom: 1px dotted #333; width: 200px;"></div>
                    <small class="text-muted">Signature & Date</small>
                </div>
                <div class="col-6 text-end">
                    <div class="d-inline-block border p-3 rounded text-center" style="width: 140px; height: 90px; border-style: dashed !important;">
                        <span class="text-muted small" style="font-size: 0.75rem;">Official School Stamp</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

