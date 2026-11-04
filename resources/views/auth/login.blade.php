<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - {{ \App\Models\SchoolSetting::get('school_name', 'St. Jude Primary School') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f1e36 0%, #1b365d 50%, #0077b6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 1.5rem;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.25);
            overflow: hidden;
            background: #ffffff;
            width: 100%;
            max-width: 480px;
        }
        .brand-header {
            background-color: #0f1e36;
            color: #ffffff;
            padding: 2.25rem 2rem;
            text-align: center;
        }
        .quick-role-badge {
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.75rem;
        }
        .quick-role-badge:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-header">
        <i class="bi bi-mortarboard-fill fs-1 text-info mb-2 d-block"></i>
        <h4 class="fw-bold mb-1">{{ \App\Models\SchoolSetting::get('school_name', 'St. Jude Primary School') }}</h4>
        <p class="text-white-50 small mb-0">{{ \App\Models\SchoolSetting::get('school_motto', 'Excellence in Knowledge and Character') }}</p>
    </div>

    <div class="p-4 p-md-5">
        <h5 class="fw-bold text-dark mb-4 text-center">Staff & Parent Portal Sign In</h5>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold text-secondary small">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="name@stjudeprimary.sc.ke">
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="password" class="form-label fw-semibold text-secondary small mb-0">Password</label>
                </div>
                <div class="input-group mt-1">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password">
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label text-muted small" for="remember">
                    Remember this device
                </label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center">
            <p class="text-muted small fw-semibold mb-2">Development Quick-Login Roles:</p>
            <div class="d-flex flex-wrap gap-1 justify-content-center">
                <span class="badge bg-dark quick-role-badge" onclick="fillCreds('admin@stjudeprimary.sc.ke', 'Admin@2026!')">Super Admin</span>
                <span class="badge bg-primary quick-role-badge" onclick="fillCreds('headteacher@stjudeprimary.sc.ke', 'Head@2026!')">Headteacher</span>
                <span class="badge bg-success quick-role-badge" onclick="fillCreds('bursar@stjudeprimary.sc.ke', 'Bursar@2026!')">Bursar</span>
                <span class="badge bg-info text-dark quick-role-badge" onclick="fillCreds('registrar@stjudeprimary.sc.ke', 'Registrar@2026!')">Registrar</span>
                <span class="badge bg-secondary quick-role-badge" onclick="fillCreds('teacher.otieno@stjudeprimary.sc.ke', 'Teacher@2026!')">Teacher</span>
                <span class="badge bg-warning text-dark quick-role-badge" onclick="fillCreds('parent.mwangi@gmail.com', 'Parent@2026!')">Parent</span>
            </div>
        </div>
    </div>
</div>

<script>
    function fillCreds(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
</body>
</html>

