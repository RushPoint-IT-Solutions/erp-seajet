<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | {{ config('erp.name', config('app.name')) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --accent: #2563eb;
            --accent-dark: #1e40af;
            --ink: #172033;
            --muted: #667085;
            --panel: #ffffff;
            --line: #d7deea;
            --field: #f8fafc;
            --navy: #182235;
            --teal: #0f766e;
        }
        * {
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            margin: 0;
            background: #edf2f7;
            color: var(--ink);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }
        .login-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px;
            background:
                linear-gradient(135deg, rgba(24, 34, 53, .92), rgba(24, 34, 53, .72)),
                url("{{ asset('images/image.png') }}") left 7vw top 8vh / 210px auto no-repeat,
                #edf2f7;
        }
        .login-frame {
            width: min(100%, 980px);
            min-height: 620px;
            display: grid;
            grid-template-columns: minmax(0, .95fr) minmax(360px, 1.05fr);
            background: var(--panel);
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 28px 80px rgba(9, 18, 34, .28);
        }
        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 36px;
            color: #fff;
            background:
                linear-gradient(rgba(24, 34, 53, .64), rgba(24, 34, 53, .86)),
                url("{{ asset('images/image.png') }}") center 28% / 310px auto no-repeat,
                var(--navy);
        }
        .brand-panel:before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, .06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .06) 1px, transparent 1px);
            background-size: 42px 42px;
            opacity: .45;
        }
        .brand-panel > * {
            position: relative;
        }
        .brand-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 7px 10px;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
            background: rgba(255, 255, 255, .1);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        .brand-mark {
            width: 54px;
            height: 54px;
            border-radius: 8px;
            background: #fff;
            display: inline-grid;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .5);
            box-shadow: 0 12px 30px rgba(0, 0, 0, .2);
        }
        .brand-mark img {
            max-width: 42px;
            max-height: 42px;
        }
        .brand-title {
            max-width: 360px;
        }
        .brand-title h1 {
            font-size: 36px;
            line-height: 1.08;
            font-weight: 800;
            margin: 18px 0 12px;
        }
        .brand-title p {
            color: rgba(255, 255, 255, .78);
            line-height: 1.6;
            margin: 0;
        }
        .status-strip {
            display: grid;
            gap: 10px;
        }
        .status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 14px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 8px;
            background: rgba(255, 255, 255, .1);
            backdrop-filter: blur(8px);
        }
        .status-item small {
            color: rgba(255, 255, 255, .72);
        }
        .status-pill {
            flex: 0 0 auto;
            padding: 4px 9px;
            border-radius: 999px;
            background: rgba(20, 184, 166, .18);
            color: #99f6e4;
            font-size: 12px;
            font-weight: 700;
        }
        .login-panel {
            display: flex;
            align-items: center;
            padding: 42px;
            background:
                linear-gradient(180deg, #ffffff, #f8fafc);
        }
        .login-content {
            width: 100%;
            max-width: 410px;
            margin: 0 auto;
        }
        .mobile-brand {
            display: none;
        }
        .form-label {
            color: #344054;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap .bi {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #98a2b3;
            pointer-events: none;
        }
        .form-control {
            min-height: 48px;
            border-radius: 8px;
            border-color: var(--line);
            background: var(--field);
            padding-left: 42px;
            color: var(--ink);
            box-shadow: none;
        }
        .form-control:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }
        .form-check-input {
            border-color: #98a2b3;
        }
        .form-check-input:checked {
            background-color: var(--accent);
            border-color: var(--accent);
        }
        .btn-primary {
            min-height: 48px;
            border-radius: 8px;
            border-color: var(--accent);
            background: var(--accent);
            font-weight: 700;
            box-shadow: 0 12px 26px rgba(37, 99, 235, .25);
        }
        .btn-primary:hover,
        .btn-primary:focus {
            border-color: var(--accent-dark);
            background: var(--accent-dark);
        }
        .login-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: var(--muted);
            font-size: 12px;
            margin-top: 18px;
        }
        .alert {
            border-radius: 8px;
        }
        @media (max-width: 860px) {
            .login-shell {
                padding: 18px;
                background: #edf2f7;
            }
            .login-frame {
                min-height: auto;
                grid-template-columns: 1fr;
            }
            .brand-panel {
                display: none;
            }
            .login-panel {
                padding: 28px;
            }
            .mobile-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 28px;
            }
            .mobile-brand .brand-mark {
                width: 48px;
                height: 48px;
                background: #f3f6fb;
                border-color: var(--line);
                box-shadow: none;
            }
            .mobile-brand .brand-mark img {
                max-width: 36px;
                max-height: 36px;
            }
        }
        @media (max-width: 460px) {
            .login-panel {
                padding: 22px;
            }
            .login-meta {
                display: block;
            }
            .login-meta span {
                display: block;
                margin-top: 6px;
            }
        }
    </style>
</head>
<body>
<main class="login-shell">
    <section class="login-frame">
        <aside class="brand-panel">
            <div>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="brand-mark">
                        <img src="{{ asset('images/image.png') }}" alt="{{ config('erp.name', config('app.name')) }} logo">
                    </span>
                    <span class="brand-kicker"><i class="bi bi-shield-lock"></i> Secure ERP</span>
                </div>
                <div class="brand-title">
                    <h1>{{ config('erp.name', config('app.name')) }}</h1>
                    <p>Finance, billing, collections, banking, and reporting in one controlled workspace.</p>
                </div>
            </div>
            <div class="status-strip">
                <div class="status-item">
                    <div>
                        <div class="fw-semibold">SeaJet Holdings</div>
                        <small>Primary company workspace</small>
                    </div>
                    <span class="status-pill">Ready</span>
                </div>
                <div class="status-item">
                    <div>
                        <div class="fw-semibold">Access Control</div>
                        <small>Role-based permissions active</small>
                    </div>
                    <span class="status-pill">Live</span>
                </div>
            </div>
        </aside>

        <section class="login-panel">
            <div class="login-content">
                <div class="mobile-brand">
                    <span class="brand-mark">
                        <img src="{{ asset('images/image.png') }}" alt="{{ config('erp.name', config('app.name')) }} logo">
                    </span>
                    <div>
                        <div class="fw-bold">{{ config('erp.name', config('app.name')) }}</div>
                        <small class="text-muted">Secure ERP access</small>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-muted fw-semibold small text-uppercase mb-2">Welcome Back</div>
                    <h2 class="h3 fw-bold mb-1">Sign in to your account</h2>
                    <p class="text-muted mb-0">Use your ERP credentials to continue.</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form method="post" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <div class="input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" required autofocus>
                        </div>
                        @if($errors->has('email'))
                            <div class="invalid-feedback d-block">{{ $errors->first('email') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock"></i>
                            <input id="password" type="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" required>
                        </div>
                        @if($errors->has('password'))
                            <div class="invalid-feedback d-block">{{ $errors->first('password') }}</div>
                        @endif
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input id="remember" class="form-check-input" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                    </button>
                </form>

                <div class="login-meta">
                    <span>{{ config('erp.name', config('app.name')) }}</span>
                    <span>{{ now()->format('F j, Y') }}</span>
                </div>
            </div>
        </section>
    </section>
</main>
</body>
</html>
