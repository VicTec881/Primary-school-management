@extends('layouts.app')

@section('title', 'Parent Profile Not Linked')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-7">
        <div class="card shadow-sm border-0 text-center p-5">
            <div class="card-body">
                <i class="bi bi-person-x-fill text-warning display-1 mb-3"></i>
                <h4 class="fw-bold text-dark">No Guardian Record Linked</h4>
                <p class="text-muted mb-4">
                    Your user account (<strong>{{ Auth::user()->email }}</strong>) is registered as a Parent, but it has not yet been linked to a specific guardian and learner profile in the school registry.
                </p>
                <div class="alert alert-info py-2 px-3 small text-start">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Please contact the School Registrar or School Administration Office to link your account to your child’s admission number.
                </div>
                <div class="mt-4">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-house me-1"></i> Return to Main Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

