<?php

namespace App\Filament\Resources\Circulars;

use App\Enums\CircularStatus;
use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Circulars\Pages\CreateCircular;
use App\Filament\Resources\Circulars\Pages\ListCirculars;
use App\Filament\Resources\Circulars\Pages\ViewCircular;
use App\Models\CircularResolution;
use App\Models\Organ;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

class CircularResolutionResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = CircularResolution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Keputusan & Tindak Lanjut';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'keputusan sirkuler';

    protected static ?string $pluralModelLabel = 'Keputusan Sirkuler';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'circular-resolutions';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('organ_id', Access::organIds(static::me()))->with('organ', 'responses');
    }

    public static function canCreate(): bool
    {
        return Access::canManageAnyMeeting(static::me());
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Usulan keputusan di luar rapat')->columns(2)->columnSpanFull()->schema([
                Select::make('organ_id')->label('Organ')->required()
                    ->options(fn () => Organ::whereIn('id', Access::managedOrganIds(static::me()))->whereIn('kind', ['direksi', 'dekom'])->pluck('name', 'id')),
                TextInput::make('number')->label('Nomor')->placeholder('SRK-DIR/05/2026'),
                TextInput::make('title')->label('Perihal')->required()->columnSpanFull(),
                MarkdownEditor::make('body')->label('Naskah usulan keputusan')->required()->columnSpanFull(),
                DateTimePicker::make('deadline_at')->label('Batas tanggapan')->seconds(false)->required()->default(now()->addDays(5)->setTime(17, 0))
                    ->helperText('Sah hanya bila seluruh anggota menyetujui secara tertulis sebelum batas ini.'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('number')->label('Nomor')->weight('bold'),
                TextEntry::make('organ.name')->label('Organ'),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('deadline_at')->label('Batas tanggapan')->dateTime('d M Y H:i'),
                TextEntry::make('title')->label('Perihal')->columnSpan(4)->weight('bold'),
                TextEntry::make('body')->label('Naskah')->columnSpan(4)->markdown(),
            ]),
            ViewEntry::make('responses')->view('filament.decisions.circular-responses')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->fontFamily('mono')->size('sm'),
                TextColumn::make('title')->label('Perihal')->wrap()->searchable(),
                TextColumn::make('organ.short_name')->label('Organ')->badge()->color('primary'),
                TextColumn::make('deadline_at')->label('Batas')->dateTime('d M Y H:i'),
                TextColumn::make('tanggapan')->label('Tanggapan')->state(fn (CircularResolution $r) => $r->responses->where('response', 'setuju')->count().' setuju · '.$r->responses->where('response', 'tidak_setuju')->count().' tidak · '.$r->responses->where('response', 'menunggu')->count().' menunggu'),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([SelectFilter::make('status')->options(CircularStatus::class)])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCirculars::route('/'),
            'create' => CreateCircular::route('/create'),
            'view' => ViewCircular::route('/{record}'),
        ];
    }
}
