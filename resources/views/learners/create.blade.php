@extends('layouts.app')

@section('title', 'New Learner Admission')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-1">New Learner Admission & Automated Fee Assignment</h4>
                <p class="text-muted small mb-0">Academic Year: {{ $currentYear?->name ?? '2026' }} | Term: {{ $currentTerm?->name ?? 'Term 1' }}</p>
            </div>
            <a href="{{ route('learners.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Directory
            </a>
        </div>

        <form action="{{ route('learners.store') }}" method="POST" id="admissionForm">
            @csrf

            <div class="row g-4">
                <!-- Left Column: Learner & Guardian Data -->
                <div class="col-lg-7">
                    <!-- Section 1: Bio Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-white">
                            <span class="fw-bold text-primary"><i class="bi bi-person-badge me-2"></i>1. Learner Personal Details</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Admission Number</label>
                                    <input type="text" name="admission_number" class="form-control form-control-sm bg-light" value="{{ old('admission_number', $suggestedAdmissionNumber) }}" required>
                                    <small class="text-muted">Auto-generated format: KPS/{YEAR}/{SEQ}</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Admission Date</label>
                                    <input type="date" name="admission_date" class="form-control form-control-sm" value="{{ old('admission_date', date('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">First Name *</label>
                                    <input type="text" name="first_name" class="form-control form-control-sm" value="{{ old('first_name') }}" required placeholder="e.g. Brian">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control form-control-sm" value="{{ old('middle_name') }}" placeholder="e.g. Kiprono">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control form-control-sm" value="{{ old('last_name') }}" required placeholder="e.g. Mwangi">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Gender *</label>
                                    <select name="gender" class="form-select form-select-sm" required>
                                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Date of Birth *</label>
                                    <input type="date" name="dob" class="form-control form-control-sm" value="{{ old('dob', '2019-01-01') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">NEMIS UPI (Optional)</label>
                                    <input type="text" name="nemis_number" class="form-control form-control-sm" value="{{ old('nemis_number') }}" placeholder="e.g. NEMIS-1234">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Previous School</label>
                                    <input type="text" name="previous_school" class="form-control form-control-sm" value="{{ old('previous_school') }}" placeholder="Previous ECDE or Primary School">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Opening Fee Balance (if transferring)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">KSh</span>
                                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', 0.00) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Class Allocation -->
                    <div class="card mb-3">
                        <div class="card-header bg-white">
                            <span class="fw-bold text-primary"><i class="bi bi-grid-3x3-gap me-2"></i>2. Class & Stream Allocation</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Select Grade *</label>
                                    <select id="gradeSelect" class="form-select form-select-sm" required>
                                        <option value="">-- Choose Grade --</option>
                                        @foreach($grades as $g)
                                            <option value="{{ $g->id }}" data-streams="{{ json_encode($g->streams) }}">{{ $g->name }} ({{ $g->stage }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Select Stream *</label>
                                    <select name="stream_id" id="streamSelect" class="form-select form-select-sm" required disabled>
                                        <option value="">-- Choose Grade First --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Parent & Emergency Contacts -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <span class="fw-bold text-primary"><i class="bi bi-telephone-fill me-2"></i>3. Primary Guardian & Emergency Contacts</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Guardian Full Name *</label>
                                    <input type="text" name="guardian_name" class="form-control form-control-sm" value="{{ old('guardian_name') }}" required placeholder="e.g. David Mwangi">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Guardian Phone *</label>
                                    <input type="text" name="guardian_phone" class="form-control form-control-sm" value="{{ old('guardian_phone') }}" required placeholder="e.g. 0712345678">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Relationship *</label>
                                    <select name="guardian_relationship" class="form-select form-select-sm" required>
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Legal Guardian">Legal Guardian</option>
                                        <option value="Sponsor">Sponsor</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">National ID No.</label>
                                    <input type="text" name="guardian_id_number" class="form-control form-control-sm" value="{{ old('guardian_id_number') }}" placeholder="e.g. 24567891">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Email Address</label>
                                    <input type="email" name="guardian_email" class="form-control form-control-sm" value="{{ old('guardian_email') }}" placeholder="guardian@gmail.com">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Residential / Home Address</label>
                                    <input type="text" name="guardian_address" class="form-control form-control-sm" value="{{ old('guardian_address') }}" placeholder="e.g. Estate, House No., Sub-county">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Automated Fee Breakdown Panel -->
                <div class="col-lg-5">
                    <div class="card shadow-sm sticky-top" style="top: 1rem;">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="bi bi-calculator me-2"></i>Automated Fee Assignment</span>
                            <span class="badge bg-light text-primary" id="scheduleBadge">Pending Grade</span>
                        </div>
                        <div class="card-body p-3">
                            <div id="feePlaceholder" class="text-center py-5 text-muted">
                                <i class="bi bi-arrow-left-circle fs-1 text-primary opacity-50 mb-2 d-block"></i>
                                <h6 class="fw-bold">Select a Grade on the Left</h6>
                                <p class="small mb-0">The system will dynamically retrieve and itemize active term fee charges.</p>
                            </div>

                            <div id="feeContent" style="display: none;">
                                <div class="alert alert-info py-2 small mb-3">
                                    <i class="bi bi-shield-check me-1"></i> Active fee schedule loaded. Amounts recalculated server-side upon saving.
                                </div>

                                <h6 class="fw-bold text-dark border-bottom pb-2 small text-uppercase">Mandatory Charges:</h6>
                                <ul class="list-group list-group-flush mb-3" id="mandatoryList"></ul>

                                <h6 class="fw-bold text-dark border-bottom pb-2 small text-uppercase">Optional Services / Transport:</h6>
                                <div id="optionalList" class="mb-3"></div>

                                <div class="border-top pt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted small">Mandatory Subtotal:</span>
                                        <span class="fw-semibold" id="mandatorySubtotal">KSh 0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">Optional Selected:</span>
                                        <span class="fw-semibold" id="optionalSubtotal">KSh 0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-2 border-top border-bottom bg-light px-2 rounded">
                                        <span class="fw-bold text-dark">Total Term 1 Fee:</span>
                                        <span class="fw-bold fs-5 text-primary" id="grandTotalText">KSh 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top p-3">
                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm" id="submitBtn" disabled>
                                <i class="bi bi-check-circle-fill me-2"></i>Complete Registration & Generate Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const gradeSelect = document.getElementById('gradeSelect');
    const streamSelect = document.getElementById('streamSelect');
    const feePlaceholder = document.getElementById('feePlaceholder');
    const feeContent = document.getElementById('feeContent');
    const mandatoryList = document.getElementById('mandatoryList');
    const optionalList = document.getElementById('optionalList');
    const mandatorySubtotal = document.getElementById('mandatorySubtotal');
    const optionalSubtotal = document.getElementById('optionalSubtotal');
    const grandTotalText = document.getElementById('grandTotalText');
    const scheduleBadge = document.getElementById('scheduleBadge');
    const submitBtn = document.getElementById('submitBtn');

    let currentFeeData = null;

    gradeSelect.addEventListener('change', function() {
        const gradeId = this.value;
        streamSelect.innerHTML = '<option value="">-- Choose Stream --</option>';
        streamSelect.disabled = true;

        if (!gradeId) {
            feePlaceholder.style.display = 'block';
            feeContent.style.display = 'none';
            scheduleBadge.textContent = 'Pending Grade';
            submitBtn.disabled = true;
            return;
        }

        // Populate streams dropdown
        const selectedOption = this.options[this.selectedIndex];
        const streams = JSON.parse(selectedOption.getAttribute('data-streams') || '[]');
        streams.forEach(str => {
            const opt = document.createElement('option');
            opt.value = str.id;
            opt.textContent = `${str.name} (Cap: ${str.capacity})`;
            streamSelect.appendChild(opt);
        });
        streamSelect.disabled = false;
        if (streams.length > 0) streamSelect.selectedIndex = 1;

        // Fetch dynamic fee schedule via AJAX
        fetchFees(gradeId);
    });

    function fetchFees(gradeId) {
        const optionalCategoryIds = [];
        document.querySelectorAll('input[name="optional_fee_category_ids[]"]:checked').forEach(cb => {
            optionalCategoryIds.push(cb.value);
        });

        fetch('{{ route("learners.calculate-fees") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                grade_id: gradeId,
                optional_fee_category_ids: optionalCategoryIds
            })
        })
        .then(res => res.json())
        .then(data => {
            currentFeeData = data;
            renderFeeSchedule(data);
            submitBtn.disabled = false;
        })
        .catch(err => {
            console.error('Fee fetch error:', err);
        });
    }

    function renderFeeSchedule(data) {
        feePlaceholder.style.display = 'none';
        feeContent.style.display = 'block';
        scheduleBadge.textContent = data.title || 'Standard Fee';

        mandatoryList.innerHTML = '';
        optionalList.innerHTML = '';

        data.items.forEach(item => {
            if (!item.is_optional) {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-0';
                li.innerHTML = `<span><i class="bi bi-dot text-primary"></i> ${item.name}</span>
                                <span class="fw-semibold">KSh ${item.amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>`;
                mandatoryList.appendChild(li);
            } else {
                const div = document.createElement('div');
                div.className = 'form-check py-1';
                div.innerHTML = `
                    <input class="form-check-input optional-cb" type="checkbox" name="optional_fee_category_ids[]" value="${item.fee_category_id}" id="opt_${item.id}" ${item.is_selected ? 'checked' : ''} onchange="updateTotals()">
                    <label class="form-check-label d-flex justify-content-between align-items-center" for="opt_${item.id}">
                        <span>${item.name}</span>
                        <span class="text-primary fw-semibold">+ KSh ${item.amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                    </label>
                `;
                optionalList.appendChild(div);
            }
        });

        mandatorySubtotal.textContent = `KSh ${data.mandatory_total.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        optionalSubtotal.textContent = `KSh ${data.optional_total.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        grandTotalText.textContent = `KSh ${data.grand_total.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    }

    function updateTotals() {
        const gradeId = gradeSelect.value;
        if (gradeId) {
            fetchFees(gradeId);
        }
    }
</script>
@endpush

