@extends('layouts.app')

@section('title', 'School & System Settings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-gear text-primary me-2"></i>School & System Configuration</h4>
        <p class="text-muted small mb-0">Configure school institutional profile, contact branding, financial settings, and SMS gateways.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('settings.users') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-people me-1"></i> Staff Accounts
        </a>
        <a href="{{ route('settings.audit-logs') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-shield-check me-1"></i> Security Audit Logs
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('settings.update') }}">
            @csrf
            
            {{-- Institutional Profile --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-building me-2 text-primary"></i>School Identity & Letterhead Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">School Name <span class="text-danger">*</span></label>
                            <input type="text" name="school_name" class="form-control" value="{{ $settings['school_name']->value ?? 'St. Jude Primary & Junior School' }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Admission Number Prefix</label>
                            <input type="text" name="admission_prefix" class="form-control font-monospace" value="{{ $settings['admission_prefix']->value ?? 'STJ' }}" placeholder="e.g. STJ">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">School Motto</label>
                            <input type="text" name="school_motto" class="form-control" value="{{ $settings['school_motto']->value ?? 'Excellence in Knowledge and Character' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Postal Box Address</label>
                            <input type="text" name="school_box" class="form-control" value="{{ $settings['school_box']->value ?? 'P.O. Box 45120 - 00100' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Town / City</label>
                            <input type="text" name="school_town" class="form-control" value="{{ $settings['school_town']->value ?? 'Nairobi, Kenya' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Physical Location / Campus Address</label>
                            <input type="text" name="school_address" class="form-control" value="{{ $settings['school_address']->value ?? 'Off Waiyaki Way, Westlands, Nairobi' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Communications & Contact --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-telephone me-2 text-success"></i>Official Contacts & Messaging</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Phone Number</label>
                            <input type="text" name="school_phone" class="form-control" value="{{ $settings['school_phone']->value ?? '+254 711 234 567' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Email Address</label>
                            <input type="email" name="school_email" class="form-control" value="{{ $settings['school_email']->value ?? 'info@stjudeprimary.sc.ke' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMS Sender ID (Alphanumeric)</label>
                            <input type="text" name="sms_sender_id" class="form-control" value="{{ $settings['sms_sender_id']->value ?? 'STJUDE' }}" maxlength="11">
                            <div class="form-text">Used when dispatching SMS fee reminders and attendance notifications.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Currency Code / Symbol</label>
                            <input type="text" name="currency_symbol" class="form-control font-monospace" value="{{ $settings['currency_symbol']->value ?? 'KSh' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Financial & M-Pesa Integration --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-phone me-2 text-success"></i>Safaricom M-Pesa Daraja Configuration</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">M-Pesa Paybill / Till Number</label>
                            <input type="text" name="mpesa_paybill" class="form-control font-monospace" value="{{ $settings['mpesa_paybill']->value ?? '522522' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">M-Pesa Account Name Display</label>
                            <input type="text" name="mpesa_account_name" class="form-control" value="{{ $settings['mpesa_account_name']->value ?? 'St. Jude School Fees' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-5">
                <button type="submit" class="btn btn-primary fw-semibold px-4">
                    <i class="bi bi-check2-circle me-2"></i> Save System Settings
                </button>
            </div>
        </form>
    </div>

    {{-- System Status Sidebar --}}
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cpu me-2 text-secondary"></i>Environment & Engine Status</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr class="border-bottom"><td class="text-muted p-3">PHP Version</td><td class="fw-bold p-3 text-end font-monospace">{{ PHP_VERSION }}</td></tr>
                    <tr class="border-bottom"><td class="text-muted p-3">Laravel Version</td><td class="fw-bold p-3 text-end font-monospace">{{ app()->version() }}</td></tr>
                    <tr class="border-bottom"><td class="text-muted p-3">Database Connection</td><td class="fw-bold p-3 text-end font-monospace text-success">{{ config('database.default') }}</td></tr>
                    <tr class="border-bottom"><td class="text-muted p-3">App Timezone</td><td class="fw-bold p-3 text-end font-monospace">{{ config('app.timezone') }}</td></tr>
                    <tr><td class="text-muted p-3">App Environment</td><td class="fw-bold p-3 text-end"><span class="badge bg-info text-dark">{{ config('app.env') }}</span></td></tr>
                </table>
            </div>
        </div>

        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body p-3 small text-muted">
                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-shield-lock me-1"></i>Security Tip</h6>
                Configuration changes are logged in the tamper-proof security audit log. Ensure M-Pesa API Consumer Keys and Secrets are securely retained in server environment files (`.env`).
            </div>
        </div>
    </div>
</div>
@endsection
