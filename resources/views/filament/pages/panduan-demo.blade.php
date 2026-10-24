<x-filament-panels::page>
<div class="mf-dash">
    <div class="mf-grid-3">
        <section class="mf-panel mf-span-2">
            @php($n = 0)
            @foreach ($stages as $stage => $rows)
                <h3 style="margin-top:{{ $loop->first ? 0 : 18 }}px">{{ $loop->iteration }}. {{ $stage }} <small>± {{ $rows->count() * 1.3 > 1 ? round($rows->count() * 1.3) : 1 }} menit</small></h3>
                @foreach ($rows as $s)
                    <div class="mf-stmt" style="grid-template-columns: 64px 1fr">
                        <div><span class="mf-chip gold">{{ $s->code }}</span></div>
                        <div><b>{{ $s->title }}</b> — {{ $s->steps }}
                            <div class="mf-muted" style="margin-top:3px">Hasil yang terlihat: {{ $s->expected }}
                                @if ($url = $this->routeFor($s->route_hint)) · <a href="{{ $url }}" style="color:var(--mf-gold);font-weight:600">buka</a>@endif</div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </section>
        <section class="mf-panel">
            <h3>Akun demo <small>kata sandi {{ config('mufakat.demo.password') }}</small></h3>
            @foreach ($accounts as $a)
                <div class="mf-row" style="grid-template-columns: 1fr">
                    <div class="body"><b>{{ $a->name }}</b><small>{{ $a->email }} · {{ $a->role?->getLabel() }}</small></div>
                </div>
            @endforeach
            <p class="mf-muted" style="margin-top:10px">Nama "Demo …" adalah alias untuk kegiatan rapat simulasi. Nama pejabat sebenarnya hanya muncul di Direktori Historis per 31 Desember 2025.</p>
        </section>
    </div>
</div>
</x-filament-panels::page>
