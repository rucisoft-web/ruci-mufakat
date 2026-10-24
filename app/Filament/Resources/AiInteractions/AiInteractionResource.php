<?php

namespace App\Filament\Resources\AiInteractions;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\AiInteractions\Pages\ManageAiInteractions;
use App\Models\AiInteraction;
use App\Services\Ai\AiAssistant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Riwayat penggunaan AI: pengguna melihat miliknya; auditor dan sekretariat melihat seluruhnya (tanpa isi bagi admin teknis). */
class AiInteractionResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = AiInteraction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Asisten AI';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'interaksi AI';

    protected static ?string $pluralModelLabel = 'Riwayat AI';

    protected static ?string $slug = 'ai-interactions';

    protected static function seesAll(): bool
    {
        return static::me()->isSecretariat() || in_array(static::me()->role?->value, ['auditor', 'tamu_demo'], true);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user')->when(! static::seesAll(), fn ($q) => $q->where('user_id', static::me()->id));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Pengguna')->visible(static::seesAll()),
                TextColumn::make('feature')->label('Fitur')->formatStateUsing(fn ($state) => AiAssistant::FEATURES[$state][0] ?? $state),
                TextColumn::make('prompt')->label('Permintaan')->limit(70)->wrap(),
                TextColumn::make('context_refs')->label('Hlm. dikirim')->state(fn (AiInteraction $r) => count($r->context_refs ?? []))->alignCenter(),
                TextColumn::make('status')->label('Status')->badge()->color(fn ($state) => ['berhasil' => 'success', 'referensi_demo' => 'warning', 'bukti_tidak_cukup' => 'warning', 'timeout' => 'danger', 'gagal' => 'danger'][$state] ?? 'gray')
                    ->formatStateUsing(fn ($state) => ['berhasil' => 'Berhasil', 'referensi_demo' => 'Referensi demo', 'bukti_tidak_cukup' => 'Bukti tidak cukup', 'timeout' => 'Timeout', 'gagal' => 'Gagal', 'ai_nonaktif' => 'AI nonaktif'][$state] ?? $state),
                TextColumn::make('model')->label('Model')->size('sm')->color('gray'),
                TextColumn::make('tokens')->label('Token')->state(fn (AiInteraction $r) => $r->input_tokens ? number_format($r->input_tokens + $r->output_tokens, 0, ',', '.') : '—')->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('feature')->label('Fitur')->options(collect(AiAssistant::FEATURES)->map(fn ($f) => $f[0])),
                SelectFilter::make('status')->options(['berhasil' => 'Berhasil', 'referensi_demo' => 'Referensi demo', 'bukti_tidak_cukup' => 'Bukti tidak cukup', 'timeout' => 'Timeout', 'gagal' => 'Gagal', 'ai_nonaktif' => 'AI nonaktif']),
            ])
            ->recordActions([
                Action::make('open')->label('Lihat')->icon('heroicon-o-eye')->color('gray')
                    ->modalHeading(fn (AiInteraction $r) => AiAssistant::FEATURES[$r->feature][0] ?? $r->feature)
                    ->modalContent(fn (AiInteraction $r) => view('filament.ai.output', ['interaction' => $r]))
                    ->modalWidth('5xl')->modalSubmitAction(false),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAiInteractions::route('/')];
    }
}
