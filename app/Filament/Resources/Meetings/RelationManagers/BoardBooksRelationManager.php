<?php

namespace App\Filament\Resources\Meetings\RelationManagers;

use App\Models\BoardBook;
use App\Services\BoardBookService;
use App\Support\Access;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class BoardBooksRelationManager extends RelationManager
{
    protected static string $relationship = 'boardBooks';

    protected static ?string $title = 'Board book';

    protected static ?string $modelLabel = 'versi board book';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        $me = auth()->user();
        $meeting = $this->getOwnerRecord();
        $voters = $meeting->participants()->where('has_vote', true)->count();

        return $table
            ->description('Paket yang sudah terbit tidak dapat diubah. Setiap revisi menjadi versi baru dengan daftar perubahan, dan seluruh penerima diberi tahu.')
            ->defaultSort('version_no', 'desc')
            ->columns([
                TextColumn::make('version_no')->label('Versi')->formatStateUsing(fn ($state) => 'v'.$state)->weight('bold'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('published_at')->label('Terbit')->dateTime('d M Y H:i'),
                TextColumn::make('lead')->label('Sebelum rapat')->state(fn (BoardBook $r) => $r->published_at ? round($r->published_at->diffInHours($meeting->scheduled_at, false) / 24, 1).' hari' : '—')
                    ->color(fn (BoardBook $r) => $r->published_at && $r->published_at->diffInHours($meeting->scheduled_at, false) < 120 ? 'danger' : null),
                TextColumn::make('docs')->label('Isi')->state(fn (BoardBook $r) => collect($r->manifest)->sum(fn ($a) => count($a['documents'])).' dokumen / '.count($r->manifest ?? []).' agenda'),
                TextColumn::make('change_log')->label('Perubahan')->wrap()->limit(160),
                TextColumn::make('reads_count')->label('Dibuka')->counts('reads')->formatStateUsing(fn ($state) => "{$state}/{$voters}"),
                TextColumn::make('manifest_hash')->label('Sidik SHA-256')->limit(14)->fontFamily('mono')->copyable(),
            ])
            ->headerActions([
                Action::make('publish')->label('Terbitkan versi baru')->icon('heroicon-o-paper-airplane')
                    ->visible(fn () => Access::canManageMeeting($me, $meeting))
                    ->schema([Textarea::make('note')->label('Catatan perubahan (wajib untuk revisi)')])
                    ->modalDescription('Manifes dibentuk dari bahan setiap agenda saat ini dan dikunci dengan sidik SHA-256.')
                    ->action(function (array $data) use ($meeting, $me) {
                        try {
                            $book = app(BoardBookService::class)->publish($meeting, $me, $data['note'] ?? null);
                            Notification::make()->title("Board book v{$book->version_no} terbit.")->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title($e->getMessage())->warning()->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('manifest')->label('Daftar isi')->icon('heroicon-o-list-bullet')->color('gray')
                    ->modalHeading(fn (BoardBook $r) => 'Board book v'.$r->version_no)
                    ->modalContent(fn (BoardBook $r) => view('filament.meetings.manifest', ['book' => $r, 'meeting' => $meeting]))
                    ->modalSubmitAction(false)
                    ->action(fn () => null)
                    ->after(fn (BoardBook $r) => app(BoardBookService::class)->markRead($r, $me)),
            ]);
    }
}
