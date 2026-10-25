@php($guestUrl = \App\Http\Controllers\DemoController::guestUrl())
<div class="mf-login-links">
    @if ($guestUrl)
        <a class="mf-login-guest" href="{{ $guestUrl }}">Masuk sebagai tamu (lihat demo Kimia Farma)</a>
        <p>Tanpa akun. Tamu hanya dapat melihat data demo, tidak dapat mengubahnya.</p>
    @endif
    <a class="mf-login-why" href="{{ route('pimpinan') }}">Ruci MUFAKAT untuk Komisaris dan Direksi &rarr;</a>
    <p>Produk <a href="https://rucisoft.id" style="color:inherit;text-decoration:underline">Rucisoft</a> &middot; <a href="https://rucisoft.id" style="color:inherit">rucisoft.id</a></p>
    @if ($guestUrl)
        <div class="mf-login-accounts">
            Akun demo per peran (kata sandi <code>{{ config('mufakat.demo.password') }}</code>):
            <code>komut@mufakat.demo</code>, <code>dirut@mufakat.demo</code>, <code>sekper@mufakat.demo</code>,
            <code>sekdekom@mufakat.demo</code>, <code>vp.akuntansi@mufakat.demo</code>, <code>konsultan@mufakat.demo</code>.
        </div>
    @endif
</div>
