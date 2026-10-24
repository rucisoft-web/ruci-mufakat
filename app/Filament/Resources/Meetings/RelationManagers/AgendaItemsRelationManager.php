<?php

namespace App\Filament\Resources\Meetings\RelationManagers;

use App\Enums\AgendaPurpose;
use App\Enums\Classification;
use App\Enums\DecisionKind;
use App\Enums\OrganKind;
use App\Filament\Pages\AsistenAi;
use App\Filament\Pages\RuangBaca;
use App\Models\AgendaItem;
use App\Models\Document;
use App\Models\User;
use App\Services\Ai\AiAssistant;
use App\Services\DecisionService;
use App\Support\Access;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class AgendaItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'agendaItems';

    protected static ?string $title = 'Agenda';

    protected static ?string $modelLabel = 'agenda';

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function me(): User
    {
        return auth()->user();
    }

    protected function canManage(): bool
    {
        return Access::canManageMeeting($this->me(), $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Agenda')->columns(3)->columnSpanFull()->schema([
                TextInput::make('seq')->label('No.')->numeric()->required()->default(fn () => $this->getOwnerRecord()->agendaItems()->max('seq') + 1),
                TextInput::make('title')->label('Judul agenda')->required()->columnSpan(2),
                Select::make('purpose')->label('Sifat')->options(AgendaPurpose::class)->required()->live(),
                Select::make('deciding_organ')->label('Organ pemutus')->options(['direksi' => 'Direksi', 'dekom' => 'Dewan Komisaris', 'komite' => 'Komite'])
                    ->visible(fn (Get $get) => in_array($get('purpose')?->value ?? $get('purpose'), ['keputusan', 'persetujuan', 'nasihat'], true)),
                Select::make('classification')->label('Klasifikasi')->options(Classification::class)->default('rahasia')->required(),
                Select::make('presenter_id')->label('Penyaji')->options(fn () => User::where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable(),
                Select::make('owner_id')->label('Pemilik bahan')->options(fn () => User::where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable(),
                TextInput::make('duration_min')->label('Durasi (menit)')->numeric()->default(30),
            ]),
            Section::make('Kertas usulan')->description('Wajib lengkap untuk agenda keputusan/persetujuan. Asisten AI dapat memeriksa kelengkapannya.')->columns(2)->columnSpanFull()->schema([
                Textarea::make('objective')->label('Tujuan pembahasan')->rows(2),
                Textarea::make('background')->label('Latar belakang')->rows(2),
                Textarea::make('alternatives')->label('Alternatif yang dipertimbangkan')->rows(2),
                Textarea::make('financial_impact')->label('Dampak keuangan')->rows(2),
                Textarea::make('risks')->label('Risiko')->rows(2),
                Textarea::make('recommendation')->label('Rekomendasi')->rows(2),
                TextInput::make('authority_basis')->label('Dasar kewenangan')->columnSpanFull(),
            ]),
            Section::make('Pelaksanaan')->columnSpanFull()->schema([
                Textarea::make('discussion')->label('Pokok pembahasan (untuk risalah)')->rows(4),
                Select::make('status')->options(['direncanakan' => 'Direncanakan', 'dibahas' => 'Dibahas', 'ditunda' => 'Ditunda', 'selesai' => 'Selesai'])->default('direncanakan'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        $me = $this->me();
        $recused = Access::recusedAgendaIds($me);
        $meeting = $this->getOwnerRecord();

        return $table
            ->defaultSort('seq')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['presenter', 'documents', 'conflicts.user'])
                // Tamu/presenter hanya melihat agenda yang ia bawakan.
                ->when($me->role === \App\Enums\UserRole::Tamu, fn ($q) => $q->where(fn ($w) => $w->where('presenter_id', $me->id)->orWhere('owner_id', $me->id))))
            ->columns([
                TextColumn::make('seq')->label('No.')->width('50px'),
                TextColumn::make('title')->label('Agenda')->wrap()->grow()->extraAttributes(['style' => 'min-width:320px'])->weight('semibold')
                    ->description(fn (AgendaItem $r) => $recused->contains($r->id) ? 'Anda mundur dari agenda ini karena benturan kepentingan; bahan ditutup.' : ($r->objective ? str($r->objective)->limit(110) : null)),
                TextColumn::make('purpose')->label('Sifat')->badge(),
                TextColumn::make('presenter.name')->label('Penyaji')->placeholder('—')->wrap(),
                TextColumn::make('classification')->label('Klasifikasi')->badge(),
                TextColumn::make('docs')->label('Bahan')->state(fn (AgendaItem $r) => $r->documents->count().' dok.')->color('gray'),
                TextColumn::make('completeness')->label('Kelengkapan')
                    ->state(function (AgendaItem $r) {
                        $c = collect(app(AiAssistant::class)->completeness($r));

                        return $c->where('ok', true)->count().'/'.$c->count();
                    })
                    ->badge()
                    ->color(fn ($state) => explode('/', $state)[0] === explode('/', $state)[1] ? 'success' : ((int) explode('/', $state)[0] >= 7 ? 'warning' : 'danger')),
                TextColumn::make('conflict')->label('Benturan')->state(fn (AgendaItem $r) => $r->conflicts->map(fn ($c) => $c->user->name)->implode(', '))->placeholder('—')->color('danger')->wrap(),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah agenda')->visible(fn () => $this->canManage()),
            ])
            ->recordActions([
                Action::make('read')->label('Baca bahan')->icon('heroicon-o-book-open')->color('gray')
                    ->visible(fn (AgendaItem $r) => $r->documents->isNotEmpty() && Access::canSeeAgendaItem($me, $r))
                    ->url(fn (AgendaItem $r) => RuangBaca::getUrl(['dokumen' => $r->documents->first()->id, 'rapat' => $meeting->id])),
                Action::make('blocked')->label('Bahan tertutup')->icon('heroicon-o-lock-closed')->color('danger')->disabled()
                    ->visible(fn (AgendaItem $r) => ! Access::canSeeAgendaItem($me, $r)),
                ActionGroup::make([
                    Action::make('critical')->label('Pertanyaan kritis (AI)')->icon('heroicon-o-light-bulb')
                        ->url(fn (AgendaItem $r) => AsistenAi::getUrl(['fitur' => 'pertanyaan_kritis', 'agenda' => $r->id])),
                    Action::make('complete')->label('Periksa kelengkapan')->icon('heroicon-o-check-badge')
                        ->url(fn (AgendaItem $r) => AsistenAi::getUrl(['fitur' => 'cek_kelengkapan', 'agenda' => $r->id])),
                    Action::make('attach')->label('Lampirkan dokumen')->icon('heroicon-o-paper-clip')
                        ->visible(fn () => $this->canManage())
                        ->schema([Select::make('documents')->label('Dokumen dari pustaka')->multiple()->searchable()
                            ->options(fn () => Document::orderByDesc('id')->get()->mapWithKeys(fn ($d) => [$d->id => $d->title.' (v'.$d->version_no.')']))])
                        ->fillForm(fn (AgendaItem $r) => ['documents' => $r->documents->pluck('id')->all()])
                        ->action(function (AgendaItem $r, array $data) {
                            $r->documents()->sync($data['documents'] ?? []);
                            $r->update(['materials_submitted_at' => $r->materials_submitted_at ?? now()]);
                            Notification::make()->title('Bahan agenda diperbarui. Terbitkan board book versi baru agar penerima diberi tahu.')->success()->send();
                        }),
                    Action::make('decide')->label('Catat keputusan')->icon('heroicon-o-scale')
                        ->visible(fn (AgendaItem $r) => $this->canManage() && in_array($r->purpose->value, ['keputusan', 'persetujuan', 'nasihat', 'diskusi'], true))
                        ->modalWidth('3xl')
                        ->schema(fn (AgendaItem $r) => $this->decisionSchema($r))
                        ->action(function (AgendaItem $r, array $data) {
                            try {
                                $votes = [];
                                foreach (['setuju', 'tidak_setuju', 'abstain'] as $v) {
                                    foreach ($data['vote_'.$v] ?? [] as $uid) {
                                        $votes[(int) $uid] = $v;
                                    }
                                }
                                $dissents = collect($data['dissents'] ?? [])->filter(fn ($d) => filled($d['user_id'] ?? null))->mapWithKeys(fn ($d) => [(int) $d['user_id'] => $d['note']])->all();
                                $decision = app(DecisionService::class)->record($r, DecisionKind::from($data['kind']), [
                                    'text' => $data['text'], 'method' => $data['method'], 'conditions' => $data['conditions'] ?? null,
                                    'result' => $data['result'] ?: null, 'effective_on' => $data['effective_on'] ?? null,
                                ], $votes, $dissents);
                                Notification::make()->title("Keputusan {$decision->number} dicatat ({$decision->result->getLabel()}).")->success()->send();
                            } catch (Throwable $e) {
                                Notification::make()->title('Keputusan tidak dapat dicatat')->body($e->getMessage())->danger()->persistent()->send();
                            }
                        }),
                    EditAction::make()->visible(fn () => $this->canManage()),
                ]),
            ]);
    }

    protected function decisionSchema(AgendaItem $item): array
    {
        $meetingKind = $item->meeting->organ->kind;
        $kinds = match ($meetingKind) {
            OrganKind::Direksi => [DecisionKind::KeputusanDireksi],
            OrganKind::Dekom => [DecisionKind::PersetujuanDekom, DecisionKind::NasihatDekom],
            OrganKind::Komite => [DecisionKind::RekomendasiKomite],
            OrganKind::Gabungan => [DecisionKind::KeputusanDireksi, DecisionKind::PersetujuanDekom, DecisionKind::NasihatDekom],
        };
        $voters = function (Get $get) use ($item) {
            if (! $get('kind')) {
                return [];
            }
            $svc = app(DecisionService::class);

            return $svc->eligibleVoters($item, $svc->organFor($item, DecisionKind::from($get('kind'))))->pluck('name', 'id')->all();
        };

        return [
            Select::make('kind')->label('Jenis hasil')->required()->live()
                ->options(collect($kinds)->mapWithKeys(fn ($k) => [$k->value => $k->getLabel()]))
                ->helperText('Pada rapat gabungan, keputusan Direksi dan persetujuan/nasihat Dewan Komisaris dicatat terpisah; suara tidak digabung.'),
            Select::make('method')->label('Cara pengambilan')->options(['musyawarah' => 'Musyawarah untuk mufakat', 'voting' => 'Pemungutan suara'])->default('musyawarah')->required()->live(),
            Textarea::make('text')->label('Rumusan keputusan')->required()->rows(3),
            CheckboxList::make('vote_setuju')->label('Setuju')->options($voters)->columns(3)->visible(fn (Get $get) => $get('method') === 'voting'),
            CheckboxList::make('vote_tidak_setuju')->label('Tidak setuju')->options($voters)->columns(3)->visible(fn (Get $get) => $get('method') === 'voting'),
            CheckboxList::make('vote_abstain')->label('Abstain')->options($voters)->columns(3)->visible(fn (Get $get) => $get('method') === 'voting')
                ->helperText('Hanya anggota organ yang hadir dan tidak mundur karena benturan kepentingan yang tercantum.'),
            Repeater::make('dissents')->label('Pendapat berbeda (dissenting opinion)')->defaultItems(0)->columns(2)->schema([
                Select::make('user_id')->label('Anggota')->options($voters),
                Textarea::make('note')->label('Catatan')->rows(2),
            ])->visible(fn (Get $get) => $get('method') === 'voting'),
            Select::make('result')->label('Hasil (kosongkan untuk dihitung otomatis)')->options(['disetujui' => 'Disetujui', 'disetujui_bersyarat' => 'Disetujui bersyarat', 'ditolak' => 'Ditolak', 'ditunda' => 'Ditunda', 'disampaikan' => 'Disampaikan (nasihat/rekomendasi)']),
            Textarea::make('conditions')->label('Syarat berlaku')->rows(2),
            DatePicker::make('effective_on')->label('Tanggal berlaku'),
        ];
    }
}
