<?php

namespace App\Filament\Resources\Meetings\RelationManagers;

use App\Enums\Rsvp;
use App\Models\MeetingParticipant;
use App\Models\User;
use App\Services\QuorumService;
use App\Support\Access;
use App\Support\Audit;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    protected static ?string $title = 'Peserta & kehadiran';

    protected static ?string $modelLabel = 'peserta';

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function canManage(): bool
    {
        return Access::canManageMeeting(auth()->user(), $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')->label('Pengguna')->options(fn () => User::where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->required(),
            Select::make('role')->label('Peran')->options(['anggota' => 'Anggota', 'undangan' => 'Undangan', 'presenter' => 'Presenter', 'sekretaris' => 'Sekretaris'])->default('undangan')->required(),
            Toggle::make('has_vote')->label('Hak suara')->default(false),
            DateTimePicker::make('access_until')->label('Akses berakhir (untuk tamu)')->seconds(false),
        ]);
    }

    public function table(Table $table): Table
    {
        $me = auth()->user();
        $meeting = $this->getOwnerRecord();

        return $table
            ->description(fn () => 'Kuorum dihitung dari kehadiran AKTUAL, bukan RSVP. '.($meeting->participants()->whereNotNull('attended')->exists() ? app(QuorumService::class)->summary($meeting) : ''))
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user', 'organ')->orderByDesc('has_vote')->orderBy('organ_id')->orderBy('id'))
            ->columns([
                TextColumn::make('user.name')->label('Nama')->weight('semibold')->description(fn (MeetingParticipant $r) => $r->user->title),
                TextColumn::make('role')->label('Peran')->badge()->color('gray'),
                TextColumn::make('organ.short_name')->label('Organ suara')->placeholder('—'),
                IconColumn::make('has_vote')->label('Suara')->boolean(),
                TextColumn::make('rsvp')->label('RSVP')->badge()->description(fn (MeetingParticipant $r) => $r->rsvp_note),
                IconColumn::make('attended')->label('Hadir aktual')->boolean()->placeholder('belum dicatat'),
                TextColumn::make('attendance_mode')->label('Mode')->placeholder('—'),
                TextColumn::make('check_in_at')->label('Masuk')->dateTime('H:i')->placeholder('—'),
                TextColumn::make('check_out_at')->label('Keluar')->dateTime('H:i')->placeholder('—'),
                TextColumn::make('absence_reason')->label('Alasan tidak hadir')->placeholder('—')->wrap(),
            ])
            ->headerActions([
                Action::make('myRsvp')->label('Konfirmasi kehadiran saya')->icon('heroicon-o-hand-thumb-up')
                    ->visible(fn () => ! $me->isReadOnly() && $meeting->scheduled_at->isFuture() && $meeting->participants()->where('user_id', $me->id)->exists())
                    ->schema([
                        Select::make('rsvp')->label('Jawaban')->options(Rsvp::options())->required(),
                        TextInput::make('rsvp_note')->label('Catatan'),
                    ])
                    ->action(function (array $data) use ($meeting, $me) {
                        $meeting->participants()->where('user_id', $me->id)->update($data);
                        Notification::make()->title('Konfirmasi tersimpan. RSVP tidak dihitung sebagai kehadiran.')->success()->send();
                    }),
                CreateAction::make()->label('Tambah peserta/tamu')->visible(fn () => $this->canManage()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('present')->label('Hadir (luring)')->icon('heroicon-o-check')
                        ->action(fn (MeetingParticipant $r) => $this->mark($r, true, 'luring')),
                    Action::make('online')->label('Hadir (daring)')->icon('heroicon-o-video-camera')
                        ->action(fn (MeetingParticipant $r) => $this->mark($r, true, 'daring')),
                    Action::make('absent')->label('Tidak hadir')->icon('heroicon-o-x-mark')
                        ->schema([TextInput::make('absence_reason')->label('Alasan')])
                        ->action(fn (MeetingParticipant $r, array $data) => $this->mark($r, false, null, $data['absence_reason'] ?? null)),
                    Action::make('leave')->label('Catat keluar')->icon('heroicon-o-arrow-right-start-on-rectangle')
                        ->visible(fn (MeetingParticipant $r) => $r->attended)
                        ->action(function (MeetingParticipant $r) {
                            $r->update(['check_out_at' => now()]);
                            Audit::log('kehadiran_keluar', $r->user->name.' keluar rapat', $r->meeting);
                        }),
                ])->visible(fn () => $this->canManage()),
            ])
            ->toolbarActions([
                BulkAction::make('bulkPresent')->label('Tandai hadir')->visible(fn () => $this->canManage())
                    ->action(fn (Collection $records) => $records->each(fn ($r) => $this->mark($r, true, 'luring'))),
            ]);
    }

    protected function mark(MeetingParticipant $p, bool $present, ?string $mode, ?string $reason = null): void
    {
        $p->update([
            'attended' => $present,
            'attendance_mode' => $mode,
            'check_in_at' => $present ? ($p->check_in_at ?? now()) : null,
            'absence_reason' => $present ? null : $reason,
        ]);
        Audit::log('kehadiran', $p->user->name.' '.($present ? 'hadir '.$mode : 'tidak hadir'), $p->meeting);
    }
}
