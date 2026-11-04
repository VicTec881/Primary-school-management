@extends('layouts.app')

@section('title', 'Payment Receipt ' . $payment->receipt_number)

@section('content')
<div class="d-print-none d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Official Fee Receipt</h4>
        <p class="text-muted small mb-0">Receipt No: <span class="fw-bold text-dark">{{ $payment->receipt_number }}</span></p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> All Payments
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Receipt
        </button>
        @if(Auth::user()->hasRole(['super_admin', 'bursar']) && $payment->status !== 'reversed')
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reversePaymentModal">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse Payment
        </button>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0 position-relative" id="printableReceipt" style="max-width: 820px; margin: 0 auto; background: #fff;">
    @if($payment->status === 'reversed')
    <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center" style="top: 0; left: 0; pointer-events: none; opacity: 0.15; z-index: 10;">
        <span style="font-size: 7rem; font-weight: 900; color: #dc3545; transform: rotate(-25deg); border: 8px solid #dc3545; padding: 10px 40px; border-radius: 12px;">REVERSED</span>
    </div>
    @endif

    <div class="card-body p-4 p-md-5">
        {{-- Header / Letterhead --}}
        <div class="text-center border-bottom pb-3 mb-4">
            <h3 class="fw-bold text-uppercase mb-1" style="color: #1b365d; letter-spacing: 1px;">
                {{ \App\Models\SchoolSetting::get('school_name', 'St. Jude Primary & Junior School') }}
            </h3>
            <p class="text-muted fst-italic mb-1 small">{{ \App\Models\SchoolSetting::get('school_motto', 'Excellence in Knowledge and Character') }}</p>
            <div class="small text-secondary">
                {{ \App\Models\SchoolSetting::get('school_box', 'P.O. Box 45120 - 00100') }}, {{ \App\Models\SchoolSetting::get('school_town', 'Nairobi, Kenya') }} | 
                Tel: {{ \App\Models\SchoolSetting::get('school_phone', '+254 711 234 567') }} | 
                Email: {{ \App\Models\SchoolSetting::get('school_email', 'info@stjudeprimary.sc.ke') }}
            </div>
            <div class="mt-2">
                <span class="badge bg-primary px-3 py-2 text-uppercase fs-6">Official Fee Payment Receipt</span>
            </div>
        </div>

        {{-- Receipt Meta Info --}}
        <div class="row g-3 mb-4">
            <div class="col-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 130px;">Receipt No:</td>
                        <td class="fw-bold font-monospace text-primary">{{ $payment->receipt_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Date Issued:</td>
                        <td class="fw-semibold">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Payment Method:</td>
                        <td class="fw-semibold text-uppercase">
                            {{ str_replace('_', ' ', $payment->payment_method) }}
                            @if($payment->transaction_reference)
                                <span class="text-muted fw-normal">({{ $payment->transaction_reference }})</span>
                            @endif
                        </td>
                    </tr>
                    @if($payment->bank_name)
                    <tr>
                        <td class="text-muted ps-0">Bank:</td>
                        <td>{{ $payment->bank_name }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            <div class="col-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 130px;">Learner Name:</td>
                        <td class="fw-bold">{{ $payment->learner->full_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Admission No:</td>
                        <td class="fw-semibold font-monospace">{{ $payment->learner->admission_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Class / Stream:</td>
                        <td>{{ $payment->learner->currentEnrollment->grade->name ?? 'N/A' }} {{ $payment->learner->currentEnrollment->stream->name ?? '' }}</td>
                    </tr>
                    @if($payment->learner->guardians->isNotEmpty())
                    <tr>
                        <td class="text-muted ps-0">Parent / Guardian:</td>
                        <td>{{ $payment->learner->guardians->first()->full_name }} ({{ $payment->learner->guardians->first()->phone }})</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Allocations Table --}}
        <div class="mb-4">
            <h6 class="fw-bold text-secondary text-uppercase small mb-2">Payment Allocation Breakdown</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr class="small text-uppercase">
                            <th>#</th>
                            <th>Description / Term Invoice</th>
                            <th class="text-end">Invoice Amount</th>
                            <th class="text-end">Amount Allocated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payment->allocations as $index => $allocation)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $allocation->invoice->term->name ?? 'Term Invoice' }}</strong> 
                                <span class="text-muted small">({{ $allocation->invoice->academicYear->name ?? '' }})</span>
                                <div class="small text-muted font-monospace">Invoice #{{ $allocation->invoice->invoice_number }}</div>
                            </td>
                            <td class="text-end">KSh {{ number_format($allocation->invoice->total_amount, 2) }}</td>
                            <td class="text-end fw-bold text-success">KSh {{ number_format($allocation->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td>1</td>
                            <td>Advance / Unallocated Fee Credit</td>
                            <td class="text-end">—</td>
                            <td class="text-end fw-bold text-success">KSh {{ number_format($payment->amount, 2) }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end text-uppercase">Total Amount Paid:</th>
                            <th class="text-end fs-5 text-primary fw-bold">KSh {{ number_format($payment->amount, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Account Balance Summary --}}
        @php
            $feeAccount = $payment->learner->feeAccount;
        @endphp
        @if($feeAccount)
        <div class="row g-3 mb-4">
            <div class="col-md-6 offset-md-6">
                <div class="bg-light p-3 rounded border">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Total Cumulative Billed:</span>
                        <span class="fw-semibold">KSh {{ number_format($feeAccount->total_invoiced, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Total Cumulative Paid:</span>
                        <span class="text-success fw-semibold">KSh {{ number_format($feeAccount->total_paid, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <span class="fw-bold">Current Outstanding Balance:</span>
                        <span class="fw-bold {{ $feeAccount->balance > 0 ? 'text-danger' : 'text-success' }}">
                            KSh {{ number_format($feeAccount->balance, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($payment->notes)
        <div class="mb-4 p-2 bg-light rounded small">
            <strong>Notes:</strong> {{ $payment->notes }}
        </div>
        @endif

        {{-- Signatures & Stamp --}}
        <div class="row pt-4 mt-4 border-top text-center text-muted small">
            <div class="col-4">
                <div style="height: 50px;"></div>
                <div class="border-top pt-2">
                    <strong>Served By</strong><br>
                    <span>{{ $payment->recordedByUser->name ?? 'Accounts Officer' }}</span>
                </div>
            </div>
            <div class="col-4">
                <div class="d-flex align-items-center justify-content-center" style="height: 50px;">
                    <span class="badge border border-secondary text-secondary p-2 text-uppercase" style="font-size: 0.75rem;">Official School Stamp</span>
                </div>
                <div class="border-top pt-2">
                    <strong>Accounts Office</strong><br>
                    <span>Verification</span>
                </div>
            </div>
            <div class="col-4">
                <div style="height: 50px;"></div>
                <div class="border-top pt-2">
                    <strong>Payer's Signature</strong><br>
                    <span>Parent / Guardian</span>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 pt-2 border-top text-muted" style="font-size: 0.72rem;">
            This is an official computer-generated receipt from {{ \App\Models\SchoolSetting::get('school_name', 'St. Jude Primary School') }}. Any alteration invalidates this receipt.
        </div>
    </div>
</div>

{{-- Reversal Modal --}}
@if(Auth::user()->hasRole(['super_admin', 'bursar']) && $payment->status !== 'reversed')
<div class="modal fade" id="reversePaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('payments.reverse', $payment->id) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reverse Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger fw-semibold">Warning: This action will reverse Receipt {{ $payment->receipt_number }} for KSh {{ number_format($payment->amount, 2) }}.</p>
                    <p class="small text-muted">The learner's account balance will be reinstated and all invoice allocations will be undone. This operation is recorded in the immutable financial ledger and audit log.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Reversal <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="State exact reason (e.g. wrong student credited, bounced cheque, bank duplicate)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm & Reverse Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<style>
@media print {
    body {
        background: #fff !important;
    }
    #sidebar, .navbar, .d-print-none, .btn {
        display: none !important;
    }
    .wrapper {
        display: block !important;
    }
    #content {
        margin: 0 !important;
        padding: 0 !important;
    }
    #printableReceipt {
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
    }
}
</style>
@endsection

