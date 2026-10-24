@php
    $task = $getRecord()->loadMissing('milestones', 'evidences.submitter', 'evidences.reviewer', 'evidences.document', 'events.user', 'decision.meeting');
    $labels = ['dibuat' => 'Dibuat', 'kemajuan' => 'Kemajuan', 'bukti_diajukan' => 'Bukti diajukan', 'bukti_ditolak' => 'Bukti ditolak', 'diverifikasi' => 'Diverifikasi', 'dibuka_kembali' => 'Dibuka kembali', 'tenggat_diubah' => 'Tenggat diubah'];
@endphp
<div class="mf-grid-3">
    <section class="mf-panel">
        <h3>Tonggak <small>{{ $task->milestones->where('is_done', true)->count() }}/{{ $task->milestones->count() }}</small></h3>
        @forelse ($task->milestones as $m)
            <div class="mf-check"><span class="{{ $m->is_done ? 'ok' : 'no' }}">{{ $m->is_done ? '✓' : '○' }}</span><span>{{ $m->title }} <span class="mf-muted">{{ $m->due_date?->format('d M Y') }}</span></span></div>
        @empty
            <p class="mf-empty">Tidak ada tonggak.</p>
        @endforelse
        @if ($task->decision)
            <p class="mf-muted" style="margin-top:10px">Asal: <a href="{{ \App\Filament\Resources\Decisions\DecisionResource::getUrl('view', ['record' => $task->decision]) }}" style="color:var(--mf-gold);font-weight:600">{{ $task->decision->number }}</a> — {{ $task->decision->title }}</p>
        @endif
    </section>
    <section class="mf-panel">
        <h3>Bukti <small>{{ $task->evidences->count() }} pengajuan</small></h3>
        @forelse ($task->evidences->sortByDesc('created_at') as $e)
            <div class="mf-note">
                <span class="mf-chip {{ ['diterima' => 'good', 'ditolak' => 'bad'][$e->status->value] ?? 'warn' }}">{{ $e->status->getLabel() }}</span>
                <span class="mf-muted">{{ $e->created_at->translatedFormat('d M Y') }} · {{ $e->submitter->name }}</span>
                <div style="margin-top:4px">{{ $e->description }}</div>
                @if ($e->document)<div class="mf-muted">Dokumen: {{ $e->document->title }}</div>@endif
                @if ($e->file_path)<div><a href="{{ route('berkas.bukti', $e) }}" style="color:var(--mf-gold);font-weight:600">Unduh berkas</a></div>@endif
                @if ($e->review_note)<div class="mf-muted" style="margin-top:4px">{{ $e->reviewer?->name }}: "{{ $e->review_note }}"</div>@endif
            </div>
        @empty
            <p class="mf-empty">Belum ada bukti diajukan.</p>
        @endforelse
    </section>
    <section class="mf-panel">
        <h3>Riwayat</h3>
        @foreach ($task->events->sortByDesc('created_at') as $ev)
            <div class="mf-note"><b>{{ $labels[$ev->event] ?? $ev->event }}</b> <span class="mf-muted">{{ $ev->created_at?->translatedFormat('d M Y H:i') }} · {{ $ev->user?->name ?? 'Sistem' }}</span>
                @if ($ev->note)<div class="mf-muted">{{ $ev->note }}</div>@endif</div>
        @endforeach
    </section>
</div>
