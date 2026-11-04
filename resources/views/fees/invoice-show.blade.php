@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('fees.invoices') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Invoices
            </a>
            <div class="d-flex gap-2">
                @if(Auth::user()->hasRole(['super_admin', 'bursar']) && (float)$invoice->balance > 0)
                <button type="button" class="btn btn-outline-warning btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#discountModal">
                    <i class="bi bi-tag me-1"></i> Apply Waiver / Discount
                </button>
                <a href="{{ route('payments.create', ['learner_id' => $invoice->learner_id, 'invoice_id' => $invoice->id]) }}" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-cash-stack me-1"></i> Pay This Invoice
                </a>
                @endif
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Invoice
                </button>
            </div>
        </div>

        <div class="card p-4 shadow-sm bg-white border">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                <div>
                    <h3 class="fw-bold text-primary mb-1">{{ \App\Models\SchoolSetting::get('school_name', 'St. Jude Primary School') }}</h3>
                    <p class="text-muted small mb-0">{{ \App\Models\SchoolSetting::get('school_box') }}, {{ \App\Models\SchoolSetting::get('school_town') }}</p>
                    <p class="text-muted small mb-0"><i class="bi bi-telephone me-1"></i>{{ \App\Models\SchoolSetting::get('school_phone') }} | <i class="bi bi-envelope me-1"></i>{{ \App\Models\SchoolSetting::get('school_email') }}</p>
                </div>
                <div class="text-end">
                    <h4 class="fw-bold text-dark mb-0">FEE INVOICE</h4>
                    <span class="badge bg-light text-dark border fs-6 mt-1">{{ $invoice->invoice_number }}</span>
                    <span class="d-block text-muted small mt-2">Issued: {{ $invoice->issue_date->format('d M Y') }}</span>
                    <span class="d-block text-muted small">Due Date: {{ $invoice->due_date->format('d M Y') }}</span>
                </div>
            </div>

            <!-- Learner & Term Details -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <h6 class="text-muted small text-uppercase fw-bold mb-2">Billed To:</h6>
                    <h5 class="fw-bold text-dark mb-1">{{ $invoice->learner->full_name }}</h5>
                    <span class="d-block text-muted small"><strong>Admission No:</strong> {{ $invoice->learner->admission_number }}</span>
                    <span class="d-block text-muted small"><strong>Grade/Class:</strong> {{ $invoice->learner->currentEnrollment?->grade?->name ?? '-' }} ({{ $invoice->learner->currentEnrollment?->stream?->name ?? '-' }})</span>
                    @php $pg = $invoice->learner->primaryGuardian(); @endphp
                    @if($pg)
                        <span class="d-block text-muted small"><strong>Guardian:</strong> {{ $pg->full_name }} ({{ $pg->phone }})</span>
                    @endif
                </div>
                <div class="col-sm-6 text-sm-end">
                    <h6 class="text-muted small text-uppercase fw-bold mb-2">Billing Term:</h6>
                    <h5 class="fw-bold text-primary mb-1">{{ $invoice->term->name }}</h5>
                    <span class="d-block text-muted small">Academic Year: {{ $invoice->academicYear->name }}</span>
                    <span class="d-block text-muted small">Payment Status: 
                        <span class="badge {{ $invoice->status === 'paid' ? 'bg-success' : ($invoice->status === 'partial' ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ strtoupper($invoice->status) }}
                        </span>
                    </span>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th class="text-end">Amount (KSh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $idx => $item)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td class="fw-semibold">{{ $item->name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $item->feeCategory->name ?? '-' }}</span></td>
                                <td class="text-end fw-semibold">{{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Gross Total:</th>
                            <th class="text-end">KSh {{ number_format($invoice->total_amount, 2) }}</th>
                        </tr>
                        @if((float)$invoice->discount_amount > 0)
                        <tr class="text-success">
                            <th colspan="3" class="text-end">Discounts / Waivers:</th>
                            <th class="text-end">- KSh {{ number_format($invoice->discount_amount, 2) }}</th>
                        </tr>
                        @endif
                        <tr class="text-success">
                            <th colspan="3" class="text-end">Paid to Date:</th>
                            <th class="text-end">- KSh {{ number_format($invoice->paid_amount, 2) }}</th>
                        </tr>
                        <tr class="table-light fs-5">
                            <th colspan="3" class="text-end text-danger">Outstanding Balance Due:</th>
                            <th class="text-end text-danger fw-bold">KSh {{ number_format($invoice->balance, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payment instructions -->
            <div class="border rounded p-3 bg-light small mb-4">
                <h6 class="fw-bold mb-1"><i class="bi bi-wallet2 text-success me-1"></i>Official Payment Instructions:</h6>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <strong>M-Pesa Paybill:</strong> {{ \App\Models\SchoolSetting::get('mpesa_paybill', '247247') }}<br>
                        <strong>Account Name:</strong> {{ $invoice->learner->admission_number }}
                    </div>
                    <div class="col-sm-6">
                        <strong>Cheque / Bank Payee:</strong> {{ \App\Models\SchoolSetting::get('mpesa_account_name', 'ST JUDE PRIMARY SCHOOL') }}<br>
                        <em>Cash must be deposited directly at school bursar desk for instant receipt.</em>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Apply Discount -->
<div class="modal fade" id="discountModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('fees.invoices.discount', $invoice->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Authorize Fee Waiver / Discount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Discount Amount (KSh) *</label>
                        <input type="number" step="0.01" max="{{ $invoice->balance }}" name="amount" class="form-control" required placeholder="Maximum KSh {{ number_format($invoice->balance, 2) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason / Justification *</label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g. Sibling discount, Staff child waiver, Scholarship grant">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-semibold">Apply Discount</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

