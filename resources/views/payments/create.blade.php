@extends('layouts.app')

@section('title', 'Record Payment')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cash-coin text-success me-2"></i>Record Fee Payment</h4>
        <p class="text-muted small mb-0">Enter payment details below. The amount will be automatically allocated to outstanding invoices.</p>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">
        <i class="bi bi-arrow-left me-1"></i> Back to Payments
    </a>
</div>

@if($errors->has('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    {{-- PAYMENT FORM --}}
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Payment Details</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('payments.store') }}" id="paymentForm">
                    @csrf

                    {{-- Learner Search --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Learner <span class="text-danger">*</span></label>
                        @if($learner)
                            <div class="alert alert-info py-2 px-3 d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <strong>{{ $learner->full_name }}</strong>
                                    <span class="badge bg-secondary ms-2">{{ $learner->admission_number }}</span>
                                    @if($learner->currentEnrollment)
                                        <span class="text-muted ms-2">— {{ $learner->currentEnrollment->grade->name ?? '' }} {{ $learner->currentEnrollment->stream->name ?? '' }}</span>
                                    @endif
                                </div>
                                <a href="{{ route('payments.create') }}" class="btn btn-sm btn-outline-secondary">Change</a>
                            </div>
                            <input type="hidden" name="learner_id" value="{{ $learner->id }}">
                        @else
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="learnerSearch" class="form-control" placeholder="Type name or admission number to search...">
                            </div>
                            <input type="hidden" name="learner_id" id="learnerId" value="{{ old('learner_id') }}" required>
                            <div id="learnerResults" class="list-group mt-1 shadow" style="position:absolute;z-index:9999;width:calc(100% - 2rem);display:none;"></div>
                            <div id="selectedLearner" class="mt-2 @if(!old('learner_id')) d-none @endif">
                                <div class="alert alert-info py-2 px-3 d-flex align-items-center justify-content-between mb-0">
                                    <span id="selectedLearnerName" class="fw-semibold"></span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearLearner()">Change</button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Outstanding invoices (populated via AJAX or pre-loaded) --}}
                    @if($learner && $learner->invoices->count())
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Apply to Specific Invoice (Optional)</label>
                        <select name="invoice_id" class="form-select">
                            <option value="">Auto-allocate (FIFO — recommended)</option>
                            @foreach($learner->invoices as $invoice)
                            <option value="{{ $invoice->id }}">
                                {{ $invoice->term->name ?? 'Invoice' }} #{{ $invoice->id }}
                                — KSh {{ number_format($invoice->balance, 2) }} outstanding
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Leave blank to automatically allocate payment to the oldest outstanding invoice first.</div>
                    </div>
                    @else
                        <input type="hidden" name="invoice_id" value="">
                    @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (KSh) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">KSh</span>
                                <input type="number" name="amount" id="amountInput" class="form-control @error('amount') is-invalid @enderror"
                                    value="{{ old('amount') }}" step="0.01" min="1" required placeholder="0.00">
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                                value="{{ old('payment_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="paymentMethod" class="form-select @error('payment_method') is-invalid @enderror" required onchange="toggleMethodFields()">
                            <option value="">-- Select Method --</option>
                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="mpesa" {{ old('payment_method') == 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                            <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer (EFT)</option>
                            <option value="cheque" {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                        </select>
                        @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- M-Pesa STK Push Panel --}}
                    @if($learner)
                    <div id="mpesaStkPanel" class="mb-3 p-3 bg-light rounded border d-none">
                        <p class="mb-2 fw-semibold text-success"><i class="bi bi-phone me-1"></i>Send M-Pesa STK Push to Parent</p>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-7">
                                <label class="form-label small">Parent Phone (07XXXXXXXX)</label>
                                <input type="text" id="stkPhone" class="form-control form-control-sm" placeholder="0712345678">
                            </div>
                            <div class="col-md-5">
                                <button type="button" class="btn btn-success btn-sm w-100" onclick="sendStkPush({{ $learner->id }})">
                                    <i class="bi bi-send me-1"></i>Send STK Prompt
                                </button>
                            </div>
                        </div>
                        <div id="stkResult" class="mt-2 small"></div>
                    </div>
                    @endif

                    {{-- Mpesa / Bank fields --}}
                    <div id="referenceGroup" class="mb-3 {{ old('payment_method') ? '' : 'd-none' }}">
                        <label class="form-label fw-semibold" id="referenceLabel">Transaction Reference</label>
                        <input type="text" name="transaction_reference" class="form-control @error('transaction_reference') is-invalid @enderror"
                            value="{{ old('transaction_reference') }}" id="referenceInput" placeholder="e.g. QHJ7XXXXX for M-Pesa">
                        @error('transaction_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div id="bankNameGroup" class="mb-3 d-none">
                        <label class="form-label fw-semibold">Bank / Branch Name</label>
                        <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                            value="{{ old('bank_name') }}" placeholder="e.g. Equity Bank — Westlands">
                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes about this payment...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Receipt Number</label>
                        <input type="text" class="form-control bg-light text-muted" value="{{ $suggestedReceipt }}" readonly>
                        <div class="form-text">Auto-generated on save.</div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg fw-semibold">
                            <i class="bi bi-check-circle me-2"></i>Record Payment & Generate Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SIDE PANEL: Outstanding balance --}}
    <div class="col-lg-5">
        @if($learner)
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold"><i class="bi bi-person-circle me-2 text-primary"></i>Learner Summary</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted">Full Name</td><td class="fw-semibold">{{ $learner->full_name }}</td></tr>
                    <tr><td class="text-muted">Admission No.</td><td>{{ $learner->admission_number }}</td></tr>
                    @if($learner->currentEnrollment)
                    <tr><td class="text-muted">Class</td><td>{{ $learner->currentEnrollment->grade->name ?? '—' }} {{ $learner->currentEnrollment->stream->name ?? '' }}</td></tr>
                    @endif
                    @if($learner->feeAccount)
                    <tr><td class="text-muted">Total Billed</td><td class="fw-semibold">KSh {{ number_format($learner->feeAccount->total_invoiced, 2) }}</td></tr>
                    <tr><td class="text-muted">Total Paid</td><td class="text-success fw-semibold">KSh {{ number_format($learner->feeAccount->total_paid, 2) }}</td></tr>
                    <tr>
                        <td class="text-muted">Balance Due</td>
                        <td class="{{ $learner->feeAccount->balance > 0 ? 'text-danger' : 'text-success' }} fw-bold">
                            KSh {{ number_format($learner->feeAccount->balance, 2) }}
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        @if($learner->invoices->count())
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2 text-warning"></i>Outstanding Invoices</h6>
            </div>
            <div class="list-group list-group-flush">
                @foreach($learner->invoices as $invoice)
                <div class="list-group-item px-3 py-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold small">{{ $invoice->term->name ?? "Invoice #{$invoice->id}" }}</span>
                        <span class="badge bg-{{ $invoice->status == 'partial' ? 'warning' : 'danger' }} text-dark">
                            KSh {{ number_format($invoice->balance, 2) }}
                        </span>
                    </div>
                    <div class="text-muted" style="font-size:0.75rem">
                        Total: KSh {{ number_format($invoice->total_amount, 2) }}
                        &bull; Paid: KSh {{ number_format($invoice->paid_amount, 2) }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @else
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-person-x display-4 mb-3 d-block"></i>
                <p>Search and select a learner to view their outstanding balance and invoices.</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleMethodFields() {
    const method = document.getElementById('paymentMethod').value;
    const refGroup = document.getElementById('referenceGroup');
    const bankGroup = document.getElementById('bankNameGroup');
    const mpesaPanel = document.getElementById('mpesaStkPanel');
    const refLabel = document.getElementById('referenceLabel');
    const refInput = document.getElementById('referenceInput');

    if (['mpesa','bank_transfer','cheque'].includes(method)) {
        refGroup.classList.remove('d-none');
    } else {
        refGroup.classList.add('d-none');
    }

    if (method === 'bank_transfer') {
        bankGroup.classList.remove('d-none');
    } else {
        bankGroup.classList.add('d-none');
    }

    if (method === 'mpesa') {
        refLabel.textContent = 'M-Pesa Transaction Code';
        refInput.placeholder = 'e.g. QHJ73LXXXX';
        if (mpesaPanel) mpesaPanel.classList.remove('d-none');
    } else {
        refLabel.textContent = 'Transaction / Cheque Reference';
        refInput.placeholder = 'Reference number';
        if (mpesaPanel) mpesaPanel.classList.add('d-none');
    }
}

// Learner search autocomplete
let searchTimeout;
const searchInput = document.getElementById('learnerSearch');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) {
            document.getElementById('learnerResults').style.display = 'none';
            return;
        }
        searchTimeout = setTimeout(() => {
            fetch(`/api/v1/learners?search=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(r => r.json())
            .then(data => {
                const results = document.getElementById('learnerResults');
                results.innerHTML = '';
                (data.data || []).slice(0, 8).forEach(learner => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action py-2';
                    item.innerHTML = `<strong>${learner.full_name}</strong> <span class="badge bg-secondary">${learner.admission_number}</span> <small class="text-muted ms-1">${learner.current_class || ''}</small>`;
                    item.addEventListener('click', () => selectLearner(learner));
                    results.appendChild(item);
                });
                results.style.display = data.data?.length ? 'block' : 'none';
            });
        }, 300);
    });
}

function selectLearner(learner) {
    document.getElementById('learnerId').value = learner.id;
    document.getElementById('selectedLearnerName').textContent = `${learner.full_name} (${learner.admission_number})`;
    document.getElementById('selectedLearner').classList.remove('d-none');
    document.getElementById('learnerResults').style.display = 'none';
    document.getElementById('learnerSearch').value = '';
}

function clearLearner() {
    document.getElementById('learnerId').value = '';
    document.getElementById('selectedLearner').classList.add('d-none');
}

function sendStkPush(learnerId) {
    const phone = document.getElementById('stkPhone').value.trim();
    const amount = document.getElementById('amountInput').value.trim();
    const result = document.getElementById('stkResult');

    if (!phone || !amount) {
        result.innerHTML = '<span class="text-danger">Please enter phone number and amount first.</span>';
        return;
    }

    result.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Sending STK push...</span>';

    fetch('/api/v1/mpesa/stk-push', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ learner_id: learnerId, phone_number: phone, amount: parseFloat(amount) })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            result.innerHTML = `<span class="text-success"><i class="bi bi-check-circle me-1"></i>${data.message}</span>`;
        } else {
            result.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle me-1"></i>${data.message || 'STK push failed.'}</span>`;
        }
    })
    .catch(() => {
        result.innerHTML = '<span class="text-danger">Network error. Please try again.</span>';
    });
}

// Run on load to restore old() selection
toggleMethodFields();
</script>
@endpush

