@extends('layouts.app')

@section('title', 'Learning Areas & Teacher Allocation')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">CBC Learning Areas & Teacher Allocation</h4>
        <p class="text-muted small mb-0">Kenyan Competency-Based Education curriculum subjects and teacher assignments.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#assignSubjectModal">
        <i class="bi bi-plus-lg me-1"></i> Assign Subject Teacher
    </button>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">CBC Learning Areas (Curriculum)</div>
            <div class="list-group list-group-flush">
                @foreach($learningAreas as $la)
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="fw-bold text-dark d-block">{{ $la->name }}</span>
                            <small class="text-muted">Target Stage: {{ $la->stage }}</small>
                        </div>
                        <span class="badge bg-primary-subtle text-primary fw-bold">{{ $la->code }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">Class & Subject Teacher Assignments ({{ $currentYear->name }})</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Class / Stream</th>
                            <th>Learning Area</th>
                            <th>Assigned Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $asg)
                            <tr>
                                <td class="fw-bold text-primary">{{ $asg->stream->full_name }}</td>
                                <td>{{ $asg->learningArea->name }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $asg->teacher->name }}</span>
                                    <small class="text-muted d-block">{{ $asg->teacher->email }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No subject assignments made yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Assign Subject Modal -->
<div class="modal fade" id="assignSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('academic.learning-areas.assign') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Assign Teacher to Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Class Stream *</label>
                        <select name="stream_id" class="form-select form-select-sm" required>
                            <option value="">-- Choose Stream --</option>
                            @foreach($streams as $st)
                                <option value="{{ $st->id }}">{{ $st->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Learning Area *</label>
                        <select name="learning_area_id" class="form-select form-select-sm" required>
                            <option value="">-- Choose Learning Area --</option>
                            @foreach($learningAreas as $la)
                                <option value="{{ $la->id }}">{{ $la->name }} ({{ $la->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Assigned Teacher *</label>
                        <select name="teacher_id" class="form-select form-select-sm" required>
                            <option value="">-- Choose Teacher --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->email }})</option>
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

