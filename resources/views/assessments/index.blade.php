@extends('layouts.app')

@section('title', 'CBE Assessments & Mark Entry')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>CBC Assessment Mark Entry</h4>
        <p class="text-muted small mb-0">Record learner scores and rubric observations aligned to the Competency-Based Education framework.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('assessments.support-report', ['stream_id' => $selectedStreamId]) }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-person-exclamation me-1"></i> Support Intervention Report
        </a>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i> Report Cards
        </a>
    </div>
</div>

{{-- CBC Scale Legend --}}
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small">
            <span class="fw-bold text-secondary text-uppercase" style="font-size: 0.75rem;">CBC 4-Level Scale:</span>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-success">EE &bull; Exceeding Expectations (80-100%)</span>
                <span class="badge bg-primary">ME &bull; Meeting Expectations (65-79%)</span>
                <span class="badge bg-warning text-dark">AE &bull; Approaching Expectations (50-64%)</span>
                <span class="badge bg-danger">BE &bull; Below Expectations (0-49%)</span>
            </div>
        </div>
    </div>
</div>

{{-- Filter Selection Panel --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('assessments.index') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Academic Year</label>
                    <select name="academic_year_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                        @foreach($years as $year)
                        <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Term</label>
                    <select name="term_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                        @foreach($terms as $term)
                        <option value="{{ $term->id }}" {{ $selectedTermId == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Class / Stream</label>
                    <select name="stream_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                        @foreach($streams as $stream)
                        <option value="{{ $stream->id }}" {{ $selectedStreamId == $stream->id ? 'selected' : '' }}>
                            {{ $stream->grade->name ?? 'Grade' }} - {{ $stream->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Learning Area (Subject)</label>
                    <select name="learning_area_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                        @foreach($learningAreas as $area)
                        <option value="{{ $area->id }}" {{ $selectedAreaId == $area->id ? 'selected' : '' }}>
                            {{ $area->name }} ({{ $area->code }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Assessment Type</label>
                    <select name="assessment_type_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                        @foreach($assessmentTypes as $type)
                        <option value="{{ $type->id }}" {{ $selectedTypeId == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Bulk Mark Entry Sheet --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-table me-2 text-primary"></i>Assessment Roster
            </h6>
            <small class="text-muted">
                {{ $enrollments->count() }} learner(s) enrolled in this stream
            </small>
        </div>
        <div>
            <span class="badge bg-light text-dark border">
                Auto-calculates Performance Level on blur
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        @if($enrollments->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people display-4 d-block mb-2"></i>
            <h6>No active enrollments found for this stream and academic year.</h6>
            <p class="small text-muted mb-0">Ensure learners have been admitted and enrolled in this class.</p>
        </div>
        @else
        <form method="POST" action="{{ route('assessments.store') }}">
            @csrf
            <input type="hidden" name="stream_id" value="{{ $selectedStreamId }}">
            <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">
            <input type="hidden" name="learning_area_id" value="{{ $selectedAreaId }}">
            <input type="hidden" name="assessment_type_id" value="{{ $selectedTypeId }}">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 120px;">Adm No</th>
                            <th>Learner Name</th>
                            <th style="width: 130px;">Score</th>
                            <th style="width: 90px;">Out Of</th>
                            <th style="width: 140px;" class="text-center">CBC Level</th>
                            <th>Teacher Observations & Rubric Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $index => $enrollment)
                        @php
                            $learner = $enrollment->learner;
                            $existing = $existingRecords->get($learner->id);
                            $rawScore = old("marks.{$index}.raw_score", $existing?->raw_score);
                            $maxScore = old("marks.{$index}.max_score", $existing?->max_score ?? 100);
                            $level = $existing?->performance_level;
                            $comments = old("marks.{$index}.rubric_comments", $existing?->rubric_comments);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="font-monospace small fw-bold">{{ $learner->admission_number }}</td>
                            <td>
                                <input type="hidden" name="marks[{{ $index }}][learner_id]" value="{{ $learner->id }}">
                                <span class="fw-semibold">{{ $learner->full_name }}</span>
                            </td>
                            <td>
                                <input type="number" 
                                       step="0.5" 
                                       min="0" 
                                       max="100" 
                                       name="marks[{{ $index }}][raw_score]" 
                                       id="score_{{ $index }}" 
                                       value="{{ $rawScore }}" 
                                       class="form-control form-control-sm text-center fw-bold" 
                                       placeholder="Score"
                                       oninput="updateCbcBadge({{ $index }})">
                            </td>
                            <td>
                                <input type="number" 
                                       step="1" 
                                       min="1" 
                                       name="marks[{{ $index }}][max_score]" 
                                       id="max_{{ $index }}" 
                                       value="{{ $maxScore }}" 
                                       class="form-control form-control-sm text-center bg-light" 
                                       oninput="updateCbcBadge({{ $index }})">
                            </td>
                            <td class="text-center" id="badge_col_{{ $index }}">
                                @if($level)
                                    @php
                                        $badgeClass = match($level) {
                                            'EE' => 'bg-success',
                                            'ME' => 'bg-primary',
                                            'AE' => 'bg-warning text-dark',
                                            'BE' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} px-2 py-1 fs-7 fw-bold">{{ $level }}</span>
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1">—</span>
                                @endif
                            </td>
                            <td>
                                <input type="text" 
                                       name="marks[{{ $index }}][rubric_comments]" 
                                       value="{{ $comments }}" 
                                       class="form-control form-control-sm" 
                                       placeholder="e.g. Demonstrates strong analytical thinking...">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">
                    <i class="bi bi-info-circle me-1"></i> Blank scores are skipped and will not overwrite previous records.
                </span>
                <button type="submit" class="btn btn-primary fw-semibold px-4">
                    <i class="bi bi-check2-circle me-2"></i> Save All Assessments
                </button>
            </div>
        </form>
        @endif
    </div>
</div>

<script>
function updateCbcBadge(index) {
    const scoreInput = document.getElementById('score_' + index);
    const maxInput = document.getElementById('max_' + index);
    const badgeCol = document.getElementById('badge_col_' + index);
    
    const score = parseFloat(scoreInput.value);
    const max = parseFloat(maxInput.value) || 100;

    if (isNaN(score) || scoreInput.value.trim() === '') {
        badgeCol.innerHTML = '<span class="badge bg-light text-muted border px-2 py-1">—</span>';
        return;
    }

    const pct = (score / max) * 100;
    let code = 'BE';
    let badgeClass = 'bg-danger';

    if (pct >= 80) {
        code = 'EE';
        badgeClass = 'bg-success';
    } else if (pct >= 65) {
        code = 'ME';
        badgeClass = 'bg-primary';
    } else if (pct >= 50) {
        code = 'AE';
        badgeClass = 'bg-warning text-dark';
    }

    badgeCol.innerHTML = `<span class="badge ${badgeClass} px-2 py-1 fs-7 fw-bold">${code} (${Math.round(pct)}%)</span>`;
}
</script>
@endsection
