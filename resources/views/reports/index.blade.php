@extends('layouts.app')

@section('title', 'Learner Report Cards')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-text text-primary me-2"></i>CBC Report Cards Registry</h4>
        <p class="text-muted small mb-0">Compile, review, and export printable official Competency-Based Assessment report cards.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        @if(Auth::user()->hasRole(['super_admin', 'headteacher', 'teacher']))
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">
            <i class="bi bi-lightning-charge me-1"></i> Bulk Compile Class Reports
        </button>
        @endif
        <a href="{{ route('assessments.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil-square me-1"></i> Enter Assessments
        </a>
    </div>
</div>

{{-- Filters Card --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Academic Year</label>
                <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($years as $year)
                    <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Term</label>
                <select name="term_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($terms as $term)
                    <option value="{{ $term->id }}" {{ $selectedTermId == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Filter by Class / Stream</label>
                <select name="stream_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Streams --</option>
                    @foreach($streams as $stream)
                    <option value="{{ $stream->id }}" {{ $selectedStreamId == $stream->id ? 'selected' : '' }}>
                        {{ $stream->grade->name ?? 'Grade' }} - {{ $stream->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Report Cards Table --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-card-checklist me-2 text-primary"></i>Compiled Report Cards
        </h6>
        <span class="badge bg-secondary">{{ $reportCards->total() }} Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Adm No</th>
                        <th>Learner Name</th>
                        <th>Grade / Stream</th>
                        <th>Term / Year</th>
                        <th>Attendance</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportCards as $report)
                    <tr>
                        <td>{{ $loop->iteration + ($reportCards->currentPage() - 1) * $reportCards->perPage() }}</td>
                        <td class="font-monospace fw-bold">{{ $report->learner->admission_number }}</td>
                        <td class="fw-semibold">
                            <a href="{{ route('reports.show', $report->id) }}" class="text-decoration-none text-dark">
                                {{ $report->learner->full_name }}
                            </a>
                        </td>
                        <td>
                            {{ $report->stream->grade->name ?? '—' }} {{ $report->stream->name ?? '' }}
                        </td>
                        <td class="small">
                            {{ $report->term->name }} ({{ $report->academicYear->name }})
                        </td>
                        <td class="small">
                            {{ $report->attendance_days_present }}/{{ $report->attendance_days_total }} Days
                        </td>
                        <td>
                            <span class="badge bg-success text-uppercase" style="font-size: 0.7rem;">
                                {{ $report->status }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('reports.show', $report->id) }}" class="btn btn-outline-primary" title="View Web Report">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="{{ route('reports.pdf', ['learnerId' => $report->learner_id, 'year_id' => $report->academic_year_id, 'term_id' => $report->term_id]) }}" 
                                   target="_blank" 
                                   class="btn btn-outline-danger" 
                                   title="Download PDF">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-x display-4 d-block mb-2"></i>
                            <h6 class="fw-bold">No Report Cards Found for Selected Criteria</h6>
                            <p class="small text-muted mb-3">Use the bulk compile tool to compile report cards for all enrolled learners in a stream.</p>
                            @if(Auth::user()->hasRole(['super_admin', 'headteacher', 'teacher']))
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">
                                <i class="bi bi-lightning-charge me-1"></i> Bulk Compile Class Reports
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reportCards->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $reportCards->links() }}
    </div>
    @endif
</div>

{{-- Bulk Compile Modal --}}
@if(Auth::user()->hasRole(['super_admin', 'headteacher', 'teacher']))
<div class="modal fade" id="bulkGenerateModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('reports.bulk-generate') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-lightning-charge me-2"></i>Bulk Compile Report Cards</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        This action will generate or refresh CBC report cards and attendance aggregations for all active learners in the selected class stream.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-select" required>
                            @foreach($years as $year)
                            <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Term <span class="text-danger">*</span></label>
                        <select name="term_id" class="form-select" required>
                            @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ $selectedTermId == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Class Stream <span class="text-danger">*</span></label>
                        <select name="stream_id" class="form-select" required>
                            @foreach($streams as $stream)
                            <option value="{{ $stream->id }}" {{ $selectedStreamId == $stream->id ? 'selected' : '' }}>
                                {{ $stream->grade->name ?? 'Grade' }} - {{ $stream->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Start Compilation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
