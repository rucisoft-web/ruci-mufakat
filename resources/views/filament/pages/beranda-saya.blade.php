@php
    use App\Filament\Resources\Meetings\MeetingResource;
    use App\Filament\Resources\Tasks\TaskResource;
    use App\Filament\Resources\InformationRequests\InformationRequestResource;
    use App\Filament\Resources\Circulars\CircularResolutionResource;
    $todo = $unread->count() + $signatures->count() + $circulars->count() + $toAnswer->count() + $toAccept->count() + $toVerify->count();
    $hour = now()->hour;
    $greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp
<x-filament-panels::page>
<div class="mf-dash">
    <div class="mf-head">
        <div>
            <div class="mf-eyebrow">{{ now()->translatedFormat('l, d F Y') }}</div>
            <h1>{{ $greet }}, {{ $me->name }}</h1>
            <p>{{ $me->title ?? $me->role?->getLabel() }} · {{ $todo ? $todo.' hal menunggu tindakan Anda.' : 'Tidak ada yang menunggu tindakan Anda.' }}
                @if ($guest) Anda melihat demo sebagai tamu; buka <a href="{{ \App\Filament\Pages\PanduanDemo::getUrl() }}">Panduan demo</a> untuk alur 30–40 menit. @endif</p>
        </div>
    </div>

    <div class="mf-kpis">
        <div class="mf-kpi {{ $unread->count() ? 'warn' : 'good' }}"><span>Bahan belum dibaca</span><strong>{{ $unread->count() }}</strong><small>board book versi terbaru</small></div>
        <div class="mf-kpi {{ $signatures->count() ? 'warn' : 'good' }}"><span>Tanda tangan risalah</span><strong>{{ $signatures->count() }}</strong><small>menunggu Anda</small></div>
        <div class="mf-kpi {{ $circulars->count() ? 'warn' : 'good' }}"><span>Sirkuler</span><strong>{{ $circulars->count() }}</strong><small>menunggu tanggapan</small></div>
        <div class="mf-kpi {{ $toAnswer->count() ? 'bad' : 'good' }}"><span>Pertanyaan untuk dijawab</span><strong>{{ $toAnswer->count() }}</strong><small>sebagai PIC</small></div>
        <div class="mf-kpi {{ $myTasks->filter(fn ($t) => $t->due_date->isPast())->count() ? 'bad' : '' }}"><span>Tugas saya</span><strong>{{ $myTasks->count() }}</strong><small>{{ $toVerify->count() }} bukti menunggu verifikasi Anda</small></div>
    </div>

    <div class="mf-grid-3">
        <section class="mf-panel mf-span-2">
            <h3>Rapat saya <small><a href="{{ MeetingResource::getUrl() }}">kalender rapat</a></small></h3>
            <div class="mf-list">
                @forelse ($meetings as $m)
                    @php($book = $m->boardBooks->where('status.value', 'terbit')->sortByDesc('version_no')->first())
                    <a class="mf-row" href="{{ MeetingResource::getUrl('view', ['record' => $m]) }}">
                        <div class="mf-date {{ $m->scheduled_at->isToday() || $m->scheduled_at->isTomorrow() ? 'soon' : '' }}">{{ $m->scheduled_at->translatedFormat('M') }}<b>{{ $m->scheduled_at->format('d') }}</b></div>
                        <div class="body"><b>{{ $m->title }}</b><small>{{ $m->organ->name }} · {{ $m->scheduled_at->format('H:i') }} · {{ ucfirst($m->mode) }}</small></div>
                        @if (! $book)<span class="mf-chip">Bahan belum terbit</span>
                        @elseif ($book->reads->contains('user_id', $me->id))<span class="mf-chip good">Dibaca · v{{ $book->version_no }}</span>
                        @else<span class="mf-chip warn">Belum dibaca · v{{ $book->version_no }}</span>@endif
                    </a>
                @empty
                    <p class="mf-empty">Tidak ada rapat mendatang.</p>
                @endforelse
            </div>
        </section>
        <section class="mf-panel">
            <h3>Perlu tindakan</h3>
            @foreach ($signatures as $s)
                <a class="mf-note" style="display:block" href="{{ MeetingResource::getUrl('view', ['record' => $s->minute->meeting_id]) }}?relation=4"><span class="mf-chip warn">Tanda tangan</span> Risalah v{{ $s->minute->version_no }} — {{ $s->minute->meeting->title }}</a>
            @endforeach
            @foreach ($circulars as $c)
                <a class="mf-note" style="display:block" href="{{ CircularResolutionResource::getUrl('view', ['record' => $c->circular]) }}"><span class="mf-chip warn">Sirkuler</span> {{ $c->circular->title }} <span class="mf-muted">batas {{ $c->circular->deadline_at->translatedFormat('d M H:i') }}</span></a>
            @endforeach
            @foreach ($toAnswer as $r)
                <a class="mf-note" style="display:block" href="{{ InformationRequestResource::getUrl('view', ['record' => $r]) }}"><span class="mf-chip {{ $r->sla_due_at->isPast() ? 'bad' : 'info' }}">Jawab</span> {{ $r->code }} {{ $r->subject }} <span class="mf-muted">SLA {{ $r->sla_due_at->translatedFormat('d M') }}</span></a>
            @endforeach
            @foreach ($toAccept as $r)
                <a class="mf-note" style="display:block" href="{{ InformationRequestResource::getUrl('view', ['record' => $r]) }}"><span class="mf-chip info">Tinjau jawaban</span> {{ $r->code }} {{ $r->subject }}</a>
            @endforeach
            @foreach ($toVerify as $t)
                <a class="mf-note" style="display:block" href="{{ TaskResource::getUrl('view', ['record' => $t]) }}"><span class="mf-chip warn">Verifikasi bukti</span> {{ $t->code }} {{ $t->title }}</a>
            @endforeach
            @foreach ($declarations as $d)
                <div class="mf-note"><span class="mf-chip">Pernyataan {{ $d->year }}</span> {{ ucfirst(str_replace('_', ' ', $d->kind)) }} belum disampaikan</div>
            @endforeach
            @foreach ($term as $t)
                <div class="mf-note"><span class="mf-chip gold">Masa jabatan</span> {{ $t->organ->name }} berakhir {{ $t->term_end->translatedFormat('d M Y') }}</div>
            @endforeach
            @if ($todo + $declarations->count() + $term->count() === 0)
                <p class="mf-empty">Semua beres.</p>
            @endif
        </section>
    </div>

    <div class="mf-grid-2">
        <section class="mf-panel">
            <h3>Tugas tindak lanjut saya <small><a href="{{ TaskResource::getUrl() }}">semua</a></small></h3>
            @forelse ($myTasks as $t)
                @php($late = $t->due_date->isPast() && ! $t->due_date->isToday())
                <a class="mf-row" style="grid-template-columns: 1fr 120px auto" href="{{ TaskResource::getUrl('view', ['record' => $t]) }}">
                    <div class="body"><b>{{ $t->code }} · {{ $t->title }}</b><small>tenggat {{ $t->due_date->translatedFormat('d M Y') }}{{ $late ? ' · terlambat' : '' }}</small></div>
                    <div class="mf-bar"><div class="{{ $late ? 'bad' : ($t->progress >= 100 ? 'good' : '') }}" style="width: {{ $t->progress }}%"></div></div>
                    <span class="mf-chip {{ $late ? 'bad' : '' }}">{{ $t->status->getLabel() }}</span>
                </a>
            @empty
                <p class="mf-empty">Tidak ada tugas terbuka atas nama Anda.</p>
            @endforelse
        </section>
        <section class="mf-panel">
            <h3>Pertanyaan saya kepada manajemen <small><a href="{{ InformationRequestResource::getUrl('create') }}">ajukan</a></small></h3>
            @forelse ($myQuestions as $r)
                <a class="mf-row" style="grid-template-columns: 1fr auto" href="{{ InformationRequestResource::getUrl('view', ['record' => $r]) }}">
                    <div class="body"><b>{{ $r->code }} · {{ $r->subject }}</b><small>SLA {{ $r->sla_due_at->translatedFormat('d M Y H:i') }}</small></div>
                    <span class="mf-chip {{ $r->sla_due_at->isPast() ? 'bad' : 'info' }}">{{ $r->sla_due_at->isPast() ? 'Lewat SLA' : $r->status->getLabel() }}</span>
                </a>
            @empty
                <p class="mf-empty">Tidak ada pertanyaan yang sedang menunggu jawaban.</p>
            @endforelse
        </section>
    </div>
</div>
</x-filament-panels::page>
