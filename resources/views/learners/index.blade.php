@extends('layouts.app')

@section('title', 'Learners Directory')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Learner Directory & Admissions</h4>
        <p class="text-muted small mb-0">Manage learner lifecycle, class allocations, and guardian records.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('learners.export') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        @if(Auth::user()->hasRole(['super_admin', 'registrar']))
        <a href="{{ route('learners.import') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-upload me-1"></i> Import CSV
        </a>
        <a href="{{ route('learners.create') }}" class="btn btn-primary btn-sm fw-semibold">
            <i class="bi bi-person-plus-fill me-1"></i> New Admission
        </a>
        @endif
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('learners.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name, admission no, phone, NEMIS..." value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-md-3">
                <select name="grade_id" class="form-select form-select-sm">
                    <option value="">-- All Grades --</option>
                    @foreach($grades as $grade)
                        <option value="{{ $grade->id }}" {{ request('grade_id') == $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                    <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                    <option value="withdrawn" {{ request('status') == 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('learners.index') }}" class="btn btn-light btn-sm border">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Learners Table Card -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Adm No.</th>
                    <th>Learner Name</th>
                    <th>Gender</th>
                    <th>Grade & Stream</th>
                    <th>Primary Guardian</th>
                    <th>Fee Balance</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($learners as $l)
                    <tr>
                        <td>
                            <a href="{{ route('learners.show', $l->id) }}" class="fw-bold text-decoration-none">
                                {{ $l->admission_number }}
                            </a>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-weight: 600; font-size: 0.8rem;">
                                    {{ substr($l->first_name, 0, 1) }}{{ substr($l->last_name, 0, 1) }}
                                </div>
                                <div>
                                    <span class="fw-semibold text-dark d-block">{{ $l->full_name }}</span>
                                    <small class="text-muted">DOB: {{ $l->dob->format('d M Y') }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst($l->gender) }}</span></td>
                        <td>
                            <span class="fw-medium text-dark">{{ $l->currentEnrollment?->grade?->name ?? 'Unassigned' }}</span>
                            <small class="text-muted d-block">{{ $l->currentEnrollment?->stream?->name ? 'Stream ' . $l->currentEnrollment->stream->name : '' }}</small>
                        </td>
                        <td>
                            @php $pg = $l->primaryGuardian(); @endphp
                            @if($pg)
                                <span class="d-block small fw-semibold">{{ $pg->full_name }}</span>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $pg->phone }}</small>
                            @else
                                <span class="text-muted small">Not linked</span>
                            @endif
                        </td>
                        <td>
                            @php $bal = (float) ($l->feeAccount?->current_balance ?? 0); @endphp
                            @if($bal > 0)
                                <span class="badge bg-danger-subtle text-danger fw-bold">KSh {{ number_format($bal, 2) }}</span>
                            @else
                                <span class="badge bg-success-subtle text-success fw-bold">Cleared</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $l->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($l->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Manage
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item" href="{{ route('learners.show', $l->id) }}"><i class="bi bi-eye me-2"></i>View Profile</a></li>
                                    <li><a class="dropdown-item" href="{{ route('fees.statement', $l->id) }}"><i class="bi bi-receipt me-2"></i>Fee Statement</a></li>
                                    @if(Auth::user()->hasRole(['super_admin', 'registrar']))
                                    <li><a class="dropdown-item" href="{{ route('learners.edit', $l->id) }}"><i class="bi bi-pencil me-2"></i>Edit Details</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('learners.destroy', $l->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this learner? (Learners with financial/academic history cannot be deleted).');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No learners found matching the given filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($learners->hasPages())
    <div class="card-footer bg-white border-top py-2">
        {{ $learners->links() }}
    </div>
    @endif
</div>
@endsection

