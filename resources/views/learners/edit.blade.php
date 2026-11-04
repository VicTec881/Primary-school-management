@extends('layouts.app')

@section('title', 'Edit Learner Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Edit Learner Profile: {{ $learner->full_name }}</h5>
                <span class="badge bg-light text-dark border">{{ $learner->admission_number }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('learners.update', $learner->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">First Name *</label>
                            <input type="text" name="first_name" class="form-control form-control-sm" value="{{ old('first_name', $learner->first_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control form-control-sm" value="{{ old('middle_name', $learner->middle_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Last Name *</label>
                            <input type="text" name="last_name" class="form-control form-control-sm" value="{{ old('last_name', $learner->last_name) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Gender *</label>
                            <select name="gender" class="form-select form-select-sm" required>
                                <option value="male" {{ old('gender', $learner->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $learner->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', $learner->gender) === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Date of Birth *</label>
                            <input type="date" name="dob" class="form-control form-control-sm" value="{{ old('dob', $learner->dob->toDateString()) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">NEMIS Number</label>
                            <input type="text" name="nemis_number" class="form-control form-control-sm" value="{{ old('nemis_number', $learner->nemis_number) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Previous School</label>
                            <input type="text" name="previous_school" class="form-control form-control-sm" value="{{ old('previous_school', $learner->previous_school) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Enrollment Status *</label>
                            <select name="status" class="form-select form-select-sm" required>
                                <option value="active" {{ old('status', $learner->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="transferred" {{ old('status', $learner->status) === 'transferred' ? 'selected' : '' }}>Transferred</option>
                                <option value="graduated" {{ old('status', $learner->status) === 'graduated' ? 'selected' : '' }}>Graduated</option>
                                <option value="withdrawn" {{ old('status', $learner->status) === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                                <option value="suspended" {{ old('status', $learner->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Medical Notes</label>
                            <textarea name="medical_notes" class="form-control form-control-sm" rows="2">{{ old('medical_notes', $learner->medical_notes) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Special Educational Needs</label>
                            <textarea name="special_needs" class="form-control form-control-sm" rows="2">{{ old('special_needs', $learner->special_needs) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('learners.show', $learner->id) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm fw-semibold px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

