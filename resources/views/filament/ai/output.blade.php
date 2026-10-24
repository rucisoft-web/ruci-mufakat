@php
    use App\Filament\Pages\RuangBaca;
    $o = $interaction->output ?? [];
    $refs = collect($interaction->context_refs ?? [])->keyBy(fn ($r) => $r['document_id'].':'.$r['page']);
    $docTitles = \App\Models\Document::whereIn('id', collect($o['statements'] ?? [])->flatMap(fn ($s) => collect($s['sources'] ?? [])->pluck('document_id'))->unique())->get()->keyBy('id');
    $typeChip = ['fakta' => ['Fakta', 'info'], 'hitungan' => ['Hitungan aplikasi', 'good'], 'analisis' => ['Analisis AI', 'gold']];
@endphp
<section class="mf-panel mf-ai-out">
    <div class="mf-ai-status">
        <span class="mf-chip bad">DRAF — perlu ditelaah manusia</span>
        @include('filament.ai.status', ['status' => $interaction->status])
        <span class="mf-muted">{{ $interaction->created_at->translatedFormat('d M Y H:i') }} · {{ $interaction->model ?? 'tanpa model' }}{{ $interaction->latency_ms ? ' · '.number_format($interaction->latency_ms / 1000, 1).' detik' : '' }} · {{ count($interaction->context_refs ?? []) }} halaman sumber dikirim</span>
    </div>
    @if ($interaction->error)
        <div class="mf-note" style="border-color:#fecaca"><b>{{ $interaction->status === 'timeout' ? 'Layanan AI tidak merespons' : 'Catatan' }}:</b> {{ $interaction->error }}</div>
    @endif
    @foreach ($o['warnings'] ?? [] as $w)
        <div class="mf-note" style="border-color:#fde68a">⚠ {{ $w }}</div>
    @endforeach
    @if (filled($o['summary'] ?? null))
        <div class="mf-ai-summary">{{ $o['summary'] }}</div>
    @endif

    @if (! empty($o['statements']))
        <div>
            @foreach ($o['statements'] as $s)
                <div class="mf-stmt">
                    <div><span class="mf-chip {{ $typeChip[$s['type']][1] ?? '' }}">{{ $typeChip[$s['type']][0] ?? $s['type'] }}</span></div>
                    <div>{{ $s['text'] }}
                        @if (! empty($s['unverified']))<span class="mf-chip bad">belum terverifikasi</span>@endif
                        @if (! empty($s['sources']))
                            <div class="refs">
                                @foreach ($s['sources'] as $src)
                                    @php($d = $docTitles[$src['document_id']] ?? null)
                                    <a class="mf-ref" href="{{ RuangBaca::getUrl(['dokumen' => $src['document_id'], 'halaman' => $src['page']]) }}">{{ $d ? \Illuminate\Support\Str::limit($d->title, 38).' v'.$d->version_no : 'dok '.$src['document_id'] }} · hlm. {{ $src['page'] }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if (! empty($o['ratios']))
        <div>
            <h3>Rasio dihitung aplikasi</h3>
            <table class="mf-compliance">
                <tr><th>Rasio</th><th>Rumus</th><th style="text-align:right">2024</th><th style="text-align:right">2025</th></tr>
                @foreach ($o['ratios'] as $r)
                    <tr><td>{{ $r['label'] }}</td><td class="mf-muted">{{ $r['formula'] }}</td><td class="num">{{ \App\Support\Fmt::num($r['values']['2024'] ?? null, 2) }}</td><td class="num"><b>{{ \App\Support\Fmt::num($r['values']['2025'] ?? null, 2) }}</b> {{ $r['unit'] }}</td></tr>
                @endforeach
            </table>
        </div>
    @endif
    @foreach ($o['quality_flags'] ?? [] as $q)
        <div class="mf-note" style="border-color:#fde68a"><b>Kualitas data — {{ $q['metric'] }}:</b> sumber {{ $q['source'] }}; hitung aplikasi {{ $q['computed'] }}. {{ $q['message'] }}</div>
    @endforeach

    @if (! empty($o['completeness']))
        <div>
            <h3>Kelengkapan kertas usulan (pemeriksaan aturan)</h3>
            @foreach ($o['completeness'] as $c)
                <div class="mf-check"><span class="{{ $c['ok'] ? 'ok' : 'no' }}">{{ $c['ok'] ? '✓' : '✕' }}</span>{{ $c['label'] }}</div>
            @endforeach
        </div>
    @endif

    @if (! empty($o['questions']))
        <div>
            <h3>Pertanyaan yang layak diajukan</h3>
            <ol style="padding-left:20px;list-style:decimal;font-size:.88rem;line-height:1.6">@foreach ($o['questions'] as $q)<li>{{ $q }}</li>@endforeach</ol>
        </div>
    @endif

    @if (! empty($o['items']))
        <div>
            <h3>Usulan butir</h3>
            <table class="mf-compliance">
                <tr><th>Butir</th><th>Rincian</th><th>PIC/penyaji</th><th>Tenggat/durasi</th></tr>
                @foreach ($o['items'] as $it)
                    <tr><td><b>{{ $it['title'] }}</b></td><td>{{ $it['detail'] }}</td><td>{{ $it['owner'] ?: '— (tidak disebut)' }}</td><td>{{ $it['due'] ?: '—' }}</td></tr>
                @endforeach
            </table>
        </div>
    @endif

    @if (filled($o['draft_text'] ?? null))
        <div>
            <h3>Draf teks</h3>
            <div class="mf-draft">{{ $o['draft_text'] }}</div>
        </div>
    @endif

    @if (! empty($o['missing_information']))
        <div>
            <h3>Informasi yang belum tersedia</h3>
            <ul class="mf-insights">@foreach ($o['missing_information'] as $m)<li class="warn">{{ $m }}</li>@endforeach</ul>
        </div>
    @endif
</section>
