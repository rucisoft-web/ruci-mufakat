<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use App\Services\MeetingService;
use App\Support\Audit;
use Filament\Resources\Pages\CreateRecord;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    protected function afterCreate(): void
    {
        $n = app(MeetingService::class)->inviteMembers($this->record);
        Audit::log('rapat_dijadwalkan', "Rapat dijadwalkan: {$this->record->title} ({$n} peserta diundang)", $this->record);
    }

    protected function getRedirectUrl(): string
    {
        return MeetingResource::getUrl('view', ['record' => $this->record]);
    }
}
