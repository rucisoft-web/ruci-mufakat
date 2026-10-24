@php
    use App\Filament\Resources\Meetings\MeetingResource;
    use App\Filament\Resources\Tasks\TaskResource;
    use App\Filament\Resources\InformationRequests\InformationRequestResource;
    use App\Support\Fmt;
    $onTime = $materials->where('on_time', true)->count();
@endphp
<x-filament-panels::page>
<div class="mf-dash">
    <div class="mf-head">
        <div>
            <div class="mf-eyebrow">Dasbor pimpinan · {{ now()->translatedFormat('l, d F Y') }}</div>
            <h1>PT Kimia Farma Tbk — ringkasan tata kelola</h1>
            <p>Angka keuangan dari Laporan Tahunan 2025 (fakta laporan); kegiatan rapat dan tindak lanjut adalah simulasi. <a href="{{ \App\Filament\Pages\KinerjaKeuangan::getUrl() }}">Rincian rasio →</a></p>
        </div>
    </div>

    <div class="mf-kpis">
        <a class="mf-kpi" href="{{ MeetingResource::getUrl() }}"><span>Rapat mendatang</span><strong>{{ $upcoming->count() }}</strong><small>{{ $upcoming->first() ? 'berikutnya '.$upcoming->first()->scheduled_at->translatedFormat('d M') : 'tidak ada' }}</small></a>
        <a class="mf-kpi {{ $materials->isNotEmpty() && $onTime / $materials->count() < .8 ? 'warn' : 'good' }}" href="{{ \App\Filament\Pages\KepatuhanRapat::getUrl() }}"><span>Bahan tepat waktu</span><strong>{{ $materials->isEmpty() ? '—' : round($onTime / $materials->count() * 100).'%' }}</strong><small>{{ $onTime }} dari {{ $materials->count() }} rapat ≥ H-5</small></a>
        <a class="mf-kpi {{ ($sla['Terbuka, lewat SLA'] ?? 0) ? 'bad' : 'good' }}" href="{{ InformationRequestResource::getUrl() }}"><span>Pertanyaan lewat SLA</span><strong>{{ $sla['Terbuka, lewat SLA'] ?? 0 }}</strong><small>{{ array_sum($sla) }} permintaan informasi</small></a>
        <a class="mf-kpi {{ $tasks['overdue'] ? 'bad' : 'good' }}" href="{{ TaskResource::getUrl() }}"><span>Tugas terlambat</span><strong>{{ $tasks['overdue'] }}</strong><small>{{ $tasks['done'] }}/{{ $tasks['total'] }} selesai terverifikasi</small></a>
        <a class="mf-kpi {{ $attendancePct !== null && $attendancePct < 85 ? 'warn' : '' }}" href="{{ \App\Filament\Pages\KepatuhanRapat::getUrl() }}"><span>Kehadiran aktual</span><strong>{{ $attendancePct === null ? '—' : Fmt::num($attendancePct, 1).'%' }}</strong><small>rata-rata anggota, 2026</small></a>
        @isset($growth['penjualan'])
            <div class="mf-kpi warn"><span>Penjualan neto 2025</span><strong>{{ Fmt::rpJuta($growth['penjualan']['to']) }}</strong><small>{{ Fmt::pct($growth['penjualan']['pct']) }} vs 2024 · hlm. 16</small></div>
            <div class="mf-kpi good"><span>Rugi tahun berjalan</span><strong>{{ Fmt::rpJuta($growth['laba_bersih']['to']) }}</strong><small>rugi turun {{ Fmt::num(abs($growth['laba_bersih']['pct']), 2) }}% · hlm. 16</small></div>
        @endisset
    </div>

    <div class="mf-grid-3">
        <section class="mf-panel mf-span-2">
            <h3>Hal yang perlu perhatian <small>dihitung aplikasi dari data saat ini</small></h3>
            <ul class="mf-insights">
                @foreach ($insights as [$tone, $text])<li class="{{ $tone }}">{{ $text }}</li>@endforeach
            </ul>
        </section>
        <section class="mf-panel">
            <h3>Rapat mendatang <small><a href="{{ MeetingResource::getUrl() }}">semua</a></small></h3>
            <div class="mf-list">
                @forelse ($upcoming as $m)
                    @php($lead = $books->leadDays($m))
                    <a class="mf-row" href="{{ MeetingResource::getUrl('view', ['record' => $m]) }}">
                        <div class="mf-date {{ $m->scheduled_at->diffInDays(now(), true) <= 3 ? 'soon' : '' }}">{{ $m->scheduled_at->translatedFormat('M') }}<b>{{ $m->scheduled_at->format('d') }}</b></div>
                        <div class="body"><b>{{ $m->title }}</b><small>{{ $m->organ->short_name }} · {{ $m->scheduled_at->format('H:i') }} · RSVP {{ $m->participants->where('has_vote', true)->where('rsvp.value', 'hadir')->count() }}/{{ $m->participants->where('has_vote', true)->count() }}</small></div>
                        @if ($lead === null)<span class="mf-chip warn">Bahan belum</span>
                        @elseif ($m->boardBooks->count() > 1)<span class="mf-chip info">BB v{{ $m->boardBooks->max('version_no') }}</span>
                        @else<span class="mf-chip {{ $lead >= 5 ? 'good' : 'bad' }}">H-{{ floor($lead) }}</span>@endif
                    </a>
                @empty
                    <p class="mf-empty">Tidak ada rapat terjadwal.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mf-grid-2">
        @livewire(\App\Filament\Widgets\FinanceChart::class)
        @livewire(\App\Filament\Widgets\QuarterlyChart::class)
    </div>
    <div class="mf-grid-3">
        @livewire(\App\Filament\Widgets\SegmentChart::class)
        @livewire(\App\Filament\Widgets\TaskStatusChart::class)
        @livewire(\App\Filament\Widgets\InfoSlaChart::class)
    </div>
    <div class="mf-grid-2">
        @livewire(\App\Filament\Widgets\MeetingFrequencyChart::class)
        @livewire(\App\Filament\Widgets\AttendanceChart::class)
    </div>

    <div class="mf-grid-3">
        <section class="mf-panel">
            <h3>Rasio (hitung aplikasi) <small>2024 → 2025</small></h3>
            <table class="mf-compliance">
                @foreach ($ratios->only(['margin_bruto', 'margin_usaha', 'margin_bersih', 'rasio_lancar', 'der', 'roe']) as $r)
                    <tr><td>{{ $r['label'] }}</td><td class="num">{{ Fmt::num($r['values']['2024'], 2) }}</td><td class="num"><b>{{ Fmt::num($r['values']['2025'], 2) }}</b> <span class="mf-muted">{{ $r['unit'] === '%' ? '%' : 'x' }}</span></td></tr>
                @endforeach
            </table>
            <p class="mf-muted" style="margin-top:8px">Rumus dan sumber ada di halaman Kinerja Keuangan. AI tidak menghitung rasio.</p>
        </section>
        <section class="mf-panel">
            <h3>Peta risiko <small>kemungkinan × dampak</small></h3>
            <div class="mf-heat">
                @for ($l = 5; $l >= 1; $l--)
                    <div class="axis">{{ $l }}</div>
                    @for ($i = 1; $i <= 5; $i++)
                        @php($score = $l * $i)
                        <div class="cell l{{ $score >= 20 ? 5 : ($score >= 12 ? 4 : ($score >= 8 ? 3 : ($score >= 4 ? 2 : 1))) }}">{{ $heat[$l][$i] ?? '' }}</div>
                    @endfor
                @endfor
                <div></div>@for ($i = 1; $i <= 5; $i++)<div class="axis">{{ $i }}</div>@endfor
            </div>
            <p class="mf-muted" style="margin-top:6px">Sumbu tegak: kemungkinan · sumbu datar: dampak</p>
        </section>
        <section class="mf-panel">
            <h3>Risiko utama <small>simulasi</small></h3>
            @foreach ($risks as $r)
                <div class="mf-row" style="grid-template-columns: 1fr auto">
                    <div class="body"><b>{{ $r->code }} · {{ $r->title }}</b><small>KRI {{ $r->kri_name }}: {{ $r->kri_value }} (batas {{ $r->kri_threshold }})</small></div>
                    <span class="mf-chip {{ ['merah' => 'bad', 'kuning' => 'warn', 'hijau' => 'good'][$r->kri_status] ?? '' }}">{{ $r->likelihood * $r->impact }}</span>
                </div>
            @endforeach
        </section>
    </div>
</div>
</x-filament-panels::page>
