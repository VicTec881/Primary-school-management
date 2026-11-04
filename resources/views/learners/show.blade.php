@extends('layouts.app')

@section('title', $learner->full_name . ' - Profile')

@section('content')
<!-- Header Card -->
<div class="card mb-4 border-0 shadow-sm overflow-hidden">
    <div class="p-4" style="background: linear-gradient(135deg, #1b365d 0%, #0077b6 100%); color: #ffffff;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center">
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3 shadow" style="width: 64px; height: 64px; font-size: 1.5rem; font-weight: bold;">
                    {{ substr($learner->first_name, 0, 1) }}{{ substr($learner->last_name, 0, 1) }}
                </div>
                <div>
                    <h3 class="fw-bold mb-1 text-white">{{ $learner->full_name }}</h3>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-light text-dark fw-bold"><i class="bi bi-hash"></i>{{ $learner->admission_number }}</span>
                        <span class="badge bg-info text-dark">
                            {{ $learner->currentEnrollment?->grade?->name ?? 'No Grade' }} - {{ $learner->currentEnrollment?->stream?->name ?? 'No Stream' }}
                        </span>
                        <span class="badge bg-success">{{ ucfirst($learner->status) }}</span>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('fees.statement', $learner->id) }}" class="btn btn-light btn-sm fw-semibold shadow-sm">
                    <i class="bi bi-receipt me-1"></i> Fee Statement
                </a>
                <a href="{{ route('reports.download', $learner->id) }}" class="btn btn-warning btn-sm fw-semibold shadow-sm" target="_blank">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Report Card
                </a>
                @if(Auth::user()->hasRole(['super_admin', 'bursar']))
                <a href="{{ route('payments.create', ['learner_id' => $learner->id]) }}" class="btn btn-success btn-sm fw-semibold shadow-sm">
                    <i class="bi bi-cash-stack me-1"></i> Record Payment
                </a>
                @endif
                @if(Auth::user()->hasRole(['super_admin', 'registrar']))
                <a href="{{ route('learners.edit', $learner->id) }}" class="btn btn-outline-light btn-sm fw-semibold">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-3" id="profileTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-semibold" id="bio-tab" data-bs-toggle="tab" data-bs-target="#bio" type="button">
            <i class="bi bi-person me-1"></i> Personal Bio
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="guardian-tab" data-bs-toggle="tab" data-bs-target="#guardian" type="button">
            <i class="bi bi-people me-1"></i> Parents & Guardians
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance" type="button">
            <i class="bi bi-cash-coin me-1"></i> Fees & Invoices
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="cbe-tab" data-bs-toggle="tab" data-bs-target="#cbe" type="button">
            <i class="bi bi-clipboard2-check me-1"></i> CBE Assessments
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button">
            <i class="bi bi-calendar-check me-1"></i> Attendance
        </button>
    </li>
</ul>

