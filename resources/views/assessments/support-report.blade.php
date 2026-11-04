@extends('layouts.app')

@section('title', 'Learning Support & Intervention Report')

@section('content')
<div class="d-print-none d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-exclamation text-warning me-2"></i>Remedial & Support Interventions</h4>
        <p class="text-muted small mb-0">Learners requiring targeted academic assistance (Approaching Expectations or Below Expectations).</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('assessments.index', ['stream_id' => $selectedStreamId]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Assessment Entry
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Support List
        </button>
    </div>
</div>

{{-- Stream Filter --}}
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('assessments.support-report') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Select Class / Stream</label>
                <select name="stream_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($streams as $stream)
                    <option value="{{ $stream->id }}" {{ $selectedStreamId == $stream->id ? 'selected' : '' }}>
                        {{ $stream->grade->name ?? 'Grade' }} - {{ $stream->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7 d-flex align-items-end">
                <div class="small text-muted">
                    Showing support records for <strong>{{ $term->name ?? 'Current Term' }} ({{ $year->name ?? 'Current Year' }})</strong>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Summary Alert --}}
<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
    <i class="bi bi-info-circle-fill fs-4 me-3 text-warning"></i>
    <div>
        <strong>CBC Remedial Guidance:</strong> Learners rated <strong>Approaching Expectations (AE)</strong> or <strong>Below Expectations (BE)</strong> require differentiated instruction, individualized learning plans, and parent-teacher consultations before the end-of-term summative reports.
    </div>
</div>

{{-- Support List --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-clipboard2-pulse me-2 text-danger"></i>Learners Requiring Support
        </h6>
        <span class="badge bg-warning text-dark">{{ count($supportList) }} Learner(s) Identified</span>
    </div>
    <div class="card-body p-0">
        @if(empty($supportList))
        <div class="text-center py-5 text-muted">
            <i class="bi bi-emoji-smile display-4 text-success d-block mb-2"></i>
            <h6 class="fw-bold text-dark">No Learners Require Remedial Support!</h6>
            <p class="small text-muted mb-0">All assessed learners in this stream are currently Meeting or Exceeding Expectations.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th style="width: 130px;">Adm No</th>
                        <th style="width: 220px;">Learner Name</th>
                        <th>Learning Areas Needing Attention & Specific Rubric Observations</th>
                        <th style="width: 120px;" class="text-center d-print-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($supportList as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="font-monospace fw-bold">{{ $item['learner']->admission_number }}</td>
                        <td>
                            <a href="{{ route('learners.show', $item['learner']->id) }}" class="text-decoration-none text-dark fw-semibold">
                                {{ $item['learner']->full_name }}
                            </a>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-2 py-1">
                                @foreach($item['areas_needing_support'] as $area)
                                <div class="p-2 rounded bg-light border-start border-3 {{ $area['level'] == 'BE' ? 'border-danger' : 'border-warning' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold text-dark">{{ $area['learning_area'] }}</span>
                                        <span class="badge {{ $area['level'] == 'BE' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                            {{ $area['level'] }} ({{ $area['score'] }}%)
                                        </span>
                                    </div>
                                    @if($area['comments'])
                                    <div class="small text-muted mt-1 fst-italic">
                                        &ldquo;{{ $area['comments'] }}&rdquo;
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-center d-print-none">
                            <a href="{{ route('learners.show', $item['learner']->id) }}#cbe" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i> Profile
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
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
