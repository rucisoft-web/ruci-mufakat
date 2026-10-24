<?php

namespace App\Filament\Resources\Meetings;

use App\Enums\MeetingStatus;
use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Meetings\Pages\CreateMeeting;
use App\Filament\Resources\Meetings\Pages\EditMeeting;
use App\Filament\Resources\Meetings\Pages\ListMeetings;
use App\Filament\Resources\Meetings\Pages\ViewMeeting;
use App\Filament\Resources\Meetings\RelationManagers\AgendaItemsRelationManager;
use App\Filament\Resources\Meetings\RelationManagers\BoardBooksRelationManager;
use App\Filament\Resources\Meetings\RelationManagers\DecisionsRelationManager;
use App\Filament\Resources\Meetings\RelationManagers\MinutesRelationManager;
use App\Filament\Resources\Meetings\RelationManagers\ParticipantsRelationManager;
use App\Models\Meeting;
use App\Models\Organ;
use App\Models\User;
use App\Services\BoardBookService;
use App\Services\QuorumService;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MeetingResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Meeting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Rapat';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'rapat';

    protected static ?string $pluralModelLabel = 'Rapat';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return Access::scopeMeetings(parent::getEloquentQuery(), static::me())->with('organ');
    }

    public static function canCreate(): bool
    {
        return Access::canManageAnyMeeting(static::me());
    }

    public static function canEdit(Model $record): bool
    {
        return Access::canManageMeeting(static::me(), $record);
    }

    public static function canView(Model $record): bool
    {
        return Access::canSeeMeeting(static::me(), $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Jadwal rapat')->columns(2)->columnSpanFull()->schema([
                Select::make('organ_id')->label('Organ penyelenggara')->required()
                    ->options(fn () => Organ::whereIn('id', Access::managedOrganIds(static::me()))->orderBy('sort')->pluck('name', 'id')),
                TextInput::make('number')->label('Nomor rapat')->placeholder('RD/11/2026'),
                TextInput::make('title')->label('Judul rapat')->required()->columnSpanFull(),
                Select::make('kind')->label('Jenis')->options(['rutin' => 'Rutin', 'khusus' => 'Khusus', 'gabungan' => 'Gabungan'])->default('rutin')->required(),
                Select::make('mode')->label('Bentuk')->options(['luring' => 'Luring', 'daring' => 'Daring', 'hybrid' => 'Hybrid'])->default('hybrid')->required(),
                DateTimePicker::make('scheduled_at')->label('Mulai')->seconds(false)->required(),
                DateTimePicker::make('ends_at')->label('Selesai')->seconds(false),
                DateTimePicker::make('materials_due_at')->label('Batas penyerahan bahan')->seconds(false)
                    ->helperText('Aturan POJK 33/2014: bahan rapat terjadwal disampaikan paling lambat 5 hari sebelum rapat.'),
                TextInput::make('location')->label('Tempat'),
                TextInput::make('online_link')->label('Tautan rapat daring')->url(),
                Select::make('chair_id')->label('Pimpinan rapat')->options(fn () => User::where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable(),
                Select::make('secretary_id')->label('Sekretaris rapat')->options(fn () => User::whereIn('role', ['sekper', 'sekdekom'])->pluck('name', 'id')),
                Toggle::make('is_closed_session')->label('Sesi tertutup (hanya anggota organ)'),
                Select::make('status')->label('Status')->options(MeetingStatus::class)->default(MeetingStatus::Dijadwalkan)->required(),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('title')->label('Rapat')->weight('bold')->columnSpan(2),
                TextEntry::make('organ.name')->label('Organ')->badge()->color('primary'),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('scheduled_at')->label('Waktu')->dateTime('l, d F Y · H:i'),
                TextEntry::make('mode')->label('Bentuk / tempat')->state(fn (Meeting $r) => ucfirst($r->mode).' — '.($r->location ?? '—')),
                TextEntry::make('chair.name')->label('Pimpinan rapat')->placeholder('—'),
                TextEntry::make('secretary.name')->label('Sekretaris')->placeholder('—'),
                TextEntry::make('quorum')->label('Kuorum (kehadiran aktual)')->columnSpan(2)
                    ->state(fn (Meeting $r) => $r->participants()->whereNotNull('attended')->exists()
                        ? app(QuorumService::class)->summary($r)
                        : 'Belum ada kehadiran aktual. RSVP: '.$r->participants()->where('rsvp', 'hadir')->where('has_vote', true)->count().' akan hadir (RSVP bukan bukti kuorum).')
                    ->color(fn (Meeting $r) => $r->status === MeetingStatus::GagalKuorum ? 'danger' : null),
                TextEntry::make('materials')->label('Ketepatan bahan')->columnSpan(2)
                    ->state(function (Meeting $r) {
                        $lead = app(BoardBookService::class)->leadDays($r);
                        $rule = (float) (\App\Models\MeetingRule::where('code', 'POJK33-BAHAN')->value('internal_value') ?? 5);

                        return $lead === null ? 'Board book belum terbit' : "Terbit {$lead} hari sebelum rapat (aturan ≥ {$rule} hari) — ".($lead >= $rule ? 'tepat waktu' : 'TERLAMBAT');
                    })
                    ->color(fn (Meeting $r) => ($l = app(BoardBookService::class)->leadDays($r)) !== null && $l < 5 ? 'danger' : 'success'),
                TextEntry::make('notes')->label('Catatan')->columnSpanFull()->placeholder('—')->visible(fn (Meeting $r) => filled($r->notes)),
            ]),
            ViewEntry::make('chain')->view('filament.meetings.overview')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_at', 'desc')
            ->columns([
                TextColumn::make('scheduled_at')->label('Waktu')->dateTime('d M Y · H:i')->sortable(),
                TextColumn::make('organ.short_name')->label('Organ')->badge()->color(fn (Meeting $r) => match ($r->organ->kind->value) {
                    'direksi' => 'success', 'dekom' => 'primary', 'gabungan' => 'info', default => 'warning',
                }),
                TextColumn::make('title')->label('Rapat')->searchable()->wrap()->description(fn (Meeting $r) => $r->number),
                TextColumn::make('agenda_items_count')->label('Agenda')->counts('agendaItems')->alignCenter(),
                TextColumn::make('attendance')->label('Hadir')->alignCenter()
                    ->state(function (Meeting $r) {
                        $voting = $r->participants->where('has_vote', true);
                        $actual = $voting->whereNotNull('attended');

                        return $actual->isEmpty() ? 'RSVP '.$voting->where('rsvp.value', 'hadir')->count().'/'.$voting->count() : $voting->where('attended', true)->count().'/'.$voting->count();
                    }),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('participants'))
            ->filters([
                SelectFilter::make('organ_id')->label('Organ')->options(fn () => Organ::whereIn('id', Access::organIds(static::me()))->orderBy('sort')->pluck('name', 'id')),
                SelectFilter::make('status')->options(MeetingStatus::class),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            AgendaItemsRelationManager::class,
            ParticipantsRelationManager::class,
            BoardBooksRelationManager::class,
            DecisionsRelationManager::class,
            MinutesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetings::route('/'),
            'create' => CreateMeeting::route('/create'),
            'view' => ViewMeeting::route('/{record}'),
            'edit' => EditMeeting::route('/{record}/edit'),
        ];
    }
}