<!-- Tabs Content -->
<div class="tab-content" id="profileTabsContent">
    <!-- Tab 1: Bio -->
    <div class="tab-pane fade show active" id="bio">
        <div class="card">
            <div class="card-header bg-white fw-bold">Personal & Demographic Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Full Legal Name</span>
                        <span class="fw-semibold">{{ $learner->full_name }}</span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Admission Date</span>
                        <span class="fw-semibold">{{ $learner->admission_date->format('d M Y') }}</span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Gender</span>
                        <span class="fw-semibold">{{ ucfirst($learner->gender) }}</span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Date of Birth (Age)</span>
                        <span class="fw-semibold">{{ $learner->dob->format('d M Y') }} ({{ $learner->dob->age }} yrs)</span>
                    </div>

                    <div class="col-md-3">
                        <span class="text-muted small d-block">NEMIS Number</span>
                        <span class="fw-semibold">{{ $learner->nemis_number ?? 'Not assigned' }}</span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Assessment Number</span>
                        <span class="fw-semibold">{{ $learner->assessment_number ?? 'Not assigned' }}</span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Previous School</span>
                        <span class="fw-semibold">{{ $learner->previous_school ?? 'None (Initial Admission)' }}</span>
                    </div>

                    <div class="col-md-6">
                        <span class="text-muted small d-block">Medical Notes & Allergies</span>
                        <span class="text-dark">{{ $learner->medical_notes ?? 'None reported.' }}</span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Special Educational Needs</span>
                        <span class="text-dark">{{ $learner->special_needs ?? 'Standard curriculum.' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Guardian -->
    <div class="tab-pane fade" id="guardian">
        <div class="card">
            <div class="card-header bg-white fw-bold">Linked Guardians & Emergency Contacts</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Guardian Name</th>
                            <th>Relationship</th>
                            <th>Phone Contact</th>
                            <th>Email</th>
                            <th>National ID</th>
                            <th>Residential Address</th>
                            <th>Authorized Collector</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($learner->guardians as $g)
                            <tr>
                                <td class="fw-bold">{{ $g->full_name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $g->pivot->relationship }}</span></td>
                                <td class="fw-semibold text-primary"><i class="bi bi-telephone me-1"></i>{{ $g->phone }}</td>
                                <td>{{ $g->email ?? '-' }}</td>
                                <td>{{ $g->national_id ?? '-' }}</td>
                                <td>{{ $g->address ?? '-' }}</td>
                                <td>
                                    @if($g->pivot->authorized_to_collect)
                                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No guardians linked.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Finance -->
    <div class="tab-pane fade" id="finance">
        <!-- Balance Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card p-3 border-start border-4 border-info">
                    <span class="text-muted small text-uppercase">Opening Balance</span>
                    <h5 class="fw-bold mb-0 mt-1">KSh {{ number_format($learner->feeAccount?->opening_balance ?? 0, 2) }}</h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-4 border-primary">
                    <span class="text-muted small text-uppercase">Total Invoiced</span>
                    <h5 class="fw-bold mb-0 mt-1">KSh {{ number_format($learner->feeAccount?->total_billed ?? 0, 2) }}</h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-4 border-success">
                    <span class="text-muted small text-uppercase">Total Paid</span>
                    <h5 class="fw-bold mb-0 mt-1 text-success">KSh {{ number_format($learner->feeAccount?->total_paid ?? 0, 2) }}</h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-4 border-danger">
                    <span class="text-muted small text-uppercase">Current Outstanding</span>
                    <h5 class="fw-bold mb-0 mt-1 text-danger">KSh {{ number_format($learner->feeAccount?->current_balance ?? 0, 2) }}</h5>
                </div>
            </div>
        </div>

        <!-- Invoices List -->
        <div class="card mb-4">
            <div class="card-header bg-white fw-bold">Invoices & Term Charges</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Invoice No.</th>
                            <th>Term / Year</th>
                            <th>Total Amount</th>
                            <th>Discounts</th>
                            <th>Paid Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($learner->invoices as $inv)
                            <tr>
                                <td class="fw-bold text-primary">{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->term->name ?? '-' }} ({{ $inv->academicYear->name ?? '-' }})</td>
                                <td>KSh {{ number_format($inv->total_amount, 2) }}</td>
                                <td>KSh {{ number_format($inv->discount_amount, 2) }}</td>
                                <td class="text-success fw-semibold">KSh {{ number_format($inv->paid_amount, 2) }}</td>
                                <td class="text-danger fw-bold">KSh {{ number_format($inv->balance, 2) }}</td>
                                <td>
                                    <span class="badge {{ $inv->status === 'paid' ? 'bg-success' : ($inv->status === 'partial' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                        {{ ucfirst($inv->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('fees.invoices.show', $inv->id) }}" class="btn btn-sm btn-outline-primary">View Breakdown</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">No invoices generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payments & Receipts List -->
        <div class="card">
            <div class="card-header bg-white fw-bold">Payment Receipts</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Receipt No.</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($learner->payments as $p)
                            <tr>
                                <td class="fw-bold">{{ $p->receipt_number }}</td>
                                <td>{{ $p->payment_date->format('d M Y') }}</td>
                                <td class="text-success fw-bold">KSh {{ number_format($p->amount, 2) }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($p->payment_method) }}</span></td>
                                <td><code>{{ $p->transaction_reference ?? '-' }}</code></td>
                                <td>
                                    <span class="badge {{ $p->status === 'completed' ? 'bg-success' : 'bg-danger' }}">
                                        {{ ucfirst($p->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('payments.receipt', $p->id) }}" class="btn btn-sm btn-light border"><i class="bi bi-printer me-1"></i>Print</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 4: CBE Assessments -->
    <div class="tab-pane fade" id="cbe">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Kenyan CBC Competency Assessment Records</span>
                <a href="{{ route('reports.download', $learner->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="bi bi-printer me-1"></i> Print Official Report Card
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Learning Area (Subject)</th>
                            <th>Assessment Period</th>
                            <th>Raw Score</th>
                            <th>Performance Level</th>
                            <th>Teacher Rubric Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($learner->assessments as $asm)
                            <tr>
                                <td class="fw-bold text-dark">{{ $asm->learningArea->name ?? '-' }}</td>
                                <td>{{ $asm->assessmentType->name ?? '-' }}</td>
                                <td class="fw-semibold">{{ number_format($asm->raw_score, 1) }} / {{ number_format($asm->max_score, 0) }}</td>
                                <td>
                                    @php
                                        $levelColors = [
                                            'EE' => 'bg-success',
                                            'ME' => 'bg-primary',
                                            'AE' => 'bg-warning text-dark',
                                            'BE' => 'bg-danger'
                                        ];
                                    @endphp
                                    <span class="badge {{ $levelColors[$asm->performance_level] ?? 'bg-secondary' }} px-2 py-1">
                                        {{ $asm->performance_level }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $asm->rubric_comments ?? 'Steady progress observed.' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No assessment records entered yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 5: Attendance -->
    <div class="tab-pane fade" id="attendance">
        <div class="card">
            <div class="card-header bg-white fw-bold">Recent Attendance Register Sessions</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Remarks / Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($learner->attendanceRecords as $att)
                            <tr>
                                <td>{{ $att->session?->date ? \Carbon\Carbon::parse($att->session->date)->format('d M Y (l)') : '-' }}</td>
                                <td>
                                    @if($att->status === 'present')
                                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Present</span>
                                    @elseif($att->status === 'absent')
                                        <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i>Absent</span>
                                    @elseif($att->status === 'late')
                                        <span class="badge bg-warning-subtle text-warning"><i class="bi bi-clock me-1"></i>Late</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">Excused</span>
                                    @endif
                                </td>
                                <td>{{ $att->remarks ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No attendance records logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

