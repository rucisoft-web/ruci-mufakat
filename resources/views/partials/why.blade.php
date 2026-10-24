{{-- Isi halaman manfaat bagi pimpinan; dipakai di panel dan halaman publik /untuk-pimpinan. --}}
<div class="mf-dash mf-why">
    <section class="mf-panel" style="padding:28px 30px">
        <div class="mf-eyebrow">Ruci MUFAKAT · ruang kerja Dewan Komisaris dan Direksi</div>
        <h2>Dari undangan rapat sampai bukti tindak lanjut, dalam satu jejak yang dapat dipertanggungjawabkan.</h2>
        <p class="lead">Nama MUFAKAT diambil dari tradisi <i>musyawarah untuk mufakat</i>: keputusan dewan lahir dari pembahasan yang terdokumentasi.
            Aplikasi ini menyatukan jadwal, board book berversi, ruang baca, kuorum, keputusan per organ, risalah, dan tindak lanjut berbukti,
            ditambah asisten AI yang selalu menunjuk dokumen dan halaman sumber.</p>
    </section>

    <div class="mf-cycle">
        @foreach ([['Rencana', 'kalender & agenda'], ['Bahan', 'board book berversi'], ['Baca', 'ruang baca & anotasi'], ['Rapat', 'hadir aktual & kuorum'], ['Putuskan', 'suara per organ'], ['Risalah', 'tanda tangan & addendum'], ['Tindak lanjut', 'bukti diverifikasi']] as $i => [$t, $s])
            <div><b>{{ $i + 1 }}</b><span>{{ $t }}</span><small>{{ $s }}</small></div>
        @endforeach
    </div>

    <div class="mf-grid-3">
        @foreach ([
            ['Komisaris Utama & Komisaris', 'Pengawasan yang tajam tanpa tenggelam di bahan', 'Ringkasan sebelum rapat dan pertanyaan kritis dengan rujukan halaman, peta risiko, rasio yang dihitung aplikasi, dan permintaan informasi ber-SLA kepada manajemen. Catatan pribadi tetap pribadi.'],
            ['Direktur Utama & Direksi', 'Keputusan cepat, kewenangan jelas', 'Kertas usulan yang lengkap (alternatif, risiko, dampak keuangan, dasar kewenangan), persetujuan Dewan Komisaris yang tercatat terpisah, dan tindak lanjut yang selesai hanya bila buktinya diterima.'],
            ['Sekretaris Perusahaan & Sekretaris Dekom', 'Kepatuhan yang terlihat setiap hari', 'Frekuensi rapat, ketepatan bahan H-5, kehadiran aktual, dan masa jabatan dipantau terhadap POJK 33/2014. Risalah final tidak bisa ditimpa; koreksi melalui addendum.'],
            ['Komite Audit & komite lain', 'Rekomendasi yang bisa ditelusuri', 'Rapat komite di bawah Dewan Komisaris dengan akses terpisah dari Direksi, rekomendasi tercatat, dan tindak lanjut auditor yang dapat dibuka kembali bila temuan berulang.'],
            ['Auditor & pemeriksa', 'Rantai bukti lengkap', 'Sumber → agenda → keputusan → tugas → bukti → verifikasi, ditambah jejak audit setiap akses, termasuk percobaan akses yang ditolak.'],
            ['Pemegang saham & regulator', 'Tata kelola yang dapat dibuktikan', 'Setiap versi board book dan risalah memiliki sidik SHA-256. Laporan pengawasan disusun dari data yang sama dengan yang dibahas di rapat.'],
        ] as [$who, $title, $body])
            <div class="mf-panel card"><div class="who">{{ $who }}</div><h4>{{ $title }}</h4><p>{{ $body }}</p></div>
        @endforeach
    </div>

    <div class="mf-grid-2">
        <section class="mf-panel">
            <h3>Yang berubah dibanding cara manual</h3>
            <div class="mf-table-wrap"><table class="mf-compare">
                <tr><th>Selama ini</th><th>Dengan Ruci MUFAKAT</th></tr>
                <tr><td>PDF dikirim lewat surel atau pesan singkat; versi bercampur.</td><td>Board book berversi dengan daftar perubahan; anggota tahu persis apa yang berubah setelah distribusi.</td></tr>
                <tr><td>RSVP dianggap kehadiran.</td><td>Kuorum dihitung dari kehadiran aktual, per organ, per agenda (benturan kepentingan dikecualikan).</td></tr>
                <tr><td>Keputusan rapat gabungan tercampur.</td><td>Keputusan Direksi, persetujuan dan nasihat Dewan Komisaris dicatat terpisah dengan suaranya masing-masing.</td></tr>
                <tr><td>Risalah direvisi diam-diam.</td><td>Risalah final terkunci; koreksi dibuat sebagai addendum yang tertaut.</td></tr>
                <tr><td>Tugas "100%" tanpa bukti.</td><td>Selesai hanya setelah verifikator menerima bukti; penolakan dan pembukaan kembali tercatat.</td></tr>
                <tr><td>Pertanyaan Komisaris hilang di percakapan.</td><td>Permintaan informasi dengan PIC, SLA, klarifikasi, dan penutupan oleh penanya.</td></tr>
            </table></div>
        </section>
        <section class="mf-panel">
            <h3>Prinsip asisten AI</h3>
            <ul class="mf-insights">
                <li>Setiap jawaban menyebut dokumen, versi, dan halaman. Rujukan yang tidak ada di sumber dibuang aplikasi.</li>
                <li>Fakta, hitungan aplikasi, dan analisis AI dibedakan dengan label.</li>
                <li>Informasi yang belum tersedia disebutkan; bila bukti tidak cukup, AI tidak menebak.</li>
                <li>Hanya bahan yang boleh Anda baca yang dikirim ke AI; dokumen dapat dikecualikan dari AI.</li>
                <li class="warn">AI tidak menentukan kuorum, memberi suara, menandatangani, atau mengesahkan. Seluruh keluaran berstatus draf.</li>
            </ul>
            <div class="mf-quote" style="margin-top:12px">"Keputusan tetap di tangan dewan. Aplikasi memastikan prosesnya tertib, buktinya lengkap, dan jejaknya dapat diperiksa."</div>
        </section>
    </div>

    <section class="mf-panel">
        <h3>Keamanan dan penerapan</h3>
        <div class="mf-grid-3">
            <p class="mf-muted">Akses mengikuti organ, komite, rapat, agenda, masa jabatan, dan klasifikasi dokumen. Administrator teknis tidak otomatis membaca isi dewan. Presenter tamu hanya membuka agendanya sampai batas waktu.</p>
            <p class="mf-muted">Autentikasi dua langkah (aplikasi autentikator) dapat diwajibkan. Setiap pembukaan dokumen, keluaran AI, dan percobaan akses yang ditolak tercatat di jejak audit.</p>
            <p class="mf-muted">Dibangun dengan Laravel 13 dan MySQL; berjalan di hosting cPanel biasa tanpa Redis atau layanan antrean. Asisten AI memakai OpenAI Responses API dengan <code>store: false</code>.</p>
        </div>
    </section>
</div>
