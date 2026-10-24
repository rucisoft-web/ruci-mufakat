<?php

return [
    // Nama produk ditampilkan di seluruh aplikasi.
    'name' => env('APP_NAME', 'Ruci MUFAKAT'),

    // Instalasi lewat browser untuk paket siap-upload cPanel tanpa Terminal.
    'installer' => (bool) env('MUFAKAT_INSTALLER', false),

    // MFA aplikasi autentikator. Untuk demo boleh false.
    'mfa_required' => (bool) env('MUFAKAT_MFA_REQUIRED', false),

    'demo' => [
        // Tombol "Masuk sebagai tamu" di halaman login.
        'guest' => (bool) env('MUFAKAT_DEMO_GUEST', true),
        'password' => env('MUFAKAT_DEMO_PASSWORD', 'Mufakat#2026'),
    ],

    'pdftotext' => env('MUFAKAT_PDFTOTEXT_PATH', '/usr/bin/pdftotext'),
    'max_upload_kb' => (int) env('MUFAKAT_MAX_UPLOAD_KB', 51200),

    'ai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 90),
        // Jumlah halaman sumber maksimal yang dikirim per permintaan (hanya potongan yang diizinkan).
        'max_pages' => (int) env('MUFAKAT_AI_MAX_PAGES', 12),
        'max_chars_per_page' => (int) env('MUFAKAT_AI_MAX_CHARS', 2500),
        // Tanpa kunci API, tampilkan keluaran referensi demo bila tersedia.
        'demo_fallback' => (bool) env('MUFAKAT_AI_DEMO_FALLBACK', true),
    ],
];
