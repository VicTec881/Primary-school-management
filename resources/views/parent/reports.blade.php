@extends('layouts.app')

@section('title', 'Report Cards - ' . $child->full_name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-person text-primary me-2"></i>Academic Report Cards</h4>
        <p class="text-muted small mb-0">Learner: <strong class="text-dark">{{ $child->full_name }}</strong> ({{ $child->admission_number }})</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('parent.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Portal
        </a>
        <a href="{{ route('parent.child.statement', $child->id) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-receipt me-1"></i> Fee Statement
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-journal-text me-2 text-primary"></i>Term Summative Report History
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Academic Year</th>
                        <th>Term</th>
                        <th>Class / Stream</th>
                        <th>Attendance</th>
                        <th>Status</th>
                        <th>Issued Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportCards as $report)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-bold">{{ $report->academicYear->name }}</td>
                        <td>{{ $report->term->name }}</td>
                        <td>{{ $report->stream->grade->name ?? '' }} {{ $report->stream->name ?? '' }}</td>
                        <td class="small">{{ $report->attendance_days_present }} / {{ $report->attendance_days_total }} Days Present</td>
                        <td>
                            <span class="badge bg-success text-uppercase" style="font-size: 0.7rem;">
                                {{ $report->status }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $report->issued_at ? \Carbon\Carbon::parse($report->issued_at)->format('d M Y') : '—' }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('reports.show', $report->id) }}" class="btn btn-outline-primary" title="View Report">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="{{ route('reports.pdf', ['learnerId' => $child->id, 'year_id' => $report->academic_year_id, 'term_id' => $report->term_id]) }}" 
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
                            <h6 class="fw-bold">No Report Cards Published Yet</h6>
                            <p class="small text-muted mb-0">Report cards will appear here once term assessments are finalized and published by the school.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
