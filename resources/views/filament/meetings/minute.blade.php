@php($minute->loadMissing('signatures.user', 'corrects'))
<div class="mf-dash">
    @if ($minute->kind === 'addendum')
        <div class="mf-note"><b>Addendum atas risalah v{{ $minute->corrects?->version_no }}</b><br><span class="mf-muted">Alasan koreksi: {{ $minute->correction_reason }}. Risalah asal tetap utuh dan tidak ditimpa.</span></div>
    @endif
    <article class="mf-markdown">{!! \Illuminate\Support\Str::markdown($minute->body, ['html_input' => 'escape']) !!}</article>
    @if ($minute->signatures->isNotEmpty())
        <section class="mf-panel">
            <h3>Pengesahan <small>sidik isi {{ substr((string) $minute->content_hash, 0, 16) }}…</small></h3>
            @foreach ($minute->signatures as $s)
                <div class="mf-check">
                    <span class="{{ $s->status->value === 'ditandatangani' ? 'ok' : 'no' }}">{{ $s->status->value === 'ditandatangani' ? '✓' : ($s->status->value === 'menolak' ? '✕' : '…') }}</span>
                    <span><b>{{ $s->user->name }}</b> — {{ $s->status->getLabel() }}{{ $s->signed_at ? ', '.$s->signed_at->translatedFormat('d M Y H:i') : '' }}{{ $s->reason ? ' — alasan: "'.$s->reason.'"' : '' }}</span>
                </div>
            @endforeach
        </section>
    @endif
</div>
