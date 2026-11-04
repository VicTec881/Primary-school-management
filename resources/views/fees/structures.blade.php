@extends('layouts.app')

@section('title', 'Fee Structures')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Grade Fee Structures & Line Items</h4>
        <p class="text-muted small mb-0">Configure standard tuition, meals, activity, transport, and recurring charges per grade.</p>
    </div>
    @if(Auth::user()->hasRole(['super_admin', 'bursar']))
    <button type="button" class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#createStructureModal">
        <i class="bi bi-plus-lg me-1"></i> New Fee Structure
    </button>
    @endif
</div>

<div class="row g-4">
    @forelse($structures as $fs)
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0 text-primary">{{ $fs->title }}</h5>
                        <small class="text-muted">{{ $fs->grade->name }} &bull; {{ $fs->term->name }} ({{ $fs->academicYear->name }})</small>
                    </div>
                    <span class="badge {{ $fs->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $fs->is_active ? 'Active v' . $fs->version : 'Archived' }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item / Charge</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount (KSh)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fs->items as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item->name }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $item->feeCategory->name ?? '-' }}</span></td>
                                        <td>
                                            @if($item->is_optional)
                                                <span class="badge bg-warning-subtle text-dark">Optional</span>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary">Mandatory</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total Mandatory Charges:</th>
                                    <th class="text-end text-primary fs-6">KSh {{ number_format($fs->total_mandatory_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center py-5 text-muted">No fee structures configured yet.</div>
    @endforelse
</div>

<!-- Modal: New Fee Structure -->
<div class="modal fade" id="createStructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('fees.structures.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create Grade Fee Structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Academic Year *</label>
                            <select name="academic_year_id" class="form-select form-select-sm" required>
                                @foreach($years as $y)
                                    <option value="{{ $y->id }}" {{ $y->is_current ? 'selected' : '' }}>Year {{ $y->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Term *</label>
                            <select name="term_id" class="form-select form-select-sm" required>
                                @foreach($terms as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Target Grade *</label>
                            <select name="grade_id" class="form-select form-select-sm" required>
                                @foreach($grades as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Structure Title *</label>
                            <input type="text" name="title" class="form-control form-control-sm" required placeholder="e.g. Grade 4 Term 1 2026 Fee Schedule">
                        </div>
                    </div>

                    <h6 class="fw-bold small text-uppercase border-bottom pb-2">Line Item Charges:</h6>
                    <div id="itemsContainer">
                        @foreach($categories as $idx => $cat)
                            <div class="row g-2 align-items-center mb-2">
                                <input type="hidden" name="items[{{ $idx }}][fee_category_id]" value="{{ $cat->id }}">
                                <div class="col-md-5">
                                    <input type="text" name="items[{{ $idx }}][name]" class="form-control form-control-sm" value="{{ $cat->name }}" required>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">KSh</span>
                                        <input type="number" step="0.01" name="items[{{ $idx }}][amount]" class="form-control" value="0.00" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="items[{{ $idx }}][is_optional]" value="1" id="cat_{{ $cat->id }}" {{ $cat->is_optional ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="cat_{{ $cat->id }}">Optional</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Save Structure</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

