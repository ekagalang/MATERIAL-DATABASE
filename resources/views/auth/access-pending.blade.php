<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Menunggu Persetujuan</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background:
                radial-gradient(circle at top, rgba(16, 185, 129, 0.16), transparent 25%),
                linear-gradient(135deg, #0f172a 0%, #132238 50%, #1e293b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #e2e8f0;
        }

        .pending-card {
            width: min(100%, 680px);
            border-radius: 28px;
            padding: 44px;
            background: rgba(15, 23, 42, 0.72);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(12px);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 14px;
            background: rgba(16, 185, 129, 0.16);
            color: #86efac;
            font-weight: 600;
            font-size: 0.9rem;
        }

        h1 {
            margin: 20px 0 14px;
            font-size: clamp(2rem, 5vw, 2.9rem);
            line-height: 1.08;
            font-weight: 700;
        }

        p {
            color: rgba(226, 232, 240, 0.82);
            line-height: 1.7;
            margin-bottom: 0;
        }

        .pending-actions {
            margin-top: 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-main {
            border-radius: 14px;
            font-weight: 700;
            min-height: 48px;
            padding-inline: 20px;
            background: linear-gradient(135deg, #10b981, #0f766e);
            border: none;
            color: #fff;
        }

        .btn-ghost {
            border-radius: 14px;
            font-weight: 700;
            min-height: 48px;
            padding-inline: 20px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            background: transparent;
            color: #e2e8f0;
        }
    </style>
</head>
<body>
    <section class="pending-card">
        <span class="badge-status">Akun aktif, akses modul belum diberikan</span>
        <h1>Halo, {{ auth()->user()->name }}.</h1>
        <p>
            Akun kamu sudah berhasil dibuat dan bisa login, tetapi belum memiliki role atau permission untuk membuka modul aplikasi.
            Hubungi administrator agar akun ini diberi akses yang sesuai.
        </p>

        <div class="pending-actions">
            <a href="{{ route('login') }}" class="btn btn-main">Kembali ke Login</a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost">Logout</button>
            </form>
        </div>
    </section>
</body>
</html>
