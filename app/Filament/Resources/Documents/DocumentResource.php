<?php

namespace App\Filament\Resources\Documents;

use App\Enums\Classification;
use App\Enums\DataOrigin;
use App\Filament\Concerns\UsesAccess;
use App\Filament\Pages\AsistenAi;
use App\Filament\Pages\RuangBaca;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Company;
use App\Models\Document;
use App\Models\Organ;
use App\Services\DocumentService;
use App\Support\Access;
use App\Support\Audit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DocumentResource extends Resource
{
    use UsesAccess;

    public const KINDS = [
        'laporan_tahunan' => 'Laporan tahunan', 'kertas_usulan' => 'Kertas usulan', 'laporan_kinerja' => 'Laporan kinerja',
        'kebijakan' => 'Kebijakan/pedoman', 'risalah' => 'Risalah', 'bukti' => 'Bukti tindak lanjut', 'kajian' => 'Kajian', 'lainnya' => 'Lainnya',
    ];

    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Rapat';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'dokumen';

    protected static ?string $pluralModelLabel = 'Pustaka Dokumen';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('id', Access::readableDocumentIds(static::me()))->with('organ', 'company');
    }

    public static function canCreate(): bool
    {
        return static::writable() && (static::me()->isSecretariat() || static::me()->role?->value === 'admin');
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dokumen')->columns(2)->columnSpanFull()->schema([
                TextInput::make('title')->label('Judul')->required()->columnSpanFull(),
                Select::make('company_id')->label('Perusahaan')->options(fn () => Company::pluck('short_name', 'id'))->default(fn () => Company::whereNull('parent_id')->value('id'))->required(),
                Select::make('organ_id')->label('Organ pemilik (kosong = seluruh dewan)')->options(fn () => Organ::orderBy('sort')->pluck('name', 'id')),
                Select::make('kind')->label('Jenis')->options(self::KINDS)->required()->default('kertas_usulan'),
                Select::make('classification')->label('Klasifikasi')->options(Classification::class)->required()->default('rahasia'),
                Select::make('data_origin')->label('Asal data')->options(DataOrigin::class)->default('simulated')->required(),
                TextInput::make('source_ref')->label('Rujukan sumber'),
                Toggle::make('ai_allowed')->label('Boleh dibaca Asisten AI')->default(true)
                    ->helperText('Bila dimatikan, isi dokumen tidak pernah dikirim ke layanan AI.'),
                FileUpload::make('file_path')->label('Berkas PDF')->disk('local')->directory('dokumen')->acceptedFileTypes(['application/pdf'])
                    ->maxSize((int) config('mufakat.max_upload_kb'))->columnSpanFull()
                    ->helperText('Teks per halaman diekstrak otomatis untuk ruang baca dan rujukan AI.'),
                Textarea::make('text')->label('Atau tempel teks (pisahkan halaman dengan baris ---)')->rows(6)->columnSpanFull()->dehydrated(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->wrap()->weight('medium')
                    ->description(fn (Document $r) => self::KINDS[$r->kind] ?? $r->kind),
                TextColumn::make('version_no')->label('Versi')->formatStateUsing(fn ($state) => 'v'.$state)->alignCenter(),
                TextColumn::make('organ.short_name')->label('Organ')->placeholder('Seluruh dewan'),
                TextColumn::make('classification')->label('Klasifikasi')->badge(),
                TextColumn::make('data_origin')->label('Asal data')->badge(),
                TextColumn::make('page_count')->label('Hlm.')->alignCenter(),
                IconColumn::make('ai_allowed')->label('AI')->boolean(),
                TextColumn::make('sha256')->label('Sidik')->limit(10)->fontFamily('mono')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kind')->label('Jenis')->options(self::KINDS),
                SelectFilter::make('classification')->label('Klasifikasi')->options(Classification::class),
                SelectFilter::make('data_origin')->label('Asal data')->options(DataOrigin::class),
                TernaryFilter::make('latest')->label('Hanya versi terbaru')->default(true)
                    ->queries(true: fn (Builder $query) => $query->whereNotIn('id', Document::whereNotNull('previous_version_id')->pluck('previous_version_id')), false: fn (Builder $query) => $query),
            ])
            ->recordUrl(fn (Document $r) => RuangBaca::getUrl(['dokumen' => $r->id]))
            ->recordActions([
                Action::make('read')->label('Baca')->icon('heroicon-o-book-open')->url(fn (Document $r) => RuangBaca::getUrl(['dokumen' => $r->id])),
                Action::make('compare')->label('Bandingkan versi (AI)')->icon('heroicon-o-arrows-right-left')->color('gray')
                    ->visible(fn (Document $r) => $r->previous_version_id !== null)
                    ->url(fn (Document $r) => AsistenAi::getUrl(['fitur' => 'bandingkan_versi', 'dokumen' => $r->id])),
                Action::make('newVersion')->label('Versi baru')->icon('heroicon-o-document-duplicate')->color('gray')
                    ->visible(fn () => static::canCreate())
                    ->schema([
                        Textarea::make('change_summary')->label('Ringkasan perubahan')->required(),
                        FileUpload::make('file_path')->label('Berkas PDF')->disk('local')->directory('dokumen')->acceptedFileTypes(['application/pdf'])->maxSize((int) config('mufakat.max_upload_kb')),
                        Textarea::make('text')->label('Atau tempel teks')->rows(5),
                    ])
                    ->action(function (Document $r, array $data) {
                        $svc = app(DocumentService::class);
                        $doc = $svc->newVersion($r, ['change_summary' => $data['change_summary'], 'file_path' => $data['file_path'] ?? null], auth()->user());
                        static::ingest($doc, $data['text'] ?? null);
                        Access::flush();
                        Notification::make()->title("Versi {$doc->version_no} disimpan; versi lama tetap tersedia.")->success()->send();
                    }),
            ]);
    }

    /** Ekstrak isi dokumen dari PDF atau teks tempel. */
    public static function ingest(Document $doc, ?string $text): void
    {
        $svc = app(DocumentService::class);

        if ($doc->file_path) {
            $disk = \Illuminate\Support\Facades\Storage::disk('local');
            $doc->forceFill(['mime' => $disk->mimeType($doc->file_path), 'size' => $disk->size($doc->file_path)])->save();
            $svc->extractPdf($doc);
        } elseif (filled($text)) {
            $svc->storeText($doc, $text);
        } else {
            $svc->refresh($doc);
        }

        Audit::log('dokumen_unggah', "Dokumen \"{$doc->title}\" v{$doc->version_no} ({$doc->page_count} hlm.)", $doc);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
        ];
    }
}
