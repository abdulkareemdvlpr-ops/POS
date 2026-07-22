<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — POS System</title>
    <link href="{{ asset('offline/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('offline/icons.css') }}" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%); min-height: 100vh; display:flex; align-items:center; justify-content:center; }
        .login-card { border:none; border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,0.4); max-width:420px; width:100%; }
        .login-brand { background: linear-gradient(135deg, #0ea5e9, #6366f1); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-size:2rem; font-weight:800; }
        .btn-primary { background:#0ea5e9; border-color:#0ea5e9; }
        .btn-primary:hover { background:#0284c7; border-color:#0284c7; }
        .form-control:focus { border-color:#0ea5e9; box-shadow:0 0 0 0.25rem rgba(14,165,233,0.2); }
    </style>
</head>
<body>
<div class="container px-3">
    <div class="login-card card mx-auto">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="login-brand mb-1">Pharmacy POS</div>
                <p class="text-muted mb-0">Sign in to your account</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="passwordField" class="form-control @error('password') is-invalid @enderror"
                            placeholder="••••••••" required>
                        <button type="button" class="btn btn-outline-secondary" id="toggleBtn" onclick="togglePwd()">Show</button>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label text-muted" for="remember">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    Sign In
                </button>
            </form>

            <hr class="my-4">
            <p class="text-center text-muted small mb-0">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-decoration-none">Register</a>
            </p>
        </div>
    </div>
</div>
<script src="{{ asset('offline/bootstrap.bundle.min.js') }}"></script>
<script>
function togglePwd() {
    const f = document.getElementById('passwordField');
    const btn = document.getElementById('toggleBtn');
    if (f.type === 'password') { f.type = 'text'; btn.textContent = 'Hide'; }
    else { f.type = 'password'; btn.textContent = 'Show'; }
}
</script>
</body>
</html>
