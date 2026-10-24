@if (\App\Models\Company::where('is_demo', true)->exists())
<div class="mf-demo-banner" role="note">
    <span class="mf-demo-tag">DATA DEMO</span>
    <span>Profil dan angka keuangan PT Kimia Farma Tbk mengacu pada Laporan Tahunan 2025 (ditandai <b>Fakta laporan</b>). Rapat, keputusan, pendapat, benturan kepentingan, tugas, dan keterlambatan adalah <b>simulasi</b> dengan alias "Demo …", bukan tindakan pejabat sebenarnya.
    @if (auth()->user()?->isDemoGuest())
        Anda masuk sebagai <b>tamu (hanya lihat)</b>.
    @endif
    <a href="{{ \App\Filament\Pages\PanduanDemo::getUrl() }}">Panduan demo</a></span>
</div>
@endif
