@extends('layouts.app')

@section('title', 'Report Card - ' . $data['learner']->full_name)

@section('content')
<div class="d-print-none d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-person text-primary me-2"></i>Learner Report Card</h4>
        <p class="text-muted small mb-0">{{ $data['learner']->full_name }} &bull; {{ $data['term']->name }} {{ $data['year']->name }}</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Reports
        </a>
        <a href="{{ route('reports.pdf', ['learnerId' => $data['learner']->id, 'year_id' => $data['year']->id, 'term_id' => $data['term']->id]) }}" 
           target="_blank" 
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download Official PDF
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<div class="card shadow-sm border-0" id="reportCardContainer" style="max-width: 900px; margin: 0 auto; background: #fff;">
    <div class="card-body p-4 p-md-5">
        {{-- Header / School Letterhead --}}
        <div class="text-center border-bottom pb-4 mb-4">
            <h2 class="fw-bold text-uppercase mb-1" style="color: #1b365d; letter-spacing: 1px;">
                {{ $data['school']['name'] }}
            </h2>
            <p class="text-muted fst-italic mb-1 small">{{ $data['school']['motto'] }}</p>
            <div class="small text-secondary mb-2">
                {{ $data['school']['box'] }}, {{ $data['school']['town'] }} | Tel: {{ $data['school']['phone'] }} | Email: {{ $data['school']['email'] }}
            </div>
            <div class="mt-2">
                <span class="badge bg-primary px-3 py-2 text-uppercase fs-6">
                    Competency-Based Assessment (CBE) Summative Report
                </span>
            </div>
        </div>

        {{-- Learner Details & Attendance --}}
        <div class="row g-3 mb-4 bg-light p-3 rounded">
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 140px;">Learner Name:</td>
                        <td class="fw-bold">{{ $data['learner']->full_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Admission No:</td>
                        <td class="fw-semibold font-monospace">{{ $data['learner']->admission_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">NEMIS / UPI No:</td>
                        <td class="font-monospace">{{ $data['learner']->nemis_number ?: 'N/A' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 140px;">Grade & Stream:</td>
                        <td class="fw-bold">{{ $data['grade']->name ?? 'Grade' }} {{ $data['stream']->name ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Academic Term:</td>
                        <td class="fw-semibold">{{ $data['term']->name }} ({{ $data['year']->name }})</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Term Attendance:</td>
                        <td>
                            <strong>{{ $data['report_card']->attendance_days_present }}</strong> / {{ $data['report_card']->attendance_days_total }} Days Present
                            <span class="text-muted small">({{ $data['report_card']->attendance_days_absent }} Absent)</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- CBE Performance Key --}}
        <div class="mb-4 p-2 border rounded bg-white small">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold text-uppercase" style="font-size: 0.75rem;">Grading Performance Levels:</span>
                <span class="badge bg-success">EE &bull; Exceeding Expectations (80-100%)</span>
                <span class="badge bg-primary">ME &bull; Meeting Expectations (65-79%)</span>
                <span class="badge bg-warning text-dark">AE &bull; Approaching Expectations (50-64%)</span>
                <span class="badge bg-danger">BE &bull; Below Expectations (0-49%)</span>
            </div>
        </div>

        {{-- Learning Area Assessments Table --}}
        <div class="mb-4">
            <h6 class="fw-bold text-secondary text-uppercase small mb-2">Learning Areas & Competencies Assessment</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Learning Area (Subject)</th>
                            <th style="width: 90px;" class="text-center">Score (%)</th>
                            <th style="width: 140px;" class="text-center">Performance Level</th>
                            <th>Teacher Rubric Comments & Recommendations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['assessments'] as $index => $record)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $record->learningArea->name }}</strong>
                                <span class="badge bg-light text-muted border ms-1">{{ $record->learningArea->code }}</span>
                            </td>
                            <td class="text-center font-monospace fw-bold">
                                {{ $record->raw_score !== null ? $record->raw_score : '—' }}
                            </td>
                            <td class="text-center">
                                @php
                                    $lvl = $record->performance_level;
                                    $badge = match($lvl) {
                                        'EE' => 'bg-success',
                                        'ME' => 'bg-primary',
                                        'AE' => 'bg-warning text-dark',
                                        'BE' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                @if($lvl)
                                <span class="badge {{ $badge }} px-2 py-1 fs-7 fw-bold">{{ $lvl }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                {{ $record->rubric_comments ?: 'Demonstrating consistent engagement in classroom activities.' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No assessment records uploaded for this term yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Remarks Section --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="p-3 border rounded h-100 bg-light">
                    <h6 class="fw-bold text-dark mb-2 small text-uppercase">Class Teacher's Remarks</h6>
                    <p class="fst-italic small mb-3 text-secondary">
                        &ldquo;{{ $data['report_card']->class_teacher_remarks ?: 'Good academic engagement and steady progress across all learning areas.' }}&rdquo;
                    </p>
                    <div class="border-top pt-2 small text-muted">
                        <strong>Signature:</strong> __________________________
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded h-100 bg-light">
                    <h6 class="fw-bold text-dark mb-2 small text-uppercase">Headteacher's Remarks</h6>
                    <p class="fst-italic small mb-3 text-secondary">
                        &ldquo;{{ $data['report_card']->headteacher_remarks ?: 'Promising results. Encouraged to maintain consistency.' }}&rdquo;
                    </p>
                    <div class="border-top pt-2 small text-muted">
                        <strong>Signature & Stamp:</strong> __________________________
                    </div>
                </div>
            </div>
        </div>

        {{-- Next Term Dates --}}
        <div class="alert alert-info py-2 px-3 small d-flex justify-content-between align-items-center mb-4">
            <span><strong>Next Term Commences:</strong> {{ $data['report_card']->next_term_begins ? \Carbon\Carbon::parse($data['report_card']->next_term_begins)->format('l, d F Y') : 'To be communicated' }}</span>
            <span><strong>Date Issued:</strong> {{ \Carbon\Carbon::parse($data['report_card']->issued_at ?? now())->format('d/m/Y') }}</span>
        </div>

        <div class="text-center pt-2 border-top text-muted" style="font-size: 0.72rem;">
            Official report card generated by {{ $data['school']['name'] }} SMS. Any unauthorized alteration renders this document invalid.
        </div>
    </div>
</div>

<style>
@media print {
    body { background: #fff !important; }
    #sidebar, .navbar, .d-print-none, .btn { display: none !important; }
    .wrapper { display: block !important; }
    #content { margin: 0 !important; padding: 0 !important; }
    #reportCardContainer { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; }
}
</style>
@endsection

