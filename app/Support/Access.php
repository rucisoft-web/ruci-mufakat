<?php

namespace App\Support;

use App\Enums\Classification;
use App\Enums\OrganKind;
use App\Enums\UserRole;
use App\Models\AgendaItem;
use App\Models\ConflictDeclaration;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Organ;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Aturan akses Ruci MUFAKAT.
 *
 * Hak baca mengikuti perusahaan, organ, komite, masa jabatan, rapat, agenda, dan klasifikasi dokumen:
 * - Direksi tidak otomatis membaca rapat Dewan Komisaris atau komite (terutama sesi tertutup).
 * - Sekretaris Perusahaan mengelola rapat Direksi dan gabungan; Sekretaris Dewan Komisaris mengelola rapat Dekom, komite, dan gabungan.
 * - Administrator teknis tidak otomatis membaca isi dokumen dewan.
 * - Tamu/presenter hanya membuka agenda yang ia bawakan, sampai batas waktunya.
 * - Anggota yang menyatakan benturan kepentingan dan mundur tidak membuka bahan agenda tersebut.
 */
class Access
{
    /** @var array<int, array<string, mixed>> */
    protected static array $cache = [];

    public static function flush(): void
    {
        static::$cache = [];
    }

    protected static function remember(User $user, string $key, callable $fn): mixed
    {
        return static::$cache[$user->id][$key] ??= $fn();
    }

    /** Organ yang rapat-rapatnya boleh dilihat pengguna. */
    public static function organIds(User $user): Collection
    {
        return static::remember($user, 'organs', function () use ($user) {
            if ($user->role === UserRole::TamuDemo) {
                return Organ::pluck('id');
            }

            $ids = collect();
            $organs = Organ::all();

            foreach ($user->activeMemberships() as $m) {
                $ids->push($m->organ_id);
                $kind = $m->organ->kind;

                // Anggota Direksi/Dekom ikut rapat gabungan perusahaannya; Dekom membawahi komite.
                if (in_array($kind, [OrganKind::Direksi, OrganKind::Dekom], true)) {
                    $ids = $ids->merge($organs->where('company_id', $m->organ->company_id)->where('kind', OrganKind::Gabungan)->pluck('id'));
                }
                if ($kind === OrganKind::Dekom) {
                    $ids = $ids->merge($organs->where('parent_organ_id', $m->organ_id)->pluck('id'));
                }
            }

            $ids = $ids->merge(match ($user->role) {
                UserRole::Sekper => $organs->whereIn('kind', [OrganKind::Direksi, OrganKind::Gabungan])->pluck('id'),
                UserRole::Sekdekom => $organs->whereIn('kind', [OrganKind::Dekom, OrganKind::Komite, OrganKind::Gabungan])->pluck('id'),
                default => [],
            });

            return $ids->unique()->values();
        });
    }

    /** Organ yang rapatnya boleh dikelola (jadwal, agenda, board book, risalah). */
    public static function managedOrganIds(User $user): Collection
    {
        if ($user->isReadOnly()) {
            return collect();
        }

        return static::remember($user, 'managed', fn () => Organ::query()
            ->when($user->role === UserRole::Sekper, fn ($q) => $q->whereIn('kind', [OrganKind::Direksi->value, OrganKind::Gabungan->value]))
            ->when($user->role === UserRole::Sekdekom, fn ($q) => $q->whereIn('kind', [OrganKind::Dekom->value, OrganKind::Komite->value, OrganKind::Gabungan->value]))
            ->when(! $user->isSecretariat(), fn ($q) => $q->whereRaw('1 = 0'))
            ->pluck('id'));
    }

    public static function canManageMeeting(User $user, Meeting $meeting): bool
    {
        return static::managedOrganIds($user)->contains($meeting->organ_id);
    }

    public static function canManageAnyMeeting(User $user): bool
    {
        return static::managedOrganIds($user)->isNotEmpty();
    }

    /** Rapat yang diundang secara pribadi (tamu/presenter, PIC) dan masih dalam masa akses. */
    public static function invitedMeetingIds(User $user): Collection
    {
        return static::remember($user, 'invited', fn () => MeetingParticipant::query()
            ->where('user_id', $user->id)
            ->where(fn ($q) => $q->whereNull('access_until')->orWhere('access_until', '>', now()))
            ->pluck('meeting_id'));
    }

    public static function scopeMeetings(Builder $query, User $user): Builder
    {
        $organIds = static::organIds($user);
        $invited = static::invitedMeetingIds($user);

        return $query->where(fn ($q) => $q->whereIn('organ_id', $organIds)->orWhereIn('id', $invited));
    }

    public static function canSeeMeeting(User $user, Meeting $meeting): bool
    {
        return static::organIds($user)->contains($meeting->organ_id) || static::invitedMeetingIds($user)->contains($meeting->id);
    }

