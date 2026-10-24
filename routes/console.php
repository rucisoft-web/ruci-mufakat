<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\Ai\AiAssistant;
use App\Services\CircularService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

// Tutup sirkuler yang melewati batas waktu (juga diperiksa saat halamannya dibuka).
Artisan::command('mufakat:sirkuler', function () {
    $this->info(app(CircularService::class)->expireAll().' usulan sirkuler ditutup.');
})->purpose('Menutup keputusan sirkuler yang melewati batas waktu');

// Pengingat harian kepada PIC tugas yang melewati tenggat.
Artisan::command('mufakat:pengingat', function () {
    $tasks = Task::with('pic')->where('status', '!=', TaskStatus::Selesai->value)->whereDate('due_date', '<', today())->get();

    foreach ($tasks as $t) {
        Notification::make()->title('Tindak lanjut melewati tenggat')->body("{$t->code} {$t->title} — tenggat {$t->due_date->translatedFormat('d M Y')}")
            ->icon('heroicon-o-clock')->danger()->sendToDatabase($t->pic);
    }

    $this->info($tasks->count().' pengingat dikirim.');
})->purpose('Mengirim pengingat tugas tindak lanjut yang terlambat');

// Pemeriksaan kesiapan server dan konfigurasi.
Artisan::command('mufakat:doctor', function () {
    $rows = [
        ['PHP '.PHP_VERSION, version_compare(PHP_VERSION, '8.3.0', '>=')],
        ['Koneksi database ('.config('database.default').')', rescue(fn () => (bool) DB::select('select 1'), false, false)],
        ['Pencarian FULLTEXT (MySQL/MariaDB)', in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)],
        ['Folder storage bisa ditulis', is_writable(storage_path('app'))],
        ['pdftotext tersedia (opsional)', is_executable((string) config('mufakat.pdftotext'))],
        ['Kunci OpenAI diisi (opsional)', app(AiAssistant::class)->isLive()],
        ['Antrean sinkron (tanpa worker)', config('queue.default') === 'sync'],
        ['Instalasi web dimatikan', ! config('mufakat.installer')],
        ['APP_DEBUG mati', ! config('app.debug')],
    ];
    $this->table(['Pemeriksaan', 'Hasil'], array_map(fn ($r) => [$r[0], $r[1] ? '✓' : '✗'], $rows));
})->purpose('Memeriksa kesiapan Ruci MUFAKAT');

Schedule::command('mufakat:sirkuler')->hourly();
Schedule::command('mufakat:pengingat')->dailyAt('07:00');
