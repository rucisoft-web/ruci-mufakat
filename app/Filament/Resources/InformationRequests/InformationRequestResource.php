<?php

namespace App\Filament\Resources\InformationRequests;

use App\Enums\InfoRequestStatus;
use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\InformationRequests\Pages\CreateInformationRequest;
use App\Filament\Resources\InformationRequests\Pages\ListInformationRequests;
use App\Filament\Resources\InformationRequests\Pages\ViewInformationRequest;
use App\Models\Document;
use App\Models\InformationRequest;
use App\Models\Meeting;
use App\Models\Organ;
use App\Models\User;
use App\Services\InformationRequestService;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class InformationRequestResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = InformationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Keputusan & Tindak Lanjut';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'permintaan informasi';

    protected static ?string $pluralModelLabel = 'Permintaan Informasi';

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getEloquentQuery(): Builder
    {
        return Access::scopeInfoRequests(parent::getEloquentQuery(), static::me())->with('requester', 'pic', 'organ');
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getEloquentQuery()->whereIn('status', ['diajukan', 'klarifikasi'])->where('sla_due_at', '<', now())->count();

        return $n ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return static::writable() && (static::me()->activeMemberships()->isNotEmpty() || static::me()->isSecretariat());
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $me = static::me();

        return $schema->components([
            Section::make('Pertanyaan kepada manajemen')->columns(2)->columnSpanFull()->schema([
                Select::make('organ_id')->label('Atas nama organ')->required()->default(fn () => $me->activeMemberships()->first()?->organ_id)
                    ->options(fn () => Organ::whereIn('id', $me->activeMemberships()->pluck('organ_id')->merge(Access::managedOrganIds($me)))->pluck('name', 'id')),
                Select::make('meeting_id')->label('Terkait rapat')->searchable()->default(fn () => request()->integer('rapat') ?: null)
                    ->options(fn () => Access::scopeMeetings(Meeting::query(), $me)->latest('scheduled_at')->limit(60)->pluck('title', 'id')),
                Select::make('document_id')->label('Dokumen')->searchable()->default(fn () => request()->integer('dokumen') ?: null)
                    ->options(fn () => Document::whereIn('id', Access::readableDocumentIds($me))->pluck('title', 'id')),
                TextInput::make('page_no')->label('Halaman')->numeric()->default(fn () => request()->integer('halaman') ?: null),
                TextInput::make('subject')->label('Perihal')->required()->columnSpanFull(),
                Textarea::make('question')->label('Pertanyaan')->required()->rows(4)->columnSpanFull(),
                Select::make('pic_id')->label('PIC manajemen')->required()->searchable()
                    ->options(fn () => User::where('is_active', true)->whereIn('role', ['direksi', 'pic', 'sekper'])->orderBy('name')->pluck('name', 'id')),
                Select::make('priority')->label('Prioritas')->options(['tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'rendah' => 'Rendah'])->default('sedang'),
                DateTimePicker::make('sla_due_at')->label('Batas jawaban (SLA)')->seconds(false)->default(now()->addWeekdays(3)->setTime(17, 0))->required(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $svc = app(InformationRequestService::class);

        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('code')->label('Kode')->weight('bold'),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('organ.name')->label('Organ'),
                TextEntry::make('sla_due_at')->label('SLA')->dateTime('d M Y H:i')
                    ->color(fn (InformationRequest $r) => $svc->isOverdue($r) || $svc->answeredLate($r) ? 'danger' : null)
                    ->belowContent(fn (InformationRequest $r) => $svc->isOverdue($r) ? 'Lewat SLA, belum dijawab' : ($svc->answeredLate($r) ? 'Dijawab setelah SLA' : null)),
                TextEntry::make('subject')->label('Perihal')->columnSpan(4)->weight('bold'),
                TextEntry::make('requester.name')->label('Penanya'),
                TextEntry::make('pic.name')->label('PIC'),
                TextEntry::make('source')->label('Sumber')->columnSpan(2)
                    ->state(fn (InformationRequest $r) => $r->document ? $r->document->title.($r->page_no ? ', hlm. '.$r->page_no : '') : '—')
                    ->url(fn (InformationRequest $r) => $r->document && Access::canReadDocument(static::me(), $r->document) ? \App\Filament\Pages\RuangBaca::getUrl(['dokumen' => $r->document_id, 'halaman' => $r->page_no]) : null),
            ]),
            ViewEntry::make('thread')->view('filament.info.thread')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $svc = app(InformationRequestService::class);

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Kode')->fontFamily('mono')->size('sm')->searchable(),
                TextColumn::make('subject')->label('Perihal')->wrap()->searchable()->description(fn (InformationRequest $r) => $r->requester->name.' → '.$r->pic->name),
                TextColumn::make('organ.short_name')->label('Organ')->badge()->color('primary'),
                TextColumn::make('sla_due_at')->label('SLA')->dateTime('d M Y')->sortable()
                    ->color(fn (InformationRequest $r) => $svc->isOverdue($r) || $svc->answeredLate($r) ? 'danger' : null)
                    ->description(fn (InformationRequest $r) => $svc->isOverdue($r) ? 'lewat SLA' : ($svc->answeredLate($r) ? 'dijawab terlambat' : null)),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(InfoRequestStatus::class),
                Filter::make('late')->label('Lewat SLA')->query(fn (Builder $query) => $query->whereIn('status', ['diajukan', 'klarifikasi'])->where('sla_due_at', '<', now())),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInformationRequests::route('/'),
            'create' => CreateInformationRequest::route('/create'),
            'view' => ViewInformationRequest::route('/{record}'),
        ];
    }
}
