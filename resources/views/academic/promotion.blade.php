@extends('layouts.app')

@section('title', 'Learner Promotion')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">Annual Learner Promotion</h4>
    <p class="text-muted small mb-0">Preview destination grades, validate eligibility, and promote learners with full audit preservation.</p>
</div>

<div class="card mb-4">
    <div class="card-header bg-white fw-bold">1. Select Source & Destination Classes</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Current Academic Year</label>
                <select id="from_year_id" class="form-select form-select-sm">
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" {{ $y->is_current ? 'selected' : '' }}>Year {{ $y->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Current Stream</label>
                <select id="from_stream_id" class="form-select form-select-sm">
                    <option value="">-- Choose Current Stream --</option>
                    @foreach($streams as $st)
                        <option value="{{ $st->id }}">{{ $st->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold">Destination Academic Year</label>
                <select id="to_year_id" class="form-select form-select-sm">
                    @foreach($years as $y)
                        <option value="{{ $y->id }}">{{ $y->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Destination Stream</label>
                <select id="to_stream_id" class="form-select form-select-sm">
                    <option value="">-- Choose Target Stream --</option>
                    @foreach($streams as $st)
                        <option value="{{ $st->id }}">{{ $st->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 text-end">
                <button type="button" class="btn btn-primary btn-sm fw-semibold" id="previewBtn">
                    <i class="bi bi-search me-1"></i> Load Promotion Preview
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Table Card -->
<div class="card" id="previewCard" style="display: none;">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0" id="previewTitle">Promotion Preview</h5>
            <small class="text-muted" id="previewSub"></small>
        </div>
    </div>
    <form action="{{ route('academic.promotion.execute') }}" method="POST">
        @csrf
        <input type="hidden" name="from_year_id" id="hidden_from_year">
        <input type="hidden" name="to_year_id" id="hidden_to_year">
        <input type="hidden" name="to_stream_id" id="hidden_to_stream">

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll" checked></th>
                        <th>Admission No.</th>
                        <th>Learner Full Name</th>
                        <th>Current Class</th>
                        <th>Target Class</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="previewTbody"></tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small"><i class="bi bi-shield-check text-success me-1"></i>Historical grades, marks, and invoices are strictly preserved.</span>
            <button type="submit" class="btn btn-success fw-bold px-4" id="executeBtn" disabled>
                <i class="bi bi-check2-circle me-1"></i> Execute Promotion
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('previewBtn').addEventListener('click', function() {
        const fromYearId = document.getElementById('from_year_id').value;
        const fromStreamId = document.getElementById('from_stream_id').value;
        const toYearId = document.getElementById('to_year_id').value;
        const toStreamId = document.getElementById('to_stream_id').value;

        if (!fromStreamId || !toStreamId) {
            alert('Please select both current stream and destination stream.');
            return;
        }

        fetch('{{ route("academic.promotion.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                from_year_id: fromYearId,
                from_stream_id: fromStreamId,
                to_year_id: toYearId,
                to_stream_id: toStreamId
            })
        })
        .then(res => res.json())
        .then(data => {
            renderPreview(data, fromYearId, toYearId, toStreamId);
        });
    });

    function renderPreview(data, fromYearId, toYearId, toStreamId) {
        document.getElementById('hidden_from_year').value = fromYearId;
        document.getElementById('hidden_to_year').value = toYearId;
        document.getElementById('hidden_to_stream').value = toStreamId;

        document.getElementById('previewTitle').textContent = `Promoting from ${data.from_stream} to ${data.to_stream}`;
        document.getElementById('previewSub').textContent = `Target Year: ${data.to_year} | Eligible Learners: ${data.learners.filter(l => l.eligible).length}`;

        const tbody = document.getElementById('previewTbody');
        tbody.innerHTML = '';

        data.learners.forEach(l => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="checkbox" name="learner_ids[]" value="${l.learner_id}" ${l.eligible ? 'checked' : 'disabled'} class="learner-cb">
                </td>
                <td class="fw-bold">${l.admission_number}</td>
                <td>${l.learner_name}</td>
                <td>${l.current_grade} (${l.current_stream})</td>
                <td><span class="text-primary fw-semibold">${l.destination_grade} (${l.destination_stream})</span></td>
                <td>
                    ${l.eligible
                        ? '<span class="badge bg-success-subtle text-success">Ready for Promotion</span>'
                        : '<span class="badge bg-secondary-subtle text-secondary">' + l.reason + '</span>'}
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('previewCard').style.display = 'block';
        document.getElementById('executeBtn').disabled = data.learners.filter(l => l.eligible).length === 0;
    }

    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.learner-cb:not(:disabled)').forEach(cb => {
            cb.checked = this.checked;
        });
    });
</script>
@endpush

