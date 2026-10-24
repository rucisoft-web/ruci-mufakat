<?php

namespace App\Filament\Resources\InformationRequests\Pages;

use App\Filament\Resources\InformationRequests\InformationRequestResource;
use App\Models\InformationRequest;
use App\Support\Audit;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInformationRequest extends CreateRecord
{
    protected static string $resource = InformationRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $organ = \App\Models\Organ::find($data['organ_id']);
        $seq = InformationRequest::whereYear('created_at', now()->year)->count() + 11;

        return $data + [
            'company_id' => $organ->company_id,
            'requester_id' => auth()->id(),
            'code' => sprintf('PI-%d-%03d', now()->year, $seq),
            'status' => 'diajukan',
        ];
    }

    protected function afterCreate(): void
    {
        $r = $this->record;
        $r->messages()->create(['user_id' => $r->requester_id, 'kind' => 'pertanyaan', 'body' => $r->question, 'document_id' => $r->document_id]);
        Notification::make()->title('Permintaan informasi baru')->body($r->subject.' — SLA '.$r->sla_due_at->translatedFormat('d M Y H:i'))->icon('heroicon-o-chat-bubble-left-right')->sendToDatabase($r->pic);
        Audit::log('info_diajukan', "Permintaan {$r->code} diajukan", $r);
    }
}
