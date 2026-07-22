<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — POS System</title>
    <link href="{{ asset('offline/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('offline/icons.css') }}" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%); min-height: 100vh; display:flex; align-items:center; justify-content:center; }
        .register-card { border:none; border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,0.4); max-width:440px; width:100%; }
        .login-brand { background: linear-gradient(135deg, #0ea5e9, #6366f1); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-size:2rem; font-weight:800; }
        .btn-primary { background:#0ea5e9; border-color:#0ea5e9; }
        .btn-primary:hover { background:#0284c7; border-color:#0284c7; }
        .form-control:focus { border-color:#0ea5e9; box-shadow:0 0 0 0.25rem rgba(14,165,233,0.2); }
    </style>
</head>
<body>
<div class="container px-3">
    <div class="register-card card mx-auto">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="login-brand mb-1">Pharmacy POS</div>
                <p class="text-muted mb-0">Create a new account</p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" placeholder="Your full name" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" placeholder="email@example.com" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="Min. 8 characters" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    Create Account
                </button>
            </form>

            <hr class="my-4">
            <p class="text-center text-muted small mb-0">
                Already have an account?
                <a href="{{ route('login') }}" class="text-decoration-none">Sign in</a>
            </p>
        </div>
    </div>
</div>
<script src="{{ asset('offline/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
