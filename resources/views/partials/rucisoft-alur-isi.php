<?php

// Isi blok halaman awal seragam (partials/rucisoft-alur.blade.php) khusus produk ini.
return [
    'slug' => 'ruci-mufakat',
    'nama' => 'Ruci MUFAKAT',
    'aksen' => '#112647',
    'aksen_2' => '#b8892f',
    'coba_url' => '',
    'masalah_judul' => 'Keputusan dewan sering sulit ditelusuri dan tindak lanjutnya sulit dibuktikan.',
    'masalah_lead' => 'Rapat Direksi dan Dewan Komisaris menghasilkan keputusan penting, tetapi jejaknya tersebar di surel, pesan singkat, dan berkas PDF.',
    'masalah' => [
        ['Bahan rapat bercampur', 'PDF dikirim lewat surel atau pesan singkat; versi bercampur dan perubahan tidak terlihat.'],
        ['Kuorum dan keputusan tidak jelas', 'RSVP dianggap kehadiran, dan keputusan rapat gabungan tercampur antarorgan.'],
        ['Tindak lanjut tanpa bukti', 'Tugas dinyatakan 100% selesai tanpa bukti yang diverifikasi.'],
        ['Pertanyaan pengawasan hilang', 'Permintaan informasi Komisaris tenggelam di percakapan tanpa PIC dan batas waktu.'],
    ],
    'penyebab_judul' => 'Tata kelola rapat masih bergantung pada dokumen lepas dan ingatan.',
    'penyebab_lead' => 'Tidak ada satu jejak yang menghubungkan sumber, agenda, keputusan, tugas, dan bukti.',
    'penyebab' => [
        ['Tidak ada board book berversi', 'Bahan rapat tidak punya riwayat versi dan daftar perubahan.'],
        ['Organ tidak dipisahkan', 'Fungsi pengurusan dan pengawasan tidak dicatat terpisah.'],
        ['Tidak ada verifikator', 'Penyelesaian tugas tidak melalui pemeriksaan bukti.'],
        ['Risalah bisa diubah diam-diam', 'Tidak ada penguncian dan addendum yang tertaut.'],
    ],
    'solusi_judul' => 'Ruci MUFAKAT: dari bahan rapat sampai bukti tindak lanjut, dalam satu jejak.',
    'solusi_lead' => 'Ruang kerja Direksi dan Dewan Komisaris dengan board book berversi, kuorum aktual, keputusan per organ, risalah terkunci, dan asisten AI yang menunjuk sumbernya.',
    'solusi' => [
        ['Board book berversi', 'Anggota tahu persis apa yang berubah setelah distribusi.', 'Bahan rapat bercampur'],
        ['Kuorum aktual & keputusan per organ', 'Kehadiran dan suara dicatat per organ, per agenda.', 'Kuorum dan keputusan tidak jelas'],
        ['Tindak lanjut berbukti', 'Tugas selesai hanya setelah verifikator menerima bukti.', 'Tindak lanjut tanpa bukti'],
        ['Permintaan informasi ber-SLA', 'Pertanyaan Komisaris punya PIC, batas waktu, dan penutupan oleh penanya.', 'Pertanyaan pengawasan hilang'],
    ],
];
