@php($c = $getRecord()->loadMissing('responses.user', 'decision'))
<section class="mf-panel">
    <h3>Tanggapan tertulis <small>{{ $c->responses->where('response', 'setuju')->count() }}/{{ $c->responses->count() }} setuju</small></h3>
    @forelse ($c->responses as $r)
        <div class="mf-check">
            <span class="mf-chip {{ ['setuju' => 'good', 'tidak_setuju' => 'bad'][$r->response] ?? 'warn' }}">{{ ['setuju' => 'Setuju', 'tidak_setuju' => 'Tidak setuju', 'menunggu' => 'Belum menanggapi'][$r->response] ?? $r->response }}</span>
            <span><b>{{ $r->user->name }}</b> <span class="mf-muted">{{ $r->responded_at?->translatedFormat('d M Y H:i') }}</span>{{ $r->note ? ' — "'.$r->note.'"' : '' }}</span>
        </div>
    @empty
        <p class="mf-empty">Belum diedarkan.</p>
    @endforelse
    @if ($c->status->value === 'kedaluwarsa')
        <p class="mf-muted" style="margin-top:8px">Batas waktu lewat sebelum seluruh anggota menanggapi; usulan tidak menjadi keputusan.</p>
    @endif
    @if ($c->decision)
        <p style="margin-top:10px"><a class="mf-chip gold" href="{{ \App\Filament\Resources\Decisions\DecisionResource::getUrl('view', ['record' => $c->decision]) }}">Menjadi keputusan {{ $c->decision->number }} →</a></p>
    @endif
</section>
