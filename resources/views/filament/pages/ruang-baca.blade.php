@php
    use App\Filament\Pages\AsistenAi;
    use App\Filament\Resources\InformationRequests\InformationRequestResource;
    use App\Filament\Resources\Meetings\MeetingResource;
    $pages = $doc?->pages->sortBy('page_no') ?? collect();
@endphp
<x-filament-panels::page>
<div class="mf-reader">
    <aside class="mf-panel mf-toc">
        <h3>{{ $meeting ? 'Bahan rapat' : 'Dokumen' }} <small>{{ $toc->count() }}</small></h3>
        @if ($meeting)
            <p class="mf-muted" style="margin:-6px 0 8px"><a href="{{ MeetingResource::getUrl('view', ['record' => $meeting]) }}" style="color:var(--mf-gold);font-weight:600">← {{ $meeting->title }}</a></p>
        @endif
        @foreach ($toc as $d)
            <a href="#" wire:click.prevent="open({{ $d->id }}, 1)" class="{{ $doc && $doc->id === $d->id ? 'active' : '' }}">
                {{ $d->title }}<br><small>{{ $d->agenda_label ?? \App\Filament\Resources\Documents\DocumentResource::KINDS[$d->kind] ?? $d->kind }} · v{{ $d->version_no }} · {{ $d->data_origin->getLabel() }}</small>
            </a>
        @endforeach
    </aside>

    <main>
        @if ($doc)
            <div class="mf-pagehead">
                <div>
                    <div class="mf-eyebrow">{{ $doc->classification->getLabel() }} · versi {{ $doc->version_no }} · {{ $doc->data_origin->getLabel() }}</div>
                    <h2 style="margin:2px 0 0;font-size:1.15rem;font-weight:700">{{ $doc->title }}</h2>
                    <span class="mf-muted">Sidik SHA-256 {{ substr((string) $doc->sha256, 0, 16) }}… {{ $doc->source_ref ? '· '.$doc->source_ref : '' }}</span>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <x-filament::input.wrapper style="width:200px">
                        <x-filament::input type="search" wire:model.live.debounce.400ms="search" placeholder="Cari di dokumen…" />
                    </x-filament::input.wrapper>
                    @if ($doc->ai_allowed)
                        <x-filament::button size="sm" color="gray" icon="heroicon-o-sparkles" tag="a" :href="AsistenAi::getUrl(['fitur' => 'tanya_dokumen', 'dokumen' => $doc->id])">Tanya AI</x-filament::button>
                    @endif
                    @if ($doc->previous_version_id)
                        <x-filament::button size="sm" color="gray" icon="heroicon-o-arrows-right-left" tag="a" :href="AsistenAi::getUrl(['fitur' => 'bandingkan_versi', 'dokumen' => $doc->id])">Bandingkan v{{ $doc->previousVersion?->version_no }}</x-filament::button>
                    @endif
                </div>
            </div>
            @if ($hits->isNotEmpty())
                <p class="mf-muted" style="margin-bottom:8px">Ditemukan di halaman:
                    @foreach ($hits as $h)<a href="#" wire:click.prevent="goTo({{ $h }})" class="mf-ref">{{ $h }}</a> @endforeach</p>
            @elseif (mb_strlen(trim($search)) >= 3)
                <p class="mf-muted" style="margin-bottom:8px">Tidak ditemukan "{{ $search }}".</p>
            @endif
            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px">
                @foreach ($pages as $p)
                    <button type="button" wire:click="goTo({{ $p->page_no }})" class="mf-chip {{ $current && $current->page_no === $p->page_no ? 'gold' : '' }}">hlm. {{ $p->page_no }}</button>
                @endforeach
            </div>
            <article class="mf-page">@if ($current?->heading)<h2>{{ $current->heading }}</h2>@endif{!! $content !!}</article>
            <p class="mf-muted" style="margin-top:8px">Halaman {{ $current?->page_no }} dari {{ $doc->page_count }}. Rujukan AI ke dokumen ini memakai nomor halaman yang sama.</p>
        @else
            <div class="mf-panel"><p class="mf-empty">Pilih dokumen di sebelah kiri.</p></div>
        @endif
    </main>

    <aside>
        @if ($doc)
            @unless ($me->isReadOnly())
                <section class="mf-panel" style="margin-bottom:12px">
                    <h3>Catatan pada hlm. {{ $current?->page_no }}</h3>
                    <form wire:submit="addNote" style="display:grid;gap:8px">
                        <x-filament::input.wrapper><x-filament::input type="text" wire:model="noteQuote" placeholder="Kutipan (opsional)" /></x-filament::input.wrapper>
                        <x-filament::input.wrapper><textarea wire:model="noteBody" rows="3" class="fi-input" placeholder="Tulis catatan atau pertanyaan…" style="width:100%;resize:vertical"></textarea></x-filament::input.wrapper>
                        <div style="display:flex;gap:6px">
                            <x-filament::input.wrapper style="flex:1"><x-filament::input.select wire:model="noteKind"><option value="catatan">Catatan</option><option value="pertanyaan">Pertanyaan</option><option value="penanda">Penanda</option></x-filament::input.select></x-filament::input.wrapper>
                            <x-filament::input.wrapper style="flex:1"><x-filament::input.select wire:model="noteVisibility"><option value="pribadi">Pribadi</option><option value="bersama">Bersama</option></x-filament::input.select></x-filament::input.wrapper>
                        </div>
                        <x-filament::button type="submit" size="sm">Simpan catatan</x-filament::button>
                    </form>
                    <p class="mf-muted" style="margin-top:8px">Punya pertanyaan untuk manajemen? <a href="{{ InformationRequestResource::getUrl('create', ['dokumen' => $doc->id, 'halaman' => $current?->page_no, 'rapat' => $meeting?->id]) }}" style="color:var(--mf-gold);font-weight:600">Ajukan permintaan informasi</a></p>
                </section>
            @endunless
            <section class="mf-panel">
                <h3>Anotasi <small>{{ $notes->count() }}</small></h3>
                @forelse ($notes as $n)
                    <div class="mf-note">
                        @if ($n->quote)<q>{{ $n->quote }}</q>@endif
                        {{ $n->body }}
                        <div class="mf-muted" style="margin-top:4px"><a href="#" wire:click.prevent="goTo({{ $n->page_no }})" class="mf-ref">hlm. {{ $n->page_no }}</a>
                            {{ $n->user->name }} · {{ $n->visibility === 'pribadi' ? 'pribadi' : 'bersama' }} · {{ $n->kind }}</div>
                    </div>
                @empty
                    <p class="mf-empty">Belum ada anotasi. Catatan pribadi hanya terlihat oleh Anda.</p>
                @endforelse
            </section>
        @endif
    </aside>
</div>
</x-filament-panels::page>
