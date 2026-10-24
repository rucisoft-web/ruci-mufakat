<?php

namespace App\Filament\Resources\Tasks;

use App\Enums\TaskStatus;
use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\Pages\ViewTask;
use App\Models\Company;
use App\Models\Decision;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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

class TaskResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Keputusan & Tindak Lanjut';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'tugas tindak lanjut';

    protected static ?string $pluralModelLabel = 'Tindak Lanjut';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return Access::scopeTasks(parent::getEloquentQuery(), static::me())->with('pic', 'verifier', 'decision');
    }

    public static function getNavigationBadge(): ?string
    {
        $late = static::getEloquentQuery()->where('status', '!=', TaskStatus::Selesai->value)->whereDate('due_date', '<', today())->count();

        return $late ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Tugas melewati tenggat';
    }

    public static function canCreate(): bool
    {
        return static::writable() && static::me()->isSecretariat();
    }

    public static function canEdit(Model $record): bool
    {
        return static::writable() && static::me()->isSecretariat() && $record->status !== TaskStatus::Selesai;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Penugasan')->columns(2)->columnSpanFull()->schema([
                Select::make('company_id')->label('Perusahaan')->options(fn () => Company::pluck('short_name', 'id'))->default(fn () => Company::whereNull('parent_id')->value('id'))->required(),
                Select::make('decision_id')->label('Keputusan asal')->searchable()
                    ->options(fn () => Decision::whereIn('organ_id', Access::organIds(static::me()))->latest('decided_on')->limit(200)->get()->mapWithKeys(fn ($d) => [$d->id => $d->number.' — '.$d->title])),
                TextInput::make('code')->label('Kode')->placeholder('TL-2026-013'),
                Select::make('priority')->label('Prioritas')->options(['tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'rendah' => 'Rendah'])->default('sedang'),
                TextInput::make('title')->label('Tugas')->required()->columnSpanFull(),
                Textarea::make('description')->label('Uraian dan kriteria selesai')->columnSpanFull()
                    ->helperText('Tuliskan bukti yang diharapkan. Tugas hanya selesai setelah bukti diverifikasi.'),
                Select::make('pic_id')->label('PIC')->required()->searchable()->options(fn () => User::where('is_active', true)->whereNotIn('role', ['tamu', 'tamu_demo'])->orderBy('name')->pluck('name', 'id')),
                Select::make('verifier_id')->label('Verifikator')->searchable()->options(fn () => User::where('is_active', true)->whereNotIn('role', ['tamu', 'tamu_demo'])->orderBy('name')->pluck('name', 'id'))
                    ->different('pic_id')->helperText('Tidak boleh sama dengan PIC.'),
                DatePicker::make('due_date')->label('Tenggat')->required(),
                Repeater::make('milestones')->relationship()->label('Tonggak')->columnSpanFull()->defaultItems(0)->columns(3)->schema([
                    TextInput::make('title')->label('Tonggak')->required()->columnSpan(2),
                    DatePicker::make('due_date')->label('Tanggal'),
                ]),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('code')->label('Kode')->weight('bold'),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('progress')->label('Kemajuan')->formatStateUsing(fn ($state, Task $r) => $state.'%'.($state >= 100 && $r->status !== TaskStatus::Selesai ? ' — belum selesai sampai bukti diverifikasi' : '')),
                TextEntry::make('due_date')->label('Tenggat')->date('d M Y')
                    ->color(fn (Task $r) => app(TaskService::class)->isOverdue($r) ? 'danger' : null)
                    ->belowContent(fn (Task $r) => app(TaskService::class)->isOverdue($r) ? 'Terlambat '.$r->due_date->diffInDays(today()).' hari' : ($r->original_due_date && ! $r->original_due_date->eq($r->due_date) ? 'Semula '.$r->original_due_date->format('d M Y') : null)),
                TextEntry::make('title')->label('Tugas')->columnSpan(4)->weight('bold'),
                TextEntry::make('description')->label('Uraian')->columnSpan(4)->placeholder('—'),
                TextEntry::make('pic.name')->label('PIC'),
                TextEntry::make('verifier.name')->label('Verifikator')->placeholder('—'),
                TextEntry::make('rejection_count')->label('Bukti ditolak')->formatStateUsing(fn ($state) => $state ? $state.' kali' : '—'),
                TextEntry::make('data_origin')->label('Asal data')->badge(),
            ]),
            ViewEntry::make('timeline')->view('filament.tasks.timeline')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date')
            ->columns([
                TextColumn::make('code')->label('Kode')->fontFamily('mono')->size('sm')->searchable(),
                TextColumn::make('title')->label('Tugas')->wrap()->searchable()->description(fn (Task $r) => $r->decision?->number),
                TextColumn::make('pic.name')->label('PIC'),
                TextColumn::make('due_date')->label('Tenggat')->date('d M Y')->sortable()
                    ->color(fn (Task $r) => app(TaskService::class)->isOverdue($r) ? 'danger' : null)
                    ->description(fn (Task $r) => app(TaskService::class)->isOverdue($r) ? 'terlambat' : null),
                TextColumn::make('progress')->label('Kemajuan')->suffix('%')->alignEnd(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(TaskStatus::class),
                Filter::make('mine')->label('Tugas saya (PIC/verifikator)')->query(fn (Builder $query) => $query->where(fn ($w) => $w->where('pic_id', auth()->id())->orWhere('verifier_id', auth()->id()))),
                Filter::make('late')->label('Terlambat')->query(fn (Builder $query) => $query->where('status', '!=', 'selesai')->whereDate('due_date', '<', today())),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'view' => ViewTask::route('/{record}'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}