    /** Agenda yang tidak boleh dibuka karena pengguna mundur akibat benturan kepentingan. */
    public static function recusedAgendaIds(User $user): Collection
    {
        if ($user->isDemoGuest()) {
            return collect();
        }

        return static::remember($user, 'recused', fn () => ConflictDeclaration::where('user_id', $user->id)->where('recused', true)->pluck('agenda_item_id'));
    }

    public static function canSeeAgendaItem(User $user, AgendaItem $item): bool
    {
        if (static::recusedAgendaIds($user)->contains($item->id)) {
            return false;
        }

        $meeting = $item->meeting;

        if (static::organIds($user)->contains($meeting->organ_id)) {
            return true;
        }

        // Undangan pribadi: presenter hanya membuka agenda yang ia bawakan; PIC membuka agenda miliknya.
        if (static::invitedMeetingIds($user)->contains($meeting->id)) {
            return in_array($user->id, [$item->presenter_id, $item->owner_id], true) || $user->role !== UserRole::Tamu;
        }

        return false;
    }

    /** ID dokumen yang boleh dibaca pengguna (dipakai daftar dokumen, ruang baca, dan asisten AI). */
    public static function readableDocumentIds(User $user): Collection
    {
        return static::remember($user, 'docs', function () use ($user) {
            $documents = Document::query()->with('agendaItems.meeting')->get(['id', 'organ_id', 'classification', 'kind']);
            $granted = $user->id ? \App\Models\DocumentGrant::where('user_id', $user->id)
                ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
                ->pluck('document_id') : collect();

            return $documents->filter(fn (Document $d) => static::evaluateDocument($user, $d, $granted))->pluck('id')->values();
        });
    }

    protected static function evaluateDocument(User $user, Document $doc, Collection $granted): bool
    {
        if ($granted->contains($doc->id) || $user->isDemoGuest()) {
            return true;
        }

        // Bahan agenda mengikuti hak atas agenda (termasuk pembatasan benturan kepentingan).
        if ($doc->agendaItems->isNotEmpty()) {
            return $doc->agendaItems->contains(fn (AgendaItem $item) => static::canSeeAgendaItem($user, $item));
        }

        $boardMember = $user->activeMemberships()->isNotEmpty() || $user->isSecretariat();

        return match (true) {
            $user->role === UserRole::Admin => $doc->classification === Classification::Internal,
            $user->role === UserRole::Tamu => false,
            $user->role === UserRole::Auditor => in_array($doc->kind, ['risalah', 'bukti', 'kebijakan', 'laporan_tahunan'], true),
            $doc->organ_id === null => $doc->classification !== Classification::SangatRahasia ? ($boardMember || $doc->classification === Classification::Internal) : $boardMember,
            default => static::organIds($user)->contains($doc->organ_id),
        };
    }

    public static function canReadDocument(User $user, Document|int $document): bool
    {
        return static::readableDocumentIds($user)->contains($document instanceof Document ? $document->id : $document);
    }

    /**
     * Organ yang register keputusannya boleh dibaca. Dewan Komisaris mengawasi pengurusan, sehingga anggotanya
     * dapat membaca keputusan Direksi (bukan sebaliknya).
     */
    public static function decisionOrganIds(User $user): Collection
    {
        return static::remember($user, 'decisionOrgans', function () use ($user) {
            $ids = static::organIds($user);

            if ($user->isCommissioner() || $user->role === UserRole::Sekdekom) {
                $ids = $ids->merge(Organ::where('kind', OrganKind::Direksi->value)->pluck('id'));
            }

            return $ids->unique()->values();
        });
    }

    /** Tugas tindak lanjut: milik sendiri (PIC/verifikator) atau berasal dari organ yang boleh dilihat. */
    public static function scopeTasks(Builder $query, User $user): Builder
    {
        if ($user->isDemoGuest() || $user->role === UserRole::Auditor || $user->isSecretariat()) {
            return $query;
        }

        $organIds = static::organIds($user);

        return $query->where(fn ($q) => $q->where('pic_id', $user->id)->orWhere('verifier_id', $user->id)
            ->orWhereHas('decision', fn ($d) => $d->whereIn('organ_id', $organIds))
            ->orWhereHas('agendaItem.meeting', fn ($m) => $m->whereIn('organ_id', $organIds)));
    }

    /** Permintaan informasi: organ yang boleh dilihat, atau pengguna sebagai peminta/PIC. */
    public static function scopeInfoRequests(Builder $query, User $user): Builder
    {
        if ($user->isDemoGuest() || $user->role === UserRole::Auditor) {
            return $query;
        }

        $organIds = static::organIds($user);

        return $query->where(fn ($q) => $q->whereIn('organ_id', $organIds)->orWhere('requester_id', $user->id)->orWhere('pic_id', $user->id));
    }

    /** Catat percobaan akses yang ditolak ke jejak audit. */
    public static function deny(User $user, string $what, mixed $subject = null): void
    {
        Audit::log('akses_ditolak', "Akses ditolak: {$what}", $subject, ['role' => $user->role?->value]);
    }
}
