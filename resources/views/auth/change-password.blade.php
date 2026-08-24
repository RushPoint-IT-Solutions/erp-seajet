<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password | SeaJet ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width: 560px">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-2">Set a secure password</h1>
            <p class="text-muted">The account is using a temporary password. Use at least 12 characters.</p>
            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="post" action="{{ route('password.update') }}">
                {{ csrf_field() }}
                <div class="mb-3">
                    <label class="form-label">Current password</label>
                    <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <input type="password" name="password" class="form-control" required minlength="12" autocomplete="new-password">
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm new password</label>
                    <input type="password" name="password_confirmation" class="form-control" required minlength="12" autocomplete="new-password">
                </div>
                <button class="btn btn-primary w-100">Change password and continue</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
