<x-filament-panels::page>
<div class="mf-dash">
    <div class="mf-head">
        <div>
            <div class="mf-eyebrow">Asisten AI · keluaran berstatus DRAF</div>
            <h1>Bantuan membaca, bertanya, dan menyusun draf</h1>
            <p>Hanya potongan dokumen yang boleh Anda baca yang dikirim ke AI. Setiap fakta menunjuk dokumen, versi, dan halaman. Rasio dihitung aplikasi.
                AI tidak menentukan kuorum, memberi suara, menandatangani, atau mengesahkan.</p>
        </div>
        <span class="mf-chip {{ $live ? 'good' : 'gold' }}">{{ $live ? 'Terhubung ke OpenAI' : 'Mode referensi demo (tanpa API)' }}</span>
    </div>

    <div class="mf-ai-features">
        @foreach ($features as $key => [$label, $desc])
            <button type="button" wire:click="choose('{{ $key }}')" class="mf-ai-feature {{ $feature === $key ? 'active' : '' }}"><b>{{ $label }}</b><small>{{ $desc }}</small></button>
        @endforeach
    </div>

    <section class="mf-panel">
        <h3>{{ $features[$feature][0] }}</h3>
        <form wire:submit="run" style="display:grid;gap:10px;max-width:900px">
            @if (in_array($input, ['meeting', 'meeting_notes'], true))
                <label class="mf-muted">Rapat</label>
                <x-filament::input.wrapper :valid="! $errors->has('meetingId')"><x-filament::input.select wire:model="meetingId">
                    <option value="">— pilih rapat —</option>
                    @foreach ($meetings as $m)<option value="{{ $m->id }}">{{ $m->scheduled_at->format('d/m/Y') }} · {{ $m->organ->short_name }} · {{ $m->title }}</option>@endforeach
                </x-filament::input.select></x-filament::input.wrapper>
            @endif
            @if ($input === 'agenda')
                <label class="mf-muted">Agenda</label>
                <x-filament::input.wrapper :valid="! $errors->has('agendaId')"><x-filament::input.select wire:model="agendaId">
                    <option value="">— pilih agenda —</option>
                    @foreach ($agendas as $a)<option value="{{ $a->id }}">{{ $a->meeting->scheduled_at->format('d/m') }} · {{ $a->seq }}. {{ $a->title }}</option>@endforeach
                </x-filament::input.select></x-filament::input.wrapper>
            @endif
            @if ($input === 'document' || $feature === 'tanya_dokumen')
                <label class="mf-muted">{{ $input === 'document' ? 'Dokumen (versi terbaru; dibandingkan dengan versi sebelumnya)' : 'Batasi ke dokumen (opsional)' }}</label>
                <x-filament::input.wrapper :valid="! $errors->has('documentId')"><x-filament::input.select wire:model="documentId">
                    <option value="">{{ $input === 'document' ? '— pilih dokumen —' : 'Semua dokumen yang boleh saya baca' }}</option>
                    @foreach ($documents as $d)<option value="{{ $d->id }}">{{ $d->title }} (v{{ $d->version_no }})</option>@endforeach
                </x-filament::input.select></x-filament::input.wrapper>
            @endif
            @if ($input === 'organ')
                <label class="mf-muted">Organ</label>
                <x-filament::input.wrapper :valid="! $errors->has('organId')"><x-filament::input.select wire:model="organId">
                    <option value="">— pilih organ —</option>
                    @foreach ($organs as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                </x-filament::input.select></x-filament::input.wrapper>
            @endif
            @if ($input === 'question')
                <label class="mf-muted">Pertanyaan</label>
                <x-filament::input.wrapper :valid="! $errors->has('question')"><x-filament::input type="text" wire:model="question" placeholder="{{ $feature === 'memori_keputusan' ? 'Contoh: keputusan apa saja tentang rekonsiliasi persediaan?' : 'Contoh: berapa liabilitas jangka pendek 2025 dan bagaimana dibanding 2024?' }}" /></x-filament::input.wrapper>
            @endif
            @if (in_array($input, ['text', 'meeting_notes'], true))
                <label class="mf-muted">{{ $feature === 'terjemahan' ? 'Teks bahasa Indonesia' : 'Catatan, transkrip, atau teks pembahasan' }}</label>
                <x-filament::input.wrapper :valid="! $errors->has('text')"><textarea wire:model="text" rows="6" class="fi-input" style="width:100%;resize:vertical"></textarea></x-filament::input.wrapper>
            @endif
            @if ($input === 'company')
                <p class="mf-muted">Memakai fakta laporan tahunan Kimia Farma 2025 dan rasio hasil hitung aplikasi.</p>
            @endif
            @foreach ($errors->all() as $err)<p class="mf-muted" style="color:var(--mf-bad)">{{ $err }}</p>@endforeach
            <div><x-filament::button type="submit" icon="heroicon-o-sparkles" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="run">Jalankan</span><span wire:loading wire:target="run">Memproses…</span>
            </x-filament::button></div>
        </form>
    </section>

    @if ($result)
        @include('filament.ai.output', ['interaction' => $result])
    @endif

    <section class="mf-panel">
        <h3>Riwayat saya <small><a href="{{ \App\Filament\Resources\AiInteractions\AiInteractionResource::getUrl() }}">semua</a></small></h3>
        @forelse ($history as $h)
            <a class="mf-row" style="grid-template-columns: 1fr auto" href="{{ \App\Filament\Pages\AsistenAi::getUrl(['fitur' => $h->feature, 'hasil' => $h->id]) }}">
                <div class="body"><b>{{ $features[$h->feature][0] ?? $h->feature }} — {{ $h->prompt }}</b><small>{{ $h->created_at->translatedFormat('d M Y H:i') }} · {{ $h->model ?? '—' }}</small></div>
                @include('filament.ai.status', ['status' => $h->status])
            </a>
        @empty
            <p class="mf-empty">Belum ada.</p>
        @endforelse
    </section>
</div>
</x-filament-panels::page>
