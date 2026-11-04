@extends('layouts.app')

@section('title', 'Security Audit Trail & Logs')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Security Audit Trail</h4>
        <p class="text-muted small mb-0">Immutable records of all administrative modifications, financial adjustments, and authentication events.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Settings
        </a>
    </div>
</div>

{{-- Filters Card --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('settings.audit-logs') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Search Action Keyword</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="action" class="form-control" placeholder="e.g. payment_reversed, user_created, learner_promoted..." value="{{ request('action') }}">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Filter by User</label>
                <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Users --</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->role_name }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('settings.audit-logs') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Audit Logs Table --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-journal-code me-2 text-primary"></i>Audit Log Entries
        </h6>
        <span class="badge bg-secondary">{{ $logs->total() }} Logged Events</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.85rem;">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th style="width: 170px;">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity Model</th>
                        <th>IP Address</th>
                        <th class="text-end">Payload Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-muted small">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>
                            @if($log->user)
                            <span class="fw-semibold text-dark">{{ $log->user->name }}</span>
                            <span class="badge bg-light text-muted border small">{{ $log->user->role_name }}</span>
                            @else
                            <span class="text-muted fst-italic">System / Guest</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $actionBadge = match(true) {
                                    str_contains($log->action, 'reversed') || str_contains($log->action, 'deactivated') => 'bg-danger-subtle text-danger border border-danger',
                                    str_contains($log->action, 'created') || str_contains($log->action, 'completed') => 'bg-success-subtle text-success border border-success',
                                    str_contains($log->action, 'updated') => 'bg-warning-subtle text-warning-emphasis border border-warning',
                                    default => 'bg-light text-dark border'
                                };
                            @endphp
                            <span class="badge {{ $actionBadge }} px-2 py-1">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="small text-secondary">
                            {{ class_basename($log->auditable_type ?? 'N/A') }} 
                            @if($log->auditable_id)
                            #{{ $log->auditable_id }}
                            @endif
                        </td>
                        <td class="small text-muted">{{ $log->ip_address ?: '127.0.0.1' }}</td>
                        <td class="text-end">
                            @if(!empty($log->new_values))
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" data-bs-toggle="collapse" data-bs-target="#log_{{ $log->id }}">
                                <i class="bi bi-code-slash"></i> View JSON
                            </button>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @if(!empty($log->new_values))
                    <tr class="collapse bg-light" id="log_{{ $log->id }}">
                        <td colspan="6" class="p-3">
                            <div class="row g-2">
                                @if(!empty($log->old_values))
                                <div class="col-md-6">
                                    <small class="fw-bold text-danger d-block mb-1">Previous State (Old):</small>
                                    <pre class="bg-white p-2 rounded border mb-0 small text-danger" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                                @endif
                                <div class="col-md-{{ !empty($log->old_values) ? '6' : '12' }}">
                                    <small class="fw-bold text-success d-block mb-1">New State / Changes:</small>
                                    <pre class="bg-white p-2 rounded border mb-0 small text-success" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No audit log records match the search filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection

