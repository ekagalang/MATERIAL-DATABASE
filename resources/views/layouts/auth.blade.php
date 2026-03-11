<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --auth-brand: #891313;
            --auth-brand-deep: #5f0d0d;
            --auth-brand-soft: #fff2f2;
            --auth-ink: #172033;
            --auth-muted: #64748b;
            --auth-line: #cbd5e1;
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(137, 19, 19, 0.2), transparent 30%),
                radial-gradient(circle at bottom right, rgba(137, 19, 19, 0.12), transparent 32%),
                linear-gradient(135deg, #1a202f 0%, #2a3145 44%, #121927 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .auth-shell {
            width: min(100%, 980px);
            height: 640px;
            min-height: 640px;
            display: grid;
            grid-template-columns: 1.02fr 0.98fr;
            border-radius: 30px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(12px);
        }

        .auth-brand {
            padding: 46px;
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                radial-gradient(circle at top right, rgba(137, 19, 19, 0.18), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0));
        }

        .auth-brand img {
            width: 148px;
        }

        .auth-brand-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255, 242, 242, 0.12);
            color: rgba(255, 242, 242, 0.94);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .auth-brand h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1.02;
            font-weight: 800;
            margin: 22px 0 14px;
        }

        .auth-brand p {
            max-width: 29rem;
            margin: 0;
            color: rgba(248, 250, 252, 0.82);
            line-height: 1.8;
            font-size: 0.95rem;
        }

        .auth-brand-foot {
            color: rgba(248, 250, 252, 0.68);
            font-size: 0.84rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .auth-card {
            padding: 44px 40px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100%;
        }

        .auth-card-inner {
            flex: 0 1 auto;
            width: 100%;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-block: auto;
        }

        .auth-card h2 {
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--auth-ink);
            letter-spacing: -0.03em;
        }

        .auth-card-inner > p {
            color: var(--auth-muted);
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .form-label {
            font-weight: 700;
            color: #334155;
            font-size: 0.86rem;
        }

        .form-control {
            min-height: 48px;
            border-radius: 14px;
            border-color: var(--auth-line);
            padding-inline: 14px;
        }

        .form-control:focus {
            border-color: var(--auth-brand);
            box-shadow: 0 0 0 0.2rem rgba(137, 19, 19, 0.14);
        }

        .btn-auth {
            min-height: 48px;
            border-radius: 14px;
            border: none;
            font-weight: 800;
            background: linear-gradient(135deg, var(--auth-brand), var(--auth-brand-deep));
            box-shadow: 0 14px 26px rgba(137, 19, 19, 0.18);
        }

        .auth-links {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 14px 0 22px;
            font-size: 0.88rem;
        }

        .auth-inline-link,
        .auth-footer a {
            color: var(--auth-brand);
            text-decoration: none;
            font-weight: 700;
        }

        .auth-inline-link:hover,
        .auth-footer a:hover {
            color: var(--auth-brand-deep);
        }

        .auth-footer {
            margin-top: 18px;
            padding-top: 0;
            font-size: 0.92rem;
            color: var(--auth-muted);
        }

        .auth-footer:empty {
            display: none;
        }

        .auth-card .alert {
            border-radius: 14px;
            font-size: 0.88rem;
        }

        @media (max-width: 900px) {
            .auth-shell {
                height: auto;
                min-height: auto;
                grid-template-columns: 1fr;
            }

            .auth-brand,
            .auth-card {
                padding: 34px 30px;
            }

            .auth-card-inner {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <section class="auth-brand">
            <div>
                <img src="/kanggo.png" alt="Kanggo">
                <h1>@yield('auth_brand_title')</h1>
            </div>
            <small class="auth-brand-foot">@yield('auth_brand_footer', 'Copyright © ' . now()->year . ' Kanggo')</small>
        </section>

        <section class="auth-card">
            <div class="auth-card-inner">
                <h2>@yield('auth_card_title')</h2>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @yield('auth_form')
                @yield('auth_footer')
            </div>
        </section>
    </div>
</body>
</html>
