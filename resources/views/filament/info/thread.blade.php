@php
    $r = $getRecord()->loadMissing('messages.user', 'messages.document');
    $kinds = ['pertanyaan' => ['Pertanyaan', 'info'], 'jawaban' => ['Jawaban', 'good'], 'klarifikasi' => ['Klarifikasi', 'bad'], 'penerimaan' => ['Diterima', 'gold']];
@endphp
<section class="mf-panel">
    <h3>Percakapan <small>{{ $r->messages->count() }} pesan</small></h3>
    @foreach ($r->messages->sortBy('created_at') as $m)
        <div class="mf-note" style="{{ $m->kind === 'jawaban' ? 'margin-left:32px;' : '' }}">
            <span class="mf-chip {{ $kinds[$m->kind][1] ?? '' }}">{{ $kinds[$m->kind][0] ?? $m->kind }}</span>
            <b>{{ $m->user->name }}</b> <span class="mf-muted">{{ $m->created_at->translatedFormat('d M Y H:i') }}</span>
            <div style="margin-top:6px;white-space:pre-line;font-size:.86rem;line-height:1.55">{{ $m->body }}</div>
            @if ($m->document)<div class="mf-muted" style="margin-top:4px">Lampiran: {{ $m->document->title }}</div>@endif
        </div>
    @endforeach
</section>
