@php($map = ['berhasil' => ['Berhasil', 'good'], 'referensi_demo' => ['Referensi demo', 'gold'], 'bukti_tidak_cukup' => ['Bukti tidak cukup', 'warn'], 'timeout' => ['Timeout', 'bad'], 'gagal' => ['Gagal', 'bad'], 'ai_nonaktif' => ['AI nonaktif', '']])
<span class="mf-chip {{ $map[$status][1] ?? '' }}">{{ $map[$status][0] ?? $status }}</span>
