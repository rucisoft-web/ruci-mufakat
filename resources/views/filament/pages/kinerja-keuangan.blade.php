@php use App\Support\Fmt; use App\Filament\Pages\RuangBaca; use App\Filament\Pages\AsistenAi; @endphp
<x-filament-panels::page>
<div class="mf-dash">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <x-filament::button icon="heroicon-o-sparkles" tag="a" :href="AsistenAi::getUrl(['fitur' => 'penjelasan_kinerja'])">Minta penjelasan AI</x-filament::button>
        <x-filament::button color="gray" icon="heroicon-o-chat-bubble-left-right" tag="a" :href="\App\Filament\Resources\InformationRequests\InformationRequestResource::getUrl('index')">Permintaan informasi terkait</x-filament::button>
    </div>
    @foreach ($flags as $q)
        <div class="mf-note" style="border-color:#fde68a;background:#fffbeb"><b>Perlu klarifikasi — {{ $q['metric'] }}.</b> Angka sumber {{ $q['source'] }}; hitung aplikasi {{ $q['computed'] }}. {{ $q['message'] }}</div>
    @endforeach
    <div class="mf-grid-2">
        @livewire(\App\Filament\Widgets\FinanceChart::class)
        @livewire(\App\Filament\Widgets\QuarterlyChart::class)
    </div>
    <div class="mf-grid-2">
        <section class="mf-panel">
            <h3>Pertumbuhan 2024 → 2025 <small>Rp juta · hitung aplikasi</small></h3>
            <div class="mf-table-wrap"><table class="mf-compliance">
                <tr><th>Indikator</th><th style="text-align:right">2024</th><th style="text-align:right">2025</th><th style="text-align:right">Perubahan</th><th>Hlm.</th></tr>
                @foreach ($growth as $g)
                    <tr><td>{{ $g['label'] }}</td><td class="num">{{ Fmt::num($g['from']) }}</td><td class="num"><b>{{ Fmt::num($g['to']) }}</b></td>
                        <td class="num">{{ Fmt::pct($g['pct']) }}{{ $g['is_loss'] ? ' (besaran rugi)' : '' }}</td>
                        <td><a class="mf-ref" href="{{ RuangBaca::getUrl(['dokumen' => $facts->firstWhere('metric', array_search($g, $growth))?->document_id, 'halaman' => $g['page']]) }}">{{ $g['page'] }}</a></td></tr>
                @endforeach
            </table></div>
            <p class="mf-muted" style="margin-top:8px">Untuk rugi, perubahan dihitung atas besaran rugi: rugi Rp1.208.173 juta menjadi Rp443.357 juta = turun 63,30%.</p>
        </section>
        <section class="mf-panel">
            <h3>Rasio <small>rumus ditampilkan</small></h3>
            <div class="mf-table-wrap"><table class="mf-compliance">
                <tr><th>Rasio</th><th>Rumus</th><th style="text-align:right">2024</th><th style="text-align:right">2025</th></tr>
                @foreach ($ratios as $r)
                    <tr><td><b>{{ $r['label'] }}</b></td><td class="mf-muted">{{ $r['formula'] }}</td><td class="num">{{ Fmt::num($r['values']['2024'], 2) }}</td><td class="num"><b>{{ Fmt::num($r['values']['2025'], 2) }}</b> {{ $r['unit'] }}</td></tr>
                @endforeach
            </table></div>
        </section>
    </div>
    <section class="mf-panel">
        <h3>Basis data keuangan <small>{{ $facts->count() }} fakta</small></h3>
        <div class="mf-table-wrap"><table class="mf-compliance">
            <tr><th>Indikator</th><th>Periode</th><th style="text-align:right">Nilai</th><th>Satuan</th><th>Asal data</th><th>Sumber</th><th>Catatan</th></tr>
            @foreach ($facts as $f)
                <tr>
                    <td>{{ $f->label }}</td><td>{{ $f->period }}</td><td class="num">{{ Fmt::num($f->value, $f->value != floor($f->value) ? 2 : 0) }}</td><td class="mf-muted">{{ $f->unit }}</td>
                    <td><span class="mf-chip {{ ['report_fact' => 'info', 'derived_from_report' => 'good', 'simulated' => 'gold'][$f->data_origin->value] ?? '' }}">{{ $f->data_origin->getLabel() }}</span></td>
                    <td>@if ($f->document_id && $f->source_page)<a class="mf-ref" href="{{ RuangBaca::getUrl(['dokumen' => $f->document_id, 'halaman' => (int) $f->source_page]) }}">hlm. {{ $f->source_page }}</a>@else — @endif</td>
                    <td class="mf-muted">{{ $f->needs_clarification ? '⚠ ' : '' }}{{ \Illuminate\Support\Str::limit($f->quality_note, 90) }}</td>
                </tr>
            @endforeach
        </table></div>
    </section>
</div>
</x-filament-panels::page>
