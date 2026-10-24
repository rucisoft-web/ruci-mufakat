@php use App\Filament\Resources\Meetings\MeetingResource; use App\Support\Fmt; @endphp
<x-filament-panels::page>
<div class="mf-dash">
    <section class="mf-panel">
        <h3>Frekuensi rapat {{ now()->year }} <small>laju minimum dihitung proporsional sampai bulan berjalan</small></h3>
        <div class="mf-table-wrap"><table class="mf-compliance">
            <tr><th>Organ</th><th>Dasar</th><th style="text-align:right">Minimum regulasi</th><th style="text-align:right">Kebijakan internal</th><th style="text-align:right">Terlaksana</th><th style="text-align:right">Gagal kuorum</th><th style="text-align:right">Terjadwal</th><th style="text-align:right">Proyeksi</th><th>Status</th></tr>
            @foreach ($frequency as $f)
                <tr>
                    <td><b>{{ $f['organ']->name }}</b></td>
                    <td class="mf-muted" style="max-width:300px">{{ $f['basis'] }}</td>
                    <td class="num">{{ Fmt::num($f['regulatory']) }}/thn</td>
                    <td class="num">{{ Fmt::num($f['internal']) }}/thn</td>
                    <td class="num"><b>{{ $f['held'] }}</b></td>
                    <td class="num">{{ $f['failed'] ?: '—' }}</td>
                    <td class="num">{{ $f['planned'] }}</td>
                    <td class="num">{{ $f['projected'] }}</td>
                    <td>
                        <span class="mf-chip {{ $f['on_track'] ? 'good' : 'bad' }}">{{ $f['on_track'] ? 'Sesuai laju' : 'Di bawah laju ('.$f['expected_to_date'].')' }}</span>
                        @if ($f['projected'] < $f['internal'])<span class="mf-chip warn">Proyeksi < internal</span>@endif
                    </td>
                </tr>
            @endforeach
        </table></div>
    </section>

    <div class="mf-grid-2">
        <section class="mf-panel">
            <h3>Ketepatan bahan rapat <small>board book versi 1 vs aturan H-5</small></h3>
            <div class="mf-table-wrap"><table class="mf-compliance">
                <tr><th>Rapat</th><th style="text-align:right">Terbit</th><th>Versi</th><th>Status</th></tr>
                @foreach ($materials as $m)
                    <tr>
                        <td><a href="{{ MeetingResource::getUrl('view', ['record' => $m['meeting']]) }}"><b>{{ $m['meeting']->title }}</b></a><br><span class="mf-muted">{{ $m['meeting']->organ->short_name }} · {{ $m['meeting']->scheduled_at->translatedFormat('d M Y') }}</span></td>
                        <td class="num">H-{{ Fmt::num($m['lead'], 1) }}</td>
                        <td>{{ $m['versions'] > 1 ? 'v1–v'.$m['versions'] : 'v1' }}</td>
                        <td><span class="mf-chip {{ $m['on_time'] ? 'good' : 'bad' }}">{{ $m['on_time'] ? 'Tepat waktu' : 'Terlambat' }}</span></td>
                    </tr>
                @endforeach
            </table></div>
        </section>
        <section class="mf-panel">
            <h3>Kehadiran aktual per anggota <small>rapat selesai dan gagal kuorum; RSVP tidak dihitung</small></h3>
            <div class="mf-table-wrap"><table class="mf-compliance">
                <tr><th>Anggota</th><th style="text-align:right">Diundang</th><th style="text-align:right">RSVP hadir</th><th style="text-align:right">Hadir</th><th style="width:130px">%</th></tr>
                @foreach ($attendance as $a)
                    <tr>
                        <td>{{ $a['user']->name }}</td><td class="num">{{ $a['invited'] }}</td><td class="num">{{ $a['rsvp_yes'] }}</td><td class="num">{{ $a['attended'] }}</td>
                        <td><div class="mf-bar"><div class="{{ $a['pct'] < 85 ? 'warn' : 'good' }}" style="width: {{ $a['pct'] }}%"></div></div><span class="mf-muted">{{ Fmt::num($a['pct'], 1) }}%</span></td>
                    </tr>
                @endforeach
            </table></div>
        </section>
    </div>

    <section class="mf-panel">
        <h3>Aturan yang dipakai <small>dapat diubah sekretariat tanpa mengubah kode</small></h3>
        <div class="mf-table-wrap"><table class="mf-compliance">
            <tr><th>Kode</th><th>Aturan</th><th style="text-align:right">Regulasi</th><th style="text-align:right">Internal</th><th>Dasar</th></tr>
            @foreach ($rules as $r)
                <tr><td class="mf-muted">{{ $r->code }}</td><td>{{ $r->title }}</td><td class="num">{{ Fmt::num($r->regulatory_value) }}</td><td class="num">{{ Fmt::num($r->internal_value) }}</td><td class="mf-muted">{{ $r->basis }}</td></tr>
            @endforeach
        </table></div>
    </section>
</div>
</x-filament-panels::page>
