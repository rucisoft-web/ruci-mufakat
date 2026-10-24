@php
    use App\Filament\Resources\Meetings\MeetingResource;
    use App\Filament\Resources\Tasks\TaskResource;
    use App\Filament\Resources\Circulars\CircularResolutionResource;
    use App\Filament\Pages\RuangBaca;
    use App\Support\Access;
    $d = $getRecord()->loadMissing('meeting', 'agendaItem.documents', 'circular', 'votes.user', 'tasks.pic', 'tasks.evidences.reviewer', 'supersedes');
    $tally = app(\App\Services\DecisionService::class)->tally($d);
    $me = auth()->user();
    $docs = $d->agendaItem?->documents->filter(fn ($doc) => Access::canReadDocument($me, $doc)) ?? collect();
@endphp
<div class="mf-dash">
    <section class="mf-panel">
        <h3>Rantai bukti <small>sumber → agenda → keputusan → tugas → bukti → verifikasi</small></h3>
        <div class="mf-chain">
            <div><b>Sumber</b>
                @forelse ($docs as $doc)
                    <a href="{{ RuangBaca::getUrl(['dokumen' => $doc->id]) }}">{{ $doc->title }}</a> <span class="mf-muted">v{{ $doc->version_no }}</span><br>
                @empty
                    {{ $d->circular ? 'Naskah sirkuler' : '—' }}
                @endforelse
            </div>
            <div><b>{{ $d->circular ? 'Sirkuler' : 'Rapat & agenda' }}</b>
                @if ($d->meeting)
                    @if (Access::canSeeMeeting($me, $d->meeting))
                        <a href="{{ MeetingResource::getUrl('view', ['record' => $d->meeting]) }}">{{ $d->meeting->title }}</a><br>
                    @else
                        {{ $d->meeting->title }} <span class="mf-muted">(bahan rapat hanya untuk organ terkait)</span><br>
                    @endif
                    <span class="mf-muted">Agenda {{ $d->agendaItem?->seq }}. {{ $d->agendaItem?->title }}</span>
                @elseif ($d->circular)
                    <a href="{{ CircularResolutionResource::getUrl('view', ['record' => $d->circular]) }}">{{ $d->circular->number }}</a>
                @endif
            </div>
            <div><b>Keputusan</b>{{ $d->number }}<br><span class="mf-muted">{{ $d->result->getLabel() }} · {{ $d->decided_on->translatedFormat('d M Y') }}</span></div>
            <div><b>Tugas</b>
                @forelse ($d->tasks as $t)
                    <a href="{{ TaskResource::getUrl('view', ['record' => $t]) }}">{{ $t->code }}</a> <span class="mf-muted">{{ $t->pic->name }} · {{ $t->status->getLabel() }}</span><br>
                @empty
                    Tidak ada tugas
                @endforelse
            </div>
            <div><b>Bukti & verifikasi</b>
                @php($evidences = $d->tasks->flatMap->evidences)
                @forelse ($evidences as $e)
                    {{ $e->status->getLabel() }}{{ $e->reviewer ? ' oleh '.$e->reviewer->name : '' }}<br>
                @empty
                    Belum ada bukti
                @endforelse
            </div>
        </div>
    </section>
    @if ($d->votes->isNotEmpty())
        <section class="mf-panel">
            <h3>Suara <small>{{ $tally['setuju'] ?? 0 }} setuju · {{ $tally['tidak_setuju'] ?? 0 }} tidak setuju · {{ $tally['abstain'] ?? 0 }} abstain</small></h3>
            @foreach ($d->votes as $v)
                <div class="mf-check">
                    <span class="mf-chip {{ ['setuju' => 'good', 'tidak_setuju' => 'bad', 'abstain' => 'warn'][$v->vote->value] ?? '' }}">{{ $v->vote->getLabel() }}</span>
                    <span><b>{{ $v->user->name }}</b>{{ $v->dissent_note ? ' — pendapat berbeda: "'.$v->dissent_note.'"' : '' }}</span>
                </div>
            @endforeach
        </section>
    @endif
</div>
