<?php

namespace App\Services;

use App\Enums\BoardBookStatus;
use App\Enums\MeetingStatus;
use App\Models\BoardBook;
use App\Models\BoardBookRead;
use App\Models\Meeting;
use App\Models\User;
use App\Support\Audit;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Paket bahan rapat (board book).
 *
 * Paket yang sudah terbit tidak pernah berubah diam-diam: revisi menghasilkan versi baru,
 * daftar perubahan dihitung dari manifes, dan seluruh penerima diberi tahu.
 */
class BoardBookService
{
    public function manifest(Meeting $meeting): array
    {
        $items = [];

        foreach ($meeting->agendaItems()->orderBy('seq')->with('documents')->get() as $item) {
            $items[] = [
                'agenda_id' => $item->id,
                'seq' => $item->seq,
                'title' => $item->title,
                'documents' => $item->documents->map(fn ($d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'version' => $d->version_no,
                    'series' => $d->series_key ?? 'doc-'.$d->id,
                    'pages' => $d->page_count,
                    'sha256' => $d->sha256,
                ])->values()->all(),
            ];
        }

        return $items;
    }

    /** Daftar perubahan antara dua manifes, dalam kalimat yang bisa dibaca anggota dewan. */
    public function diff(array $old, array $new): array
    {
        $flatten = fn (array $m) => collect($m)->flatMap(fn ($a) => collect($a['documents'])->map(fn ($d) => $d + ['agenda' => $a['seq'].'. '.$a['title']]))->keyBy('series');
        $o = $flatten($old);
        $n = $flatten($new);
        $changes = [];

        foreach ($n as $series => $doc) {
            if (! $o->has($series)) {
                $changes[] = "Ditambahkan: {$doc['title']} (agenda {$doc['agenda']})";
            } elseif ($o[$series]['sha256'] !== $doc['sha256']) {
                $changes[] = "Diperbarui: {$doc['title']} v{$o[$series]['version']} → v{$doc['version']}";
            }
        }

        foreach ($o as $series => $doc) {
            if (! $n->has($series)) {
                $changes[] = "Dihapus: {$doc['title']} (agenda {$doc['agenda']})";
            }
        }

        $oldAgenda = collect($old)->pluck('title', 'agenda_id');
        foreach ($new as $a) {
            if (! $oldAgenda->has($a['agenda_id'])) {
                $changes[] = "Agenda baru: {$a['seq']}. {$a['title']}";
            }
        }

        return $changes;
    }

    public function publish(Meeting $meeting, User $user, ?string $note = null): BoardBook
    {
        return DB::transaction(function () use ($meeting, $user, $note) {
            $previous = $meeting->boardBooks()->where('status', BoardBookStatus::Terbit->value)->latest('version_no')->first();
            $manifest = $this->manifest($meeting);
            $changes = $previous ? $this->diff($previous->manifest ?? [], $manifest) : [];

            if ($previous && ! $changes && ! $note) {
                throw new \RuntimeException('Tidak ada perubahan bahan sejak versi '.$previous->version_no.'.');
            }

            $previous?->update(['status' => BoardBookStatus::Digantikan]);

            $book = $meeting->boardBooks()->create([
                'version_no' => (int) $meeting->boardBooks()->max('version_no') + 1,
                'status' => BoardBookStatus::Terbit,
                'manifest' => $manifest,
                'manifest_hash' => hash('sha256', json_encode($manifest)),
                'change_log' => $previous ? trim(implode("\n", $changes)."\n".($note ? "Catatan: {$note}" : '')) : ($note ?: 'Penerbitan pertama.'),
                'prepared_by' => $user->id,
                'approved_by' => $user->id,
                'published_at' => now(),
            ]);

            $meeting->forceFill([
                'materials_sent_at' => $meeting->materials_sent_at ?? now(),
                'status' => $meeting->status === MeetingStatus::Dijadwalkan || $meeting->status === MeetingStatus::Draf ? MeetingStatus::BahanTerbit : $meeting->status,
            ])->save();

            foreach ($meeting->participants()->with('user')->get() as $p) {
                Notification::make()
                    ->title($previous ? "Board book v{$book->version_no} diperbarui" : 'Bahan rapat terbit')
                    ->body($meeting->title.($previous ? ' — '.count($changes).' perubahan sejak v'.$previous->version_no : ''))
                    ->icon('heroicon-o-book-open')
                    ->sendToDatabase($p->user);
            }

            Audit::log('board_book_terbit', "Board book v{$book->version_no} \"{$meeting->title}\" diterbitkan", $book, ['changes' => $changes]);

            return $book;
        });
    }

    public function markRead(BoardBook $book, User $user): void
    {
        BoardBookRead::firstOrCreate(['board_book_id' => $book->id, 'user_id' => $user->id], ['opened_at' => now()]);
    }

    /** Hari antara penerbitan pertama dan rapat; dibandingkan dengan aturan penyampaian bahan. */
    public function leadDays(Meeting $meeting): ?float
    {
        $first = $meeting->boardBooks()->whereNotNull('published_at')->orderBy('version_no')->first();

        return $first ? round($first->published_at->diffInHours($meeting->scheduled_at, false) / 24, 1) : null;
    }
}
