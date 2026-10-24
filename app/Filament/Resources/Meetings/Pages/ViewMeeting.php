<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Enums\MeetingStatus;
use App\Filament\Pages\AsistenAi;
use App\Filament\Resources\Meetings\MeetingResource;
use App\Models\ConflictDeclaration;
use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingService;
use App\Support\Access;
use App\Support\Audit;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewMeeting extends ViewRecord
{
    protected static string $resource = MeetingResource::class;

    public function mount(int|string $record): void
    {
        $meeting = Meeting::find($record);

        if ($meeting && ! Access::canSeeMeeting(auth()->user(), $meeting)) {
            Access::deny(auth()->user(), 'membuka rapat "'.$meeting->title.'"', $meeting);
            abort(403, 'Anda tidak berhak membuka rapat ini.');
        }

        parent::mount($record);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    protected function getHeaderActions(): array
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Meeting $meeting */
        $meeting = $this->getRecord();
        $manage = Access::canManageMeeting($user, $meeting);

        return [
            Action::make('ai')
                ->label('Ringkasan AI')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->url(AsistenAi::getUrl(['fitur' => 'ringkasan_rapat', 'rapat' => $meeting->id])),
            Action::make('conflict')
                ->label('Nyatakan benturan kepentingan')
                ->icon('heroicon-o-hand-raised')
                ->color('warning')
                ->visible(fn () => ! $user->isReadOnly() && $meeting->participants()->where('user_id', $user->id)->where('has_vote', true)->exists() && ! in_array($meeting->status, [MeetingStatus::Selesai, MeetingStatus::GagalKuorum], true))
                ->schema([
                    Select::make('agenda_item_id')->label('Agenda')->required()->options(fn () => $meeting->agendaItems()->orderBy('seq')->get()->mapWithKeys(fn ($i) => [$i->id => $i->seq.'. '.$i->title])),
                    Textarea::make('nature')->label('Sifat hubungan atau kepentingan')->required(),
                    Toggle::make('recused')->label('Saya tidak mengikuti pembahasan dan pengambilan keputusan agenda ini')->default(true)
                        ->helperText('Bahan agenda akan tertutup bagi Anda. Pengaruh terhadap kuorum mengikuti anggaran dasar dan kebijakan.'),
                ])
                ->action(function (array $data) use ($user) {
                    $c = ConflictDeclaration::create($data + ['user_id' => $user->id, 'declared_at' => now()]);
                    Access::flush();
                    Audit::log('benturan_kepentingan', $user->name.' menyatakan benturan kepentingan pada agenda '.$c->agendaItem->seq, $c->agendaItem);
                    Notification::make()->title('Pernyataan benturan kepentingan dicatat.')->success()->send();
                }),
            ActionGroup::make([
                Action::make('invite')->label('Undang seluruh anggota organ')->icon('heroicon-o-user-plus')
                    ->action(fn () => Notification::make()->title(app(MeetingService::class)->inviteMembers($meeting).' peserta ditambahkan.')->success()->send()),
                Action::make('start')->label('Mulai rapat')->icon('heroicon-o-play')
                    ->visible(fn () => in_array($meeting->status, [MeetingStatus::Dijadwalkan, MeetingStatus::BahanTerbit], true))
                    ->requiresConfirmation()
                    ->action(function () use ($meeting) {
                        app(MeetingService::class)->start($meeting);
                        Notification::make()->title('Rapat dimulai. Catat kehadiran aktual di tab Peserta.')->success()->send();
                    }),
                Action::make('close')->label('Tutup rapat')->icon('heroicon-o-stop')
                    ->visible(fn () => $meeting->status === MeetingStatus::Berlangsung)
                    ->requiresConfirmation()
                    ->modalDescription('Kuorum dihitung dari kehadiran aktual. Bila tidak terpenuhi, rapat dicatat gagal kuorum.')
                    ->action(function () use ($meeting) {
                        try {
                            $status = app(MeetingService::class)->close($meeting);
                            Notification::make()->title('Rapat ditutup: '.$status->getLabel())->color($status === MeetingStatus::Selesai ? 'success' : 'danger')->send();
                        } catch (Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make()->label('Ubah jadwal'),
            ])->label('Kelola')->icon('heroicon-o-cog-6-tooth')->button()->visible($manage),
        ];
    }
}
