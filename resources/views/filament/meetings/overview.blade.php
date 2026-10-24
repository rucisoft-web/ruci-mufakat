@php
    use App\Filament\Resources\InformationRequests\InformationRequestResource;
    use App\Filament\Pages\RuangBaca;
    $meeting = $getRecord();
    $book = $meeting->boardBooks()->latest('version_no')->first();
    $voters = $meeting->participants()->where('has_vote', true)->count();
    $requests = $meeting->informationRequests()->with('requester', 'pic')->latest()->get();
    $conflicts = \App\Models\ConflictDeclaration::with('user', 'agendaItem')->whereIn('agenda_item_id', $meeting->agendaItems()->pluck('id'))->get();
    $firstDoc = $meeting->agendaItems()->orderBy('seq')->get()->first(fn ($i) => \App\Support\Access::canSeeAgendaItem(auth()->user(), $i) && $i->documents()->exists())?->documents()->first();
@endphp
<div class="mf-grid-3">
    <section class="mf-panel">
        <h3>Board book <small>{{ $book ? 'versi '.$book->version_no : 'belum terbit' }}</small></h3>
        @if ($book)
            <p class="mf-muted">Terbit {{ $book->published_at->translatedFormat('d M Y H:i') }} · sidik {{ substr($book->manifest_hash, 0, 12) }}…</p>
            <p class="mf-muted">Dibuka {{ $book->reads()->count() }} dari {{ $voters }} anggota.</p>
            @if ($book->version_no > 1)
                <p style="margin-top:6px"><span class="mf-chip warn">Diperbarui setelah distribusi</span></p>
                <p class="mf-muted" style="white-space:pre-line;margin-top:6px">{{ $book->change_log }}</p>
            @endif
        @else
            <p class="mf-empty">Sekretariat belum menerbitkan bahan.</p>
        @endif
        @if ($firstDoc)
            <p style="margin-top:10px"><a class="mf-chip gold" href="{{ RuangBaca::getUrl(['dokumen' => $firstDoc->id, 'rapat' => $meeting->id]) }}">Buka di ruang baca →</a></p>
        @endif
    </section>
    <section class="mf-panel">
        <h3>Permintaan informasi <small>{{ $requests->count() }}</small></h3>
        @forelse ($requests as $r)
            <a class="mf-row" style="grid-template-columns: 1fr auto" href="{{ InformationRequestResource::getUrl('view', ['record' => $r]) }}">
                <div class="body"><b>{{ $r->subject }}</b><small>{{ $r->code }} · {{ $r->requester->name }} → {{ $r->pic->name }}</small></div>
                <span class="mf-chip {{ ['diterima' => 'good', 'dijawab' => 'info', 'klarifikasi' => 'bad'][$r->status->value] ?? 'warn' }}">{{ $r->status->getLabel() }}</span>
            </a>
        @empty
            <p class="mf-empty">Tidak ada.</p>
        @endforelse
    </section>
    <section class="mf-panel">
        <h3>Benturan kepentingan <small>per agenda</small></h3>
        @forelse ($conflicts as $c)
            <div class="mf-note"><b>{{ $c->user->name }}</b> — agenda {{ $c->agendaItem->seq }}. {{ $c->agendaItem->title }}<br>
                <span class="mf-muted">{{ $c->nature }}. {{ $c->recused ? 'Mundur dari pembahasan; bahan agenda tertutup baginya.' : 'Tetap mengikuti.' }}</span></div>
        @empty
            <p class="mf-empty">Belum ada pernyataan.</p>
        @endforelse
    </section>
</div>
