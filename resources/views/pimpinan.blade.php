<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ruci MUFAKAT untuk Komisaris dan Direksi</title>
    <meta name="description" content="Ruang kerja digital Dewan Komisaris dan Direksi: board book berversi, kuorum dari kehadiran aktual, keputusan per organ, risalah dengan addendum, tindak lanjut berbukti, dan asisten AI dengan rujukan halaman.">
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/mufakat.css') }}?v={{ @filemtime(public_path('css/mufakat.css')) }}">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--mf-paper); color: var(--mf-text); font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif; }
        a { color: inherit; text-decoration: none; }
        .top { background: var(--mf-ink); color: #eef3fa; }
        .top .wrap { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 16px; max-width: 1180px; margin: 0 auto; flex-wrap: wrap; }
        .top .mf-auth-brand { font-size: 1.05rem; }
        .top nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 9px 14px; border-radius: 10px; font-weight: 600; font-size: .88rem; }
        .btn.gold { background: #e3c27a; color: #2b1d05; }
        .btn.line { border: 1px solid #ffffff44; }
        main { max-width: 1180px; margin: 0 auto; padding: 24px 16px 48px; }
        footer { text-align: center; color: var(--mf-muted); font-size: .78rem; padding: 0 16px 32px; }
        h3 { margin: 0 0 12px; }
        ul { margin: 0; }
    </style>
</head>
<body>
<header class="top">
    <div class="wrap">
        <a class="mf-auth-brand" href="{{ url('/') }}"><img src="{{ asset('favicon.svg') }}" alt="" width="32" height="32"><span>Ruci <b>MUFAKAT</b></span></a>
        <nav>
            @if ($guestUrl)<a class="btn gold" href="{{ $guestUrl }}">Lihat demo sebagai tamu</a>@endif
            <a class="btn line" href="{{ url('/app/login') }}">Masuk</a>
        </nav>
    </div>
</header>
<main>
    @if ($demo)
        <div class="mf-demo-banner" style="margin-bottom:16px"><span class="mf-demo-tag">DATA DEMO</span><span>Demo memakai profil dan angka Laporan Tahunan 2025 PT Kimia Farma Tbk. Rapat, keputusan, dan tindak lanjut di dalamnya adalah simulasi dengan alias "Demo …".</span></div>
    @endif
    @include('partials.why')
</main>
<footer>Ruci MUFAKAT · Rucisoft · Laravel 13 + MySQL · siap cPanel</footer>
</body>
</html>
