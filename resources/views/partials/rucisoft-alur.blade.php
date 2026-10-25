{{--
    Blok halaman awal yang seragam untuk semua produk Rucisoft:
    masalah → penyebab → solusi → cara memperoleh (jalur Rucisoft Academy sampai pendampingan).
    Isi khusus produk ada di partials/rucisoft-alur-isi.php; berkas ini sama persis di setiap aplikasi.
--}}
@php
    $alur = require resource_path('views/partials/rucisoft-alur-isi.php');
    $situs = 'https://rucisoft.id';
    $produk = $situs.'/solusi/'.$alur['slug'];
    $academy = $situs.'/academy?produk='.$alur['slug'];
    $langkah = fn (string $tab) => $produk.'?langkah='.$tab.'#mulai';
    $wa = 'https://wa.me/6281199892939?text='.rawurlencode('Halo Rucisoft, saya tertarik dengan '.$alur['nama'].'.');
    $jalur = [
        ['Pahami masalahnya', 'Webinar gratis dan materi edukasi dengan studi kasus nyata.', 'Ikuti webinar', $academy],
        ['Petakan kebutuhan', 'Asesmen singkat: masalah, dampak, pengguna, dan kesiapan organisasi.', 'Isi asesmen', $langkah('asesmen')],
        ['Praktikkan', 'Workshop praktik berbayar. Peserta membawa pulang hasil kerjanya.', 'Lihat workshop', $academy],
        ['Pastikan', 'Klinik kebutuhan dan demo 30-45 menit bersama sponsor dan PIC.', 'Jadwalkan demo', $langkah('demo')],
        ['Buktikan', 'Pilot terbatas dan didampingi, dengan ukuran keberhasilan yang disepakati di awal.', 'Ajukan pilot', $langkah('pilot')],
        ['Terapkan', 'Proposal transparan, implementasi, pelatihan, pendampingan, dan perpanjangan.', 'Minta proposal', $langkah('proposal')],
    ];
