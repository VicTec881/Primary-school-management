@extends('layouts.app')

@section('title', 'Parent Portal Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-house-heart text-primary me-2"></i>Parent & Guardian Portal</h4>
        <p class="text-muted small mb-0">Welcome, <strong class="text-dark">{{ $guardian->full_name }}</strong>. Monitor your children's academic performance, attendance, and fee statements.</p>
    </div>
    <div class="mt-2 mt-md-0">
        <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-2">
            <i class="bi bi-shield-check me-1"></i> Authorized Guardian Access
        </span>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Children Cards Section --}}
    <div class="col-lg-8">
        <h5 class="fw-bold mb-3 text-secondary text-uppercase small" style="letter-spacing: 0.5px;">
            <i class="bi bi-people-fill me-1"></i> My Linked Children ({{ $children->count() }})
        </h5>

        <div class="row g-3">
            @forelse($children as $child)
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 border-bottom pb-3 mb-3">
                            <div>
                                <h5 class="fw-bold text-primary mb-1">{{ $child->full_name }}</h5>
                                <div class="text-muted small">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $child->admission_number }}</span>
                                    <span class="ms-2">
                                        <i class="bi bi-mortarboard me-1"></i>
                                        {{ $child->currentEnrollment->grade->name ?? 'Primary' }} {{ $child->currentEnrollment->stream->name ?? '' }}
                                    </span>
                                    @if($child->nemis_number)
                                    <span class="ms-2 text-muted">&bull; UPI: {{ $child->nemis_number }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-end">
                                @php
                                    $bal = $child->feeAccount->current_balance ?? 0;
                                @endphp
                                <span class="small text-muted d-block">Current Fee Balance</span>
                                <h5 class="fw-bold {{ $bal > 0 ? 'text-danger' : 'text-success' }} mb-0 font-monospace">
                                    KSh {{ number_format($bal, 2) }}
                                </h5>
                            </div>
                        </div>

                        <div class="row g-3 align-items-center">
                            <div class="col-md-7">
                                @php
                                    $latestReport = $child->reportCards->first();
                                @endphp
                                @if($latestReport)
                                <div class="bg-light p-2 rounded small">
                                    <strong>Latest Report Card:</strong> 
                                    <span class="text-muted">{{ $latestReport->term->name ?? 'Term' }} ({{ $latestReport->academicYear->name ?? '' }})</span>
                                    &bull; 
                                    <span class="text-success fw-semibold">Attendance: {{ $latestReport->attendance_days_present }}/{{ $latestReport->attendance_days_total }} days</span>
                                </div>
                                @else
                                <div class="text-muted small fst-italic">
                                    Summative report card for current term is currently being finalized.
                                </div>
                                @endif
                            </div>
                            <div class="col-md-5 text-md-end">
                                <div class="btn-group">
                                    <a href="{{ route('parent.child.statement', $child->id) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-receipt me-1"></i> Fee Statement
                                    </a>
                                    <a href="{{ route('parent.child.reports', $child->id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-text me-1"></i> Report Cards
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="card shadow-sm border-0 text-center py-5">
                    <p class="text-muted mb-0">No learners currently linked to your guardian profile.</p>
                </div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Announcements & School Events Sidebar --}}
    <div class="col-lg-4">
        {{-- M-Pesa Payment Instructions Card --}}
        <div class="card shadow-sm border-0 mb-4 bg-success text-white">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-phone me-1"></i>Pay Fees via M-Pesa</h6>
                <div class="small opacity-90 mb-1">
                    <strong>Paybill Business No:</strong> <span class="font-monospace fw-bold bg-white text-success px-1 rounded">{{ \App\Models\SchoolSetting::get('mpesa_paybill', '522522') }}</span>
                </div>
                <div class="small opacity-90 mb-2">
                    <strong>Account No:</strong> Learner Admission Number (e.g. <span class="font-monospace">ADM/2026/001</span>)
                </div>
                <div class="small text-white-50" style="font-size: 0.75rem;">
                    Instant automatic receipting and balance update upon payment confirmation.
                </div>
            </div>
        </div>

        {{-- Announcements --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-megaphone me-2 text-primary"></i>School Announcements</h6>
            </div>
            <div class="list-group list-group-flush">
                @forelse($announcements as $announcement)
                <div class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-dark small">{{ $announcement->title }}</strong>
                        <span class="badge bg-light text-muted border small" style="font-size: 0.65rem;">
                            {{ $announcement->published_at ? \Carbon\Carbon::parse($announcement->published_at)->format('d M') : '' }}
                        </span>
                    </div>
                    <p class="small text-muted mb-0">{{ Str::limit($announcement->content, 110) }}</p>
                </div>
                @empty
                <div class="list-group-item text-center py-3 text-muted small">
                    No active announcements.
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upcoming Events --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-event me-2 text-success"></i>Upcoming Events</h6>
            </div>
            <div class="list-group list-group-flush">
                @forelse($events as $event)
                <div class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-dark small">{{ $event->title }}</strong>
                        <span class="badge bg-primary-subtle text-primary small" style="font-size: 0.7rem;">
                            {{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}
                        </span>
                    </div>
                    @if($event->description)
                    <p class="small text-muted mb-0">{{ Str::limit($event->description, 80) }}</p>
                    @endif
                </div>
                @empty
                <div class="list-group-item text-center py-3 text-muted small">
                    No scheduled upcoming events.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
