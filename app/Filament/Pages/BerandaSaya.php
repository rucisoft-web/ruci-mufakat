<?php

namespace App\Filament\Pages;

use App\Enums\CircularStatus;
use App\Enums\InfoRequestStatus;
use App\Enums\MeetingStatus;
use App\Enums\MinuteStatus;
use App\Enums\TaskStatus;
use App\Models\BoardBook;
use App\Models\CircularResponse;
use App\Models\Declaration;
use App\Models\InformationRequest;
use App\Models\Meeting;
use App\Models\MinuteSignature;
use App\Models\Task;
use App\Support\Access;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BerandaSaya extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Beranda';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Beranda Saya';

    protected static ?string $title = 'Beranda Saya';

    protected static ?string $slug = 'beranda-saya';

    protected string $view = 'filament.pages.beranda-saya';

    public function getHeading(): string
    {
        return '';
    }

    protected function getViewData(): array
    {
        $me = auth()->user();
        $guest = $me->isDemoGuest();

        $meetings = Access::scopeMeetings(Meeting::query(), $me)->with('organ', 'boardBooks.reads')
            ->where('scheduled_at', '>=', now()->startOfDay())->whereNotIn('status', [MeetingStatus::Dibatalkan->value])
            ->when(! $guest && ! $me->isSecretariat(), fn ($q) => $q->whereHas('participants', fn ($p) => $p->where('user_id', $me->id)))
            ->orderBy('scheduled_at')->limit(6)->get();

        $unread = $meetings->map(fn ($m) => $m->boardBooks->where('status.value', 'terbit')->sortByDesc('version_no')->first())
            ->filter(fn (?BoardBook $b) => $b && ! $b->reads->contains('user_id', $me->id));

        $signatures = MinuteSignature::with('minute.meeting')->where('user_id', $me->id)->where('status', 'menunggu')
            ->whereHas('minute', fn ($q) => $q->where('status', MinuteStatus::EdarTtd->value))->get();

        $circulars = CircularResponse::with('circular')->where('user_id', $me->id)->where('response', 'menunggu')
            ->whereHas('circular', fn ($q) => $q->where('status', CircularStatus::Beredar->value))->get();

        $toAnswer = InformationRequest::where('pic_id', $me->id)->whereIn('status', [InfoRequestStatus::Diajukan->value, InfoRequestStatus::Klarifikasi->value])->orderBy('sla_due_at')->get();
        $toAccept = InformationRequest::where('requester_id', $me->id)->where('status', InfoRequestStatus::Dijawab->value)->get();
        $myQuestions = InformationRequest::where('requester_id', $me->id)->whereIn('status', [InfoRequestStatus::Diajukan->value, InfoRequestStatus::Klarifikasi->value])->get();

        $myTasks = Task::where('pic_id', $me->id)->where('status', '!=', TaskStatus::Selesai->value)->orderBy('due_date')->get();
        $toVerify = Task::where('verifier_id', $me->id)->where('status', TaskStatus::Diajukan->value)->get();

        $declarations = Declaration::where('user_id', $me->id)->where('status', 'menunggu')->get();
        $term = $me->activeMemberships()->filter(fn ($m) => $m->term_end && $m->term_end->lessThan(now()->addYear()));

        return compact('me', 'guest', 'meetings', 'unread', 'signatures', 'circulars', 'toAnswer', 'toAccept', 'myQuestions', 'myTasks', 'toVerify', 'declarations', 'term');
    }
}
