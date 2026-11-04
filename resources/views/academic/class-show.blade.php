@extends('layouts.app')

@section('title', $stream->full_name . ' - Class Roster')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ $stream->full_name }} Class Roster</h4>
        <p class="text-muted small mb-0">Academic Year: {{ $currentYear->name }} | Capacity: {{ $stream->capacity }} | Current Enrollment: {{ $enrollments->count() }}</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignTeacherModal">
            <i class="bi bi-person-check-fill me-1"></i> Assign Class Teacher
        </button>
        <a href="{{ route('academic.classes') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Classes
        </a>
    </div>
</div>

<!-- Class Teacher Banner Card -->
<div class="card mb-4 bg-light">
    <div class="card-body p-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white p-2 rounded-circle me-3">
                <i class="bi bi-person-workspace fs-4"></i>
            </div>
            <div>
                <span class="text-muted small text-uppercase fw-semibold">Class Teacher:</span>
                <h6 class="fw-bold mb-0">{{ $classTeacher?->teacher?->name ?? 'None assigned yet' }}</h6>
                <small class="text-muted">{{ $classTeacher?->teacher?->email ?? '' }}</small>
            </div>
        </div>
        <span class="badge bg-primary px-3 py-2">Stream {{ $stream->name }}</span>
    </div>
</div>

<!-- Enrolled Learners Table -->
<div class="card">
    <div class="card-header bg-white fw-bold">Enrolled Learners ({{ $enrollments->count() }})</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Roll No.</th>
                    <th>Admission No.</th>
                    <th>Learner Full Name</th>
                    <th>Gender</th>
                    <th>Guardian Contact</th>
                    <th>Fee Balance</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $enr)
                    @php $l = $enr->learner; @endphp
                    <tr>
                        <td class="fw-bold">{{ $enr->roll_number ?? '-' }}</td>
                        <td>
                            <a href="{{ route('learners.show', $l->id) }}" class="fw-bold text-decoration-none">
                                {{ $l->admission_number }}
                            </a>
                        </td>
                        <td>{{ $l->full_name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst($l->gender) }}</span></td>
                        <td>
                            @php $pg = $l->primaryGuardian(); @endphp
                            @if($pg)
                                <span class="d-block small fw-semibold">{{ $pg->full_name }}</span>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $pg->phone }}</small>
                            @else
                                <span class="text-muted small">None</span>
                            @endif
                        </td>
                        <td>
                            @php $bal = (float)($l->feeAccount?->current_balance ?? 0); @endphp
                            @if($bal > 0)
                                <span class="badge bg-danger-subtle text-danger">KSh {{ number_format($bal, 2) }}</span>
                            @else
                                <span class="badge bg-success-subtle text-success">Cleared</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('learners.show', $l->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.8rem;">Profile</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No learners enrolled in this stream yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Teacher Modal -->
<div class="modal fade" id="assignTeacherModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('academic.class.assign-teacher', $stream->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Assign Class Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Teacher</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">-- Choose Teacher --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ $classTeacher?->teacher_id == $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ $t->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Save Assignment</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

