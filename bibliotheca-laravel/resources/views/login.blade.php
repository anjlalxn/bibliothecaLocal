<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Bibliotheca</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Momo+Trust+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('style.css') }}" rel="stylesheet">
</head>

<body>

    <div class="container-fluid p-0">
        <div class="row g-0 auth-wrapper">

            <div class="col-md-6 brand-panel d-flex flex-column align-items-center justify-content-center text-center">
                <div class="logo-placeholder d-flex align-items-center justify-content-center">
                    <img src="{{ asset('images/logo_index.png') }}" alt="Bibliotheca" class="img-fluid">
                </div>

                <h1 class="brand-name">bibliotheca</h1>
                <p class="brand-tagline">
                    modern library management system for community development
                </p>
            </div>

            <div class="col-md-6 form-panel d-flex align-items-center justify-content-center">
                <div class="auth-card">

                    <div class="text-center">
                        <h2 class="auth-title">Sign In</h2>
                        <p class="auth-subtitle">Enter your admin account</p>
                    </div>

                    @if ($errors->any())
                    <div class="alert alert-danger py-2" role="alert">
                        {{ $errors->first() }}
                    </div>
                    @endif

                    <form action="{{ route('login.attempt') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text"
                                class="form-control @error('username') is-invalid @enderror"
                                id="username" name="username"
                                value="{{ old('username') }}"
                                placeholder="Enter username" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Enter password" required>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-info text-white btn-login">Login</button>
                        </div>
                    </form>

                    <p class="text-center text-muted small mt-3 mb-0">
                        Forgot your password or don't have an account?
                        Please ask your admin to create or reset one for you.
                    </p>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>