@endphp
<style>
    .rsa { --rsa-a: {{ $alur['aksen'] }}; --rsa-a2: {{ $alur['aksen_2'] }}; --rsa-ink: #0f1f33; --rsa-tx: #3b4a5c; --rsa-mu: #6b7a8c; --rsa-ln: #e2e8f0; --rsa-bg: #ffffff; --rsa-soft: #f5f7fa;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: var(--rsa-tx); background: var(--rsa-bg); line-height: 1.6; }
    .rsa *, .rsa *::before, .rsa *::after { box-sizing: border-box; }
    .rsa-sec { padding: 64px 0; }
    .rsa-sec.soft { background: var(--rsa-soft); }
    .rsa-wrap { max-width: 1140px; margin: 0 auto; padding: 0 20px; }
    .rsa-eye { display: inline-flex; align-items: center; gap: 8px; font-size: .74rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--rsa-a); margin: 0 0 10px; }
    .rsa-eye b { display: inline-grid; place-items: center; width: 22px; height: 22px; border-radius: 999px; background: var(--rsa-a); color: #fff; font-size: .72rem; letter-spacing: 0; }
    .rsa h2, .rsa h3 { font-family: inherit; font-weight: 700; }
    .rsa h2 { margin: 0 0 10px; color: var(--rsa-ink); font-size: clamp(1.5rem, 2.6vw, 2.05rem); line-height: 1.25; letter-spacing: -.02em; max-width: 46rem; }
    .rsa-lead { margin: 0 0 28px; max-width: 46rem; font-size: 1.02rem; color: var(--rsa-tx); }
    .rsa-grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); }
    .rsa-card { background: var(--rsa-bg); border: 1px solid var(--rsa-ln); border-radius: 14px; padding: 20px; }
    .rsa-sec.soft .rsa-card { background: #fff; }
    .rsa-card h3 { margin: 0 0 6px; font-size: 1.02rem; color: var(--rsa-ink); letter-spacing: -.01em; }
    .rsa-card p { margin: 0; font-size: .93rem; }
    .rsa-card.masalah { border-top: 3px solid #d64545; }
    .rsa-card.penyebab { border-top: 3px solid #e0a030; }
    .rsa-card.solusi { border-top: 3px solid var(--rsa-a); }
    .rsa-card small { display: block; margin-top: 10px; font-size: .78rem; color: var(--rsa-mu); }
    .rsa-flow { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(165px, 1fr)); counter-reset: rsa; }
    .rsa-step { position: relative; display: flex; flex-direction: column; background: #fff; border: 1px solid var(--rsa-ln); border-radius: 14px; padding: 18px 16px 16px; }
    .rsa-step::before { counter-increment: rsa; content: counter(rsa, decimal-leading-zero); font-size: .75rem; font-weight: 800; color: var(--rsa-a); letter-spacing: .06em; }
    .rsa-step h3 { margin: 6px 0 6px; font-size: .98rem; color: var(--rsa-ink); }
    .rsa-step p { margin: 0 0 14px; font-size: .86rem; flex: 1; }
    .rsa-step a { font-size: .85rem; font-weight: 700; color: var(--rsa-a); text-decoration: none; }
    .rsa-step a:hover { text-decoration: underline; }
    .rsa-cta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 28px; align-items: center; }
    .rsa-btn { display: inline-flex; align-items: center; gap: 6px; padding: 11px 18px; border-radius: 10px; font-weight: 700; font-size: .92rem; text-decoration: none; border: 1px solid var(--rsa-a); color: var(--rsa-a); background: #fff; }
    .rsa-btn.utama { background: var(--rsa-a); color: #fff; }
    .rsa-btn.wa { border-color: #1f9d55; color: #1f9d55; }
    .rsa-btn:hover { filter: brightness(.95); }
    .rsa-note { margin: 16px 0 0; font-size: .84rem; color: var(--rsa-mu); }
    .rsa-note a { color: var(--rsa-a); }
    @media (max-width: 640px) { .rsa-sec { padding: 44px 0; } }
</style>
<div class="rsa" id="alur-rucisoft">
    <section class="rsa-sec">
        <div class="rsa-wrap">
            <p class="rsa-eye"><b>1</b> Masalah yang sering dihadapi</p>
            <h2>{{ $alur['masalah_judul'] }}</h2>
            <p class="rsa-lead">{{ $alur['masalah_lead'] }}</p>
            <div class="rsa-grid">
                @foreach ($alur['masalah'] as [$judul, $isi])
                    <article class="rsa-card masalah"><h3>{{ $judul }}</h3><p>{{ $isi }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="rsa-sec soft">
        <div class="rsa-wrap">
            <p class="rsa-eye"><b>2</b> Mengapa masalah ini terjadi</p>
            <h2>{{ $alur['penyebab_judul'] }}</h2>
            <p class="rsa-lead">{{ $alur['penyebab_lead'] }}</p>
            <div class="rsa-grid">
                @foreach ($alur['penyebab'] as [$judul, $isi])
                    <article class="rsa-card penyebab"><h3>{{ $judul }}</h3><p>{{ $isi }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="rsa-sec">
        <div class="rsa-wrap">
            <p class="rsa-eye"><b>3</b> Solusi Rucisoft</p>
            <h2>{{ $alur['solusi_judul'] }}</h2>
            <p class="rsa-lead">{{ $alur['solusi_lead'] }}</p>
            <div class="rsa-grid">
                @foreach ($alur['solusi'] as [$judul, $isi, $menjawab])
                    <article class="rsa-card solusi"><h3>{{ $judul }}</h3><p>{{ $isi }}</p><small>Menjawab: {{ $menjawab }}</small></article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="rsa-sec soft">
        <div class="rsa-wrap">
            <p class="rsa-eye"><b>4</b> Cara memperoleh {{ $alur['nama'] }}</p>
            <h2>Mulai dari tahap mana pun yang sesuai dengan kesiapan Anda.</h2>
            <p class="rsa-lead">Rucisoft tidak meminta Anda langsung membeli. Pahami masalahnya lewat Rucisoft Academy, buktikan manfaatnya lewat pilot, lalu putuskan dengan proposal yang jelas. Sudah yakin? Langsung ke demo atau proposal.</p>
            <div class="rsa-flow">
                @foreach ($jalur as [$judul, $isi, $aksi, $url])
                    <div class="rsa-step"><h3>{{ $judul }}</h3><p>{{ $isi }}</p><a href="{{ $url }}">{{ $aksi }} &rarr;</a></div>
                @endforeach
            </div>
            <div class="rsa-cta">
                <a class="rsa-btn utama" href="{{ $langkah('demo') }}">Jadwalkan klinik &amp; demo</a>
                <a class="rsa-btn" href="{{ $academy }}">Ikuti webinar gratis</a>
                <a class="rsa-btn wa" href="{{ $wa }}">Tanya via WhatsApp</a>
                @if (! empty($alur['coba_url']))
                    <a class="rsa-btn" href="{{ $alur['coba_url'] }}">Coba data contoh</a>
                @endif
            </div>
            <p class="rsa-note">Program, jadwal, dan formulir dikelola di <a href="{{ $produk }}">rucisoft.id</a>. Satu organisasi cukup satu pendaftaran.</p>
        </div>
    </section>
</div>
