<?php

namespace App\Filament\Resources\Meetings\RelationManagers;

use Illuminate\Database\Eloquent\Builder;
use App\Enums\MinuteStatus;
use App\Enums\SignatureStatus;
use App\Filament\Pages\AsistenAi;
use App\Models\Minute;
use App\Services\MinuteService;
use App\Support\Access;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class MinutesRelationManager extends RelationManager
{
    protected static string $relationship = 'minutes';

    protected static ?string $title = 'Risalah';

    protected static ?string $modelLabel = 'risalah';

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function run(callable $fn, string $ok): void
    {
        try {
            $fn();
            Notification::make()->title($ok)->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function table(Table $table): Table
    {
        $me = auth()->user();
        $meeting = $this->getOwnerRecord();
        $manage = Access::canManageMeeting($me, $meeting);
        $svc = app(MinuteService::class);
        $mySignature = fn (Minute $r) => $r->signatures->firstWhere('user_id', $me->id);

        return $table
            ->description('Draf → reviu → edaran tanda tangan → final. Risalah final tidak ditimpa; koreksi dibuat sebagai addendum yang tertaut.')
            ->defaultSort('version_no', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('signatures.user', 'corrects'))
            ->columns([
                TextColumn::make('version_no')->label('Versi')->formatStateUsing(fn ($state, Minute $r) => 'v'.$state.($r->kind === 'addendum' ? ' (addendum)' : ''))->weight('bold')
                    ->description(fn (Minute $r) => $r->corrects ? 'Mengoreksi v'.$r->corrects->version_no.': '.$r->correction_reason : null),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('sigs')->label('Tanda tangan')->state(fn (Minute $r) => $r->signatures->isEmpty() ? '—'
                    : $r->signatures->where('status', SignatureStatus::Ditandatangani)->count().'/'.$r->signatures->count().' ditandatangani')
                    ->description(fn (Minute $r) => $r->signatures->where('status', '!=', SignatureStatus::Ditandatangani)->map(fn ($s) => $s->user->name.': '.$s->status->getLabel().($s->reason ? ' — "'.$s->reason.'"' : ''))->implode(' · ') ?: null)
                    ->wrap(),
                TextColumn::make('finalized_at')->label('Final')->dateTime('d M Y H:i')->placeholder('—'),
                TextColumn::make('content_hash')->label('Sidik')->limit(12)->fontFamily('mono')->placeholder('—'),
            ])
            ->headerActions([
                Action::make('draft')->label('Buat draf dari data rapat')->icon('heroicon-o-document-plus')
                    ->visible($manage)
                    ->action(fn () => $this->run(fn () => $svc->createDraft($meeting, $me), 'Draf risalah dibuat dari kehadiran, kuorum, agenda, dan keputusan.')),
                Action::make('aiDraft')->label('Draf dari catatan (AI)')->icon('heroicon-o-sparkles')->color('gray')
                    ->visible($manage)
                    ->url(AsistenAi::getUrl(['fitur' => 'draft_risalah', 'rapat' => $meeting->id])),
            ])
            ->recordActions([
                Action::make('read')->label('Baca')->icon('heroicon-o-eye')->color('gray')
                    ->modalHeading(fn (Minute $r) => 'Risalah v'.$r->version_no)
                    ->modalContent(fn (Minute $r) => view('filament.meetings.minute', ['minute' => $r]))
                    ->modalSubmitAction(false),
                Action::make('sign')->label('Tanda tangani')->icon('heroicon-o-pencil-square')->color('success')
                    ->visible(fn (Minute $r) => ! $me->isReadOnly() && $r->status === MinuteStatus::EdarTtd && $mySignature($r)?->status === SignatureStatus::Menunggu)
                    ->requiresConfirmation()
                    ->modalDescription('Tanda tangan simulasi. Pada implementasi, diteruskan ke penyedia e-sign yang dipilih perusahaan.')
                    ->action(fn (Minute $r) => $this->run(fn () => $svc->sign($r, $me), 'Risalah ditandatangani.')),
                Action::make('refuse')->label('Tidak menandatangani')->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn (Minute $r) => ! $me->isReadOnly() && $r->status === MinuteStatus::EdarTtd && $mySignature($r)?->status === SignatureStatus::Menunggu)
                    ->schema([Textarea::make('reason')->label('Alasan tertulis')->required()])
                    ->action(fn (Minute $r, array $data) => $this->run(fn () => $svc->refuse($r, $me, $data['reason']), 'Alasan dicatat dan dilekatkan pada risalah.')),
                ActionGroup::make([
                    Action::make('edit')->label('Ubah isi')->icon('heroicon-o-pencil')
                        ->visible(fn (Minute $r) => in_array($r->status, [MinuteStatus::Draf, MinuteStatus::Reviu], true))
                        ->fillForm(fn (Minute $r) => ['body' => $r->body])
                        ->schema([MarkdownEditor::make('body')->label('Isi risalah')->required()])
                        ->modalWidth('5xl')
                        ->action(fn (Minute $r, array $data) => $this->run(fn () => $r->update(['body' => $data['body']]), 'Draf disimpan.')),
                    Action::make('review')->label('Kirim untuk reviu')->icon('heroicon-o-users')
                        ->visible(fn (Minute $r) => $r->status === MinuteStatus::Draf)
                        ->action(fn (Minute $r) => $this->run(fn () => $svc->sendForReview($r), 'Draf dikirim untuk reviu peserta.')),
                    Action::make('circulate')->label('Edarkan untuk tanda tangan')->icon('heroicon-o-paper-airplane')
                        ->visible(fn (Minute $r) => in_array($r->status, [MinuteStatus::Draf, MinuteStatus::Reviu], true))
                        ->requiresConfirmation()
                        ->action(fn (Minute $r) => $this->run(fn () => $svc->circulate($r), 'Risalah diedarkan; isi dikunci dengan sidik SHA-256.')),
                    Action::make('finalize')->label('Tetapkan final')->icon('heroicon-o-lock-closed')
                        ->visible(fn (Minute $r) => $r->status === MinuteStatus::EdarTtd)
                        ->requiresConfirmation()
                        ->modalDescription('Penanda tangan yang menolak tetap tercatat beserta alasannya.')
                        ->action(fn (Minute $r) => $this->run(fn () => $svc->finalize($r), 'Risalah ditetapkan final.')),
                    Action::make('addendum')->label('Buat koreksi/addendum')->icon('heroicon-o-document-duplicate')
                        ->visible(fn (Minute $r) => $r->status === MinuteStatus::Final)
                        ->schema([
                            Textarea::make('reason')->label('Alasan koreksi')->required(),
                            MarkdownEditor::make('body')->label('Isi addendum')->required(),
                        ])
                        ->action(fn (Minute $r, array $data) => $this->run(fn () => $svc->correct($r, $data['reason'], $data['body'], $me), 'Addendum dibuat sebagai draf; risalah asal tidak berubah.')),
                ])->visible($manage),
            ]);
    }
}
