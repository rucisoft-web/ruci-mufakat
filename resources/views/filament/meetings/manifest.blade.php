@php($me = auth()->user())
@php(app(\App\Services\BoardBookService::class)->markRead($book, $me))
<div class="mf-dash">
    @if ($book->change_log)
        <div class="mf-note"><b>Perubahan:</b><br><span style="white-space:pre-line">{{ $book->change_log }}</span></div>
    @endif
    @foreach ($book->manifest ?? [] as $agenda)
        @php($item = \App\Models\AgendaItem::find($agenda['agenda_id']))
        <section class="mf-panel">
            <h3>{{ $agenda['seq'] }}. {{ $agenda['title'] }}</h3>
            @if ($item && ! \App\Support\Access::canSeeAgendaItem($me, $item))
                <p class="mf-empty">Bahan agenda ini tertutup bagi Anda (benturan kepentingan atau di luar penugasan).</p>
            @else
                @forelse ($agenda['documents'] as $doc)
                    <a class="mf-row" style="grid-template-columns: 1fr auto" href="{{ \App\Filament\Pages\RuangBaca::getUrl(['dokumen' => $doc['id'], 'rapat' => $meeting->id]) }}">
                        <div class="body"><b>{{ $doc['title'] }}</b><small>versi {{ $doc['version'] }} · {{ $doc['pages'] }} halaman · {{ substr((string) $doc['sha256'], 0, 16) }}…</small></div>
                        <span class="mf-chip gold">Baca</span>
                    </a>
                @empty
                    <p class="mf-empty">Tanpa bahan tertulis.</p>
                @endforelse
            @endif
        </section>
    @endforeach
    <p class="mf-muted">Sidik manifes: <code>{{ $book->manifest_hash }}</code></p>
</div>
