@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Administrative Dashboard</h3>
        <p class="text-muted small mb-0">Overview of student lifecycle, CBE assessments, and fee collections.</p>
    </div>

    <!-- Term Filter Form -->
    <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
        <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($years as $y)
                <option value="{{ $y->id }}" {{ $selectedYearId == $y->id ? 'selected' : '' }}>Year {{ $y->name }}</option>
            @endforeach
        </select>
        <select name="term_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($terms as $t)
                <option value="{{ $t->id }}" {{ $selectedTermId == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </form>
</div>

<!-- 5 Key Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl">
        <div class="card stat-card h-100 p-3" style="border-left-color: #0077b6;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Active Learners</span>
                    <h3 class="fw-bold mb-0 mt-1">{{ number_format($metrics['total_learners']) }}</h3>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
            </div>
            <span class="text-success small mt-2 d-block"><i class="bi bi-check-circle me-1"></i>Enrolled & Attending</span>
        </div>
    </div>

    <div class="col-sm-6 col-xl">
        <div class="card stat-card h-100 p-3" style="border-left-color: #2a9d8f;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Fees Billed</span>
                    <h4 class="fw-bold mb-0 mt-1">KSh {{ number_format($metrics['total_billed'], 2) }}</h4>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                    <i class="bi bi-receipt fs-4"></i>
                </div>
            </div>
            <span class="text-muted small mt-2 d-block">Term Invoices Generated</span>
        </div>
    </div>

    <div class="col-sm-6 col-xl">
        <div class="card stat-card h-100 p-3" style="border-left-color: #198754;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Total Collected</span>
                    <h4 class="fw-bold mb-0 mt-1 text-success">KSh {{ number_format($metrics['total_collected'], 2) }}</h4>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                    <i class="bi bi-cash-coin fs-4"></i>
                </div>
            </div>
            <span class="text-success small mt-2 d-block fw-semibold">{{ $metrics['collection_rate'] }}% Collection Rate</span>
        </div>
    </div>

    <div class="col-sm-6 col-xl">
        <div class="card stat-card h-100 p-3" style="border-left-color: #e63946;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Outstanding Arrears</span>
                    <h4 class="fw-bold mb-0 mt-1 text-danger">KSh {{ number_format($metrics['total_outstanding'], 2) }}</h4>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
                    <i class="bi bi-exclamation-circle-fill fs-4"></i>
                </div>
            </div>
            <span class="text-danger small mt-2 d-block"><i class="bi bi-arrow-right-short"></i>Pending Balances</span>
        </div>
    </div>

    <div class="col-sm-6 col-xl">
        <div class="card stat-card h-100 p-3" style="border-left-color: #ffb703;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Attendance Rate</span>
                    <h3 class="fw-bold mb-0 mt-1 text-warning">{{ $metrics['today_attendance_rate'] }}%</h3>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                    <i class="bi bi-calendar-check-fill fs-4"></i>
                </div>
            </div>
            <span class="text-muted small mt-2 d-block">Today's Daily Register</span>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <span class="fw-bold text-secondary small text-uppercase"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Quick Actions:</span>
            <div class="d-flex flex-wrap gap-2">
                @if(Auth::user()->hasRole(['super_admin', 'registrar']))
                    <a href="{{ route('learners.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-person-plus-fill me-1"></i> New Admission
                    </a>
                @endif
                @if(Auth::user()->hasRole(['super_admin', 'bursar']))
                    <a href="{{ route('payments.create') }}" class="btn btn-sm btn-success">
                        <i class="bi bi-credit-card-2-front me-1"></i> Record Payment
                    </a>
                    <a href="{{ route('payments.arrears-report') }}" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Arrears Report
                    </a>
                @endif
                <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-check2-square me-1"></i> Mark Attendance
                </a>
                <a href="{{ route('assessments.index') }}" class="btn btn-sm btn-outline-info text-dark">
                    <i class="bi bi-journal-check me-1"></i> CBE Marks Entry
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Charts & Summary Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Learner Distribution by Grade</span>
            </div>
            <div class="card-body">
                <canvas id="gradeChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <span class="fw-bold"><i class="bi bi-calendar-event-fill me-2 text-warning"></i>Upcoming School Events</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($metrics['upcoming_events'] as $event)
                        <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                            <div>
                                <span class="fw-semibold text-dark d-block">{{ $event->title }}</span>
                                <small class="text-muted">{{ $event->description }}</small>
                            </div>
                            <span class="badge bg-light text-primary border text-nowrap ms-2">
                                {{ $event->start_date->format('M d, Y') }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">No upcoming events scheduled.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Recent Admissions & Payments Tables -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-person-lines-fill me-2 text-info"></i>Recent Admissions</span>
                <a href="{{ route('learners.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Adm No.</th>
                            <th>Learner Name</th>
                            <th>Grade/Class</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($metrics['recent_admissions'] as $adm)
                            <tr>
                                <td class="fw-semibold text-primary">
                                    <a href="{{ route('learners.show', $adm->id) }}" class="text-decoration-none">{{ $adm->admission_number }}</a>
                                </td>
                                <td>{{ $adm->full_name }}</td>
                                <td>{{ $adm->currentEnrollment?->grade?->name ?? '-' }} ({{ $adm->currentEnrollment?->stream?->name ?? '-' }})</td>
                                <td><span class="badge bg-success-subtle text-success">Active</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No admissions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-cash-stack me-2 text-success"></i>Recent Fee Payments</span>
                <a href="{{ route('payments.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Learner</th>
                            <th>Amount</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($metrics['recent_payments'] as $pay)
                            <tr>
                                <td>
                                    <a href="{{ route('payments.receipt', $pay->id) }}" class="fw-semibold text-decoration-none">{{ $pay->receipt_number }}</a>
                                </td>
                                <td>{{ $pay->learner->full_name }}</td>
                                <td class="fw-bold text-success">KSh {{ number_format($pay->amount, 2) }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($pay->payment_method) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const gradeLabels = {!! json_encode($metrics['grade_distribution']->pluck('name')) !!};
    const gradeData = {!! json_encode($metrics['grade_distribution']->pluck('count')) !!};

    const ctx = document.getElementById('gradeChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: gradeLabels,
                datasets: [{
                    label: 'Enrolled Learners',
                    data: gradeData,
                    backgroundColor: '#0077b6',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
</script>
@endpush

