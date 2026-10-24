<?php

namespace App\Services;

use App\Enums\InfoRequestStatus;
use App\Enums\MeetingStatus;
use App\Enums\TaskStatus;
use App\Models\BoardBook;
use App\Models\InformationRequest;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\MeetingRule;
use App\Models\Organ;
use App\Models\Risk;
use App\Models\Task;
use App\Models\User;
use App\Support\Access;
use Illuminate\Support\Collection;

/**
 * Ukuran tata kelola untuk dasbor dan halaman kepatuhan.
 * Seluruh angka dihitung dari data aplikasi; aturan POJK 33/2014 dibaca dari tabel meeting_rules.
 */
class GovernanceMetrics
{
    public function __construct(protected BoardBookService $books) {}

    public function ruleFor(Organ $organ): ?MeetingRule
    {
        $code = match (true) {
            $organ->kind->value === 'direksi' => 'POJK33-DIR',
            $organ->kind->value === 'dekom' => 'POJK33-DK',
            $organ->kind->value === 'gabungan' => 'POJK33-GAB',
            str_contains($organ->name, 'Audit') => 'POJK55-KA',
            str_contains($organ->name, 'Nominasi') => 'POJK34-KNR',
            default => null,
        };

        return $code ? MeetingRule::where('code', $code)->first() : null;
    }

    /** Realisasi rapat per organ dibanding aturan regulasi dan kebijakan internal. */
    public function frequency(?User $user = null, ?int $year = null): Collection
    {
        $year ??= now()->year;
        $organs = Organ::orderBy('sort')->when($user, fn ($q) => $q->whereIn('id', Access::organIds($user)))->get();
        $elapsed = now()->year > $year ? 12 : now()->month;

        return $organs->map(function (Organ $organ) use ($year, $elapsed) {
            $meetings = Meeting::where('organ_id', $organ->id)->whereYear('scheduled_at', $year)->get();
            $rule = $this->ruleFor($organ);
            $regulatory = (float) ($rule?->regulatory_value ?? $organ->min_meetings_per_year);
            $internal = (float) ($rule?->internal_value ?? $organ->min_meetings_per_year);
            $held = $meetings->where('status', MeetingStatus::Selesai)->count();
            $expected = (int) floor($regulatory * $elapsed / 12);
            $planned = $meetings->filter(fn ($m) => $m->scheduled_at->isFuture() && ! in_array($m->status, [MeetingStatus::Dibatalkan, MeetingStatus::GagalKuorum], true))->count();

            return [
                'organ' => $organ,
                'held' => $held,
                'failed' => $meetings->where('status', MeetingStatus::GagalKuorum)->count(),
                'planned' => $planned,
                'regulatory' => $regulatory,
                'internal' => $internal,
                'expected_to_date' => $expected,
                'on_track' => $held >= $expected,
                'projected' => $held + $planned,
                'basis' => $rule?->basis ?? 'Kebijakan internal',
            ];
        });
    }

    /** Ketepatan penyampaian bahan rapat (board book versi pertama terbit vs aturan H-n). */
    public function materials(?User $user = null, ?int $year = null): Collection
    {
        $year ??= now()->year;
        $rule = (float) (MeetingRule::where('code', 'POJK33-BAHAN')->value('internal_value') ?? 5);
        $query = Meeting::with('organ')->whereYear('scheduled_at', $year)->whereHas('boardBooks', fn ($q) => $q->whereNotNull('published_at'))->orderByDesc('scheduled_at');

        if ($user) {
            Access::scopeMeetings($query, $user);
        }

        return $query->get()->map(function (Meeting $m) use ($rule) {
            $lead = $this->books->leadDays($m);
            $versions = BoardBook::where('meeting_id', $m->id)->count();

            return ['meeting' => $m, 'lead' => $lead, 'rule' => $rule, 'on_time' => $lead !== null && $lead >= $rule, 'versions' => $versions];
        });
    }

    /** Kehadiran aktual anggota berhak suara pada rapat yang sudah berlangsung. */
    public function attendance(?User $user = null, ?int $year = null): Collection
    {
        $year ??= now()->year;
        $meetingIds = Meeting::whereYear('scheduled_at', $year)->whereIn('status', [MeetingStatus::Selesai->value, MeetingStatus::GagalKuorum->value])
            ->when($user, fn ($q) => Access::scopeMeetings($q, $user))->pluck('id');

        return MeetingParticipant::with('user')->whereIn('meeting_id', $meetingIds)->where('has_vote', true)->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => [
                'user' => $rows->first()->user,
                'invited' => $rows->count(),
                'attended' => $rows->where('attended', true)->count(),
                'rsvp_yes' => $rows->where('rsvp.value', 'hadir')->count(),
                'pct' => $rows->count() ? $rows->where('attended', true)->count() / $rows->count() * 100 : null,
            ])
            ->filter(fn ($r) => $r['user'] && ! $r['user']->isSecretariat())
            ->sortBy('pct')->values();
    }

    /** Status SLA permintaan informasi. */
    public function infoSla(?User $user = null): array
    {
        $q = InformationRequest::query();
        if ($user) {
            Access::scopeInfoRequests($q, $user);
        }
        $all = $q->get();
        $svc = app(InformationRequestService::class);

        return [
            'Dijawab tepat waktu' => $all->filter(fn ($r) => $r->answered_at && ! $svc->answeredLate($r))->count(),
            'Dijawab terlambat' => $all->filter(fn ($r) => $svc->answeredLate($r))->count(),
            'Terbuka, lewat SLA' => $all->filter(fn ($r) => $svc->isOverdue($r))->count(),
            'Terbuka, dalam SLA' => $all->filter(fn ($r) => in_array($r->status, [InfoRequestStatus::Diajukan, InfoRequestStatus::Klarifikasi], true) && ! $svc->isOverdue($r))->count(),
        ];
    }

    public function tasks(?User $user = null): array
    {
        $q = Task::query();
        if ($user) {
            Access::scopeTasks($q, $user);
        }
        $all = $q->get();
        $svc = app(TaskService::class);
        $byStatus = collect(TaskStatus::cases())->mapWithKeys(fn ($s) => [$s->getLabel() => $all->where('status', $s)->count()])->all();

        return [
            'by_status' => $byStatus,
            'total' => $all->count(),
            'overdue' => $all->filter(fn ($t) => $svc->isOverdue($t))->count(),
            'done' => $all->where('status', TaskStatus::Selesai)->count(),
            'full_not_done' => $all->filter(fn ($t) => $t->progress >= 100 && $t->status !== TaskStatus::Selesai)->count(),
        ];
    }

    /** Matriks risiko 5×5: [kemungkinan][dampak] => jumlah. */
    public function heatmap(): array
    {
        $grid = [];
        foreach (Risk::all() as $r) {
            $grid[$r->likelihood][$r->impact] = ($grid[$r->likelihood][$r->impact] ?? 0) + 1;
        }

        return $grid;
    }
}
