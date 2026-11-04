@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white d-flex align-items-center">
                <i class="bi bi-shield-lock-fill text-primary me-2 fs-5"></i>
                <h5 class="mb-0 fw-bold">Update Account Password</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('password.update') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold small">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">New Password (minimum 8 characters)</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold small">Confirm New Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary fw-semibold px-4">
                            <i class="bi bi-check-lg me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

