@extends('layouts.app')

@section('title', 'Daily Attendance Register')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-check text-primary me-2"></i>Daily Attendance Register</h4>
        <p class="text-muted small mb-0">Record and track learner classroom attendance, absences, and tardiness.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <button type="button" class="btn btn-outline-success btn-sm" onclick="markAll('present')">
            <i class="bi bi-check-all me-1"></i> Mark All Present
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="markAll('absent')">
            <i class="bi bi-x-circle me-1"></i> Mark All Absent
        </button>
    </div>
</div>

{{-- Absence Alerts --}}
@if(!empty($alerts))
<div class="alert alert-danger border-0 shadow-sm mb-4">
    <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
        <div>
            <h6 class="fw-bold mb-1 text-danger">Frequent Absence Warning (> 3 absences this term)</h6>
            <div class="d-flex flex-wrap gap-2 mt-2">
                @foreach($alerts as $alert)
                <span class="badge bg-white text-danger border border-danger p-2">
                    <strong>{{ $alert['learner']->full_name }}</strong> 
                    ({{ $alert['absence_count'] }} days absent)
                    @if($alert['primary_guardian'])
                    &bull; Parent: {{ $alert['primary_guardian']->phone }}
                    @endif
                </span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

{{-- Stream & Date Filter Card --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('attendance.index') }}" class="row g-2 align-items-end" id="filterForm">
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Select Class / Stream</label>
                <select name="stream_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                    @foreach($streams as $stream)
                    <option value="{{ $stream->id }}" {{ $selectedStreamId == $stream->id ? 'selected' : '' }}>
                        {{ $stream->grade->name ?? 'Grade' }} - {{ $stream->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Attendance Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" max="{{ date('Y-m-d') }}" onchange="document.getElementById('filterForm').submit()">
            </div>
            <div class="col-md-3">
                <div class="small text-muted mb-1">Status on {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}:</div>
                @if($session)
                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                    <i class="bi bi-check-circle me-1"></i> Register Submitted
                </span>
                @else
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">
                    <i class="bi bi-clock me-1"></i> Pending Submission
                </span>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Attendance Register Table Form --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-person-lines-fill me-2 text-primary"></i>Learner Roll Call — {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
            </h6>
            <small class="text-muted">{{ $enrollments->count() }} learner(s) registered in this stream</small>
        </div>
    </div>
    <div class="card-body p-0">
        @if($enrollments->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people display-4 d-block mb-2"></i>
            <h6>No learners enrolled in this stream.</h6>
        </div>
        @else
        <form method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <input type="hidden" name="stream_id" value="{{ $selectedStreamId }}">
            <input type="hidden" name="date" value="{{ $date }}">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 120px;">Adm No</th>
                            <th>Learner Name</th>
                            <th style="width: 320px;" class="text-center">Attendance Status</th>
                            <th>Remarks / Reason for Absence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $index => $enrollment)
                        @php
                            $learner = $enrollment->learner;
                            $rec = $records->get($learner->id);
                            $currentStatus = old("records.{$index}.status", $rec ? $rec->status : 'present');
                            $remarks = old("records.{$index}.remarks", $rec ? $rec->remarks : '');
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="font-monospace small fw-bold">{{ $learner->admission_number }}</td>
                            <td class="fw-semibold">
                                <input type="hidden" name="records[{{ $index }}][learner_id]" value="{{ $learner->id }}">
                                {{ $learner->full_name }}
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <div class="form-check form-check-inline m-0">
                                        <input class="btn-check status-radio status-present" type="radio" 
                                               name="records[{{ $index }}][status]" 
                                               id="pres_{{ $index }}" 
                                               value="present" 
                                               {{ $currentStatus === 'present' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-success btn-sm py-1 px-2" for="pres_{{ $index }}">Present</label>
                                    </div>
                                    <div class="form-check form-check-inline m-0">
                                        <input class="btn-check status-radio status-absent" type="radio" 
                                               name="records[{{ $index }}][status]" 
                                               id="abs_{{ $index }}" 
                                               value="absent" 
                                               {{ $currentStatus === 'absent' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-danger btn-sm py-1 px-2" for="abs_{{ $index }}">Absent</label>
                                    </div>
                                    <div class="form-check form-check-inline m-0">
                                        <input class="btn-check status-radio status-late" type="radio" 
                                               name="records[{{ $index }}][status]" 
                                               id="late_{{ $index }}" 
                                               value="late" 
                                               {{ $currentStatus === 'late' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-warning btn-sm py-1 px-2" for="late_{{ $index }}">Late</label>
                                    </div>
                                    <div class="form-check form-check-inline m-0">
                                        <input class="btn-check status-radio status-excused" type="radio" 
                                               name="records[{{ $index }}][status]" 
                                               id="exc_{{ $index }}" 
                                               value="excused" 
                                               {{ $currentStatus === 'excused' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary btn-sm py-1 px-2" for="exc_{{ $index }}">Excused</label>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="records[{{ $index }}][remarks]" 
                                       value="{{ $remarks }}" 
                                       class="form-control form-control-sm" 
                                       placeholder="Optional (e.g. medical excuse, flu)...">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">
                    <i class="bi bi-info-circle me-1"></i> Register updates are recorded with an audit timestamp and teacher identity.
                </span>
                <button type="submit" class="btn btn-primary fw-semibold px-4">
                    <i class="bi bi-cloud-arrow-up me-2"></i> Save Daily Register
                </button>
            </div>
        </form>
        @endif
    </div>
</div>

<script>
function markAll(status) {
    const radios = document.querySelectorAll('.status-' + status);
    radios.forEach(r => r.checked = true);
}
</script>
@endsection

