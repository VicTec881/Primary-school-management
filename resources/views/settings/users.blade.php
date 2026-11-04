@extends('layouts.app')

@section('title', 'Staff User Accounts & Access Control')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i>Staff & User Access Management</h4>
        <p class="text-muted small mb-0">Create, manage, and toggle access for teaching and administrative personnel.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <button type="button" class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="bi bi-person-plus me-1"></i> Add New Staff User
        </button>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Settings
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-shield-lock me-2 text-primary"></i>System User Accounts
        </h6>
        <span class="badge bg-secondary">{{ $users->total() }} Total Users</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Name</th>
                        <th>Email Address</th>
                        <th>Phone</th>
                        <th>Assigned Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $user->name }}</div>
                            @if($user->id === Auth::id())
                            <span class="badge bg-info-subtle text-info border border-info small" style="font-size: 0.65rem;">You</span>
                            @endif
                        </td>
                        <td class="font-monospace small">{{ $user->email }}</td>
                        <td class="small">{{ $user->phone ?: '—' }}</td>
                        <td>
                            @php
                                $roleBadge = match($user->role_name) {
                                    'super_admin' => 'bg-danger',
                                    'headteacher' => 'bg-primary',
                                    'bursar' => 'bg-success',
                                    'registrar' => 'bg-warning text-dark',
                                    'teacher' => 'bg-info text-dark',
                                    'parent' => 'bg-secondary',
                                    default => 'bg-light text-dark border'
                                };
                            @endphp
                            <span class="badge {{ $roleBadge }} text-uppercase" style="font-size: 0.72rem;">
                                {{ str_replace('_', ' ', $user->role_name ?? 'user') }}
                            </span>
                        </td>
                        <td>
                            @if($user->is_active)
                            <span class="badge bg-success-subtle text-success border border-success">Active</span>
                            @else
                            <span class="badge bg-danger-subtle text-danger border border-danger">Deactivated</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            @if($user->id !== Auth::id())
                            <form method="POST" action="{{ route('settings.users.toggle', $user->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to change this user\'s access status?');">
                                @csrf
                                @if($user->is_active)
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Deactivate Account">
                                    <i class="bi bi-person-x"></i> Deactivate
                                </button>
                                @else
                                <button type="submit" class="btn btn-outline-success btn-sm py-0 px-2" title="Activate Account">
                                    <i class="bi bi-person-check"></i> Activate
                                </button>
                                @endif
                            </form>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $users->links() }}
    </div>
    @endif
</div>

{{-- Add User Modal --}}
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('settings.users.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Create New Staff User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Samuel Mutiso" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address (Login Username) <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="e.g. smutiso@stjudeprimary.sc.ke" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">User Role <span class="text-danger">*</span></label>
                        <select name="role_name" class="form-select" required>
                            <option value="">-- Select Assigned Role --</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->display_name ?? ucwords(str_replace('_', ' ', $role->name)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Initial Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="8" placeholder="Minimum 8 characters" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Save Staff Account
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

