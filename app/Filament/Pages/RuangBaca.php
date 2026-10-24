<?php

namespace App\Filament\Pages;

use App\Models\Annotation;
use App\Models\Document;
use App\Models\Meeting;
use App\Services\BoardBookService;
use App\Support\Access;
use App\Support\Audit;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Ruang baca digital: halaman demi halaman, pencarian, anotasi pribadi/bersama.
 * Setiap pembukaan dokumen diperiksa haknya; penolakan dicatat di jejak audit.
 */
class RuangBaca extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Rapat';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Ruang Baca';

    protected static ?string $title = 'Ruang Baca';

    protected static ?string $slug = 'ruang-baca';

    protected string $view = 'filament.pages.ruang-baca';

    #[Url(as: 'dokumen')]
    public ?int $documentId = null;

    #[Url(as: 'rapat')]
    public ?int $meetingId = null;

    #[Url(as: 'halaman')]
    public ?int $page = null;

    #[Url(as: 'cari')]
    public string $search = '';

    public string $noteBody = '';

    public string $noteQuote = '';

    public string $noteKind = 'catatan';

    public string $noteVisibility = 'pribadi';

    public function mount(): void
    {
        $me = auth()->user();

        if ($this->meetingId) {
            $meeting = Meeting::find($this->meetingId);
            if (! $meeting || ! Access::canSeeMeeting($me, $meeting)) {
                $meeting && Access::deny($me, 'membuka bahan rapat "'.$meeting->title.'"', $meeting);
                abort(403, 'Anda tidak berhak membuka bahan rapat ini.');
            }
            $book = $meeting->boardBooks()->where('status', 'terbit')->latest('version_no')->first();
            if ($book && ! $me->isDemoGuest()) {
                app(BoardBookService::class)->markRead($book, $me);
            }
        }

        if ($this->documentId) {
            $this->open($this->documentId);
        } else {
            $this->documentId = $this->toc()->first()?->id;
        }
    }

    public function open(int $id, ?int $page = null): void
    {
        $me = auth()->user();
        $doc = Document::find($id);

        if (! $doc || ! Access::canReadDocument($me, $doc)) {
            $doc && Access::deny($me, 'membuka dokumen "'.$doc->title.'"', $doc);
            Notification::make()->title('Anda tidak berhak membuka dokumen ini. Percobaan akses dicatat di jejak audit.')->danger()->persistent()->send();
            $this->documentId = null;

            return;
        }

        $this->documentId = $id;
        $this->page = $page ?? $this->page;
        Audit::log('dokumen_dibuka', 'Membuka "'.$doc->title.'" v'.$doc->version_no, $doc);
    }

    public function goTo(int $page): void
    {
        $this->page = $page;
    }

    public function addNote(): void
    {
        $me = auth()->user();
        abort_if($me->isReadOnly() || ! $this->documentId, 403);
        $this->validate(['noteBody' => 'required|string|max:2000', 'noteKind' => 'in:catatan,penanda,pertanyaan', 'noteVisibility' => 'in:pribadi,bersama']);

        Annotation::create([
            'document_id' => $this->documentId, 'user_id' => $me->id, 'page_no' => $this->currentPage()?->page_no ?? 1,
            'kind' => $this->noteKind, 'visibility' => $this->noteVisibility, 'quote' => $this->noteQuote ?: null, 'body' => $this->noteBody,
        ]);
        $this->reset('noteBody', 'noteQuote');
        Notification::make()->title($this->noteVisibility === 'pribadi' ? 'Catatan pribadi disimpan (hanya Anda yang melihat).' : 'Catatan bersama disimpan.')->success()->send();
    }

    public function document(): ?Document
    {
        return $this->documentId ? Document::with('pages', 'previousVersion', 'organ')->find($this->documentId) : null;
    }

    public function currentPage()
    {
        $doc = $this->document();

        return $doc?->pages->firstWhere('page_no', $this->page) ?? $doc?->pages->sortBy('page_no')->first();
    }

    /** Daftar isi: bahan rapat (bila dibuka dari rapat) atau seluruh dokumen yang boleh dibaca. */
    public function toc(): Collection
    {
        $me = auth()->user();
        $ids = Access::readableDocumentIds($me);

        if ($this->meetingId && ($meeting = Meeting::find($this->meetingId))) {
            return $meeting->agendaItems()->with('documents')->orderBy('seq')->get()
                ->filter(fn ($i) => Access::canSeeAgendaItem($me, $i))
                ->flatMap(fn ($i) => $i->documents->map(fn ($d) => $d->setAttribute('agenda_label', 'Agenda '.$i->seq)))
                ->filter(fn ($d) => $ids->contains($d->id))->unique('id')->values();
        }

        $superseded = Document::whereNotNull('previous_version_id')->pluck('previous_version_id');

        return Document::whereIn('id', $ids)->whereNotIn('id', $superseded)->orderBy('kind')->orderBy('title')->get();
    }

    protected function getViewData(): array
    {
        $me = auth()->user();
        $doc = $this->document();
        $current = $this->currentPage();
        $hits = collect();

        if ($doc && mb_strlen(trim($this->search)) >= 3) {
            $hits = $doc->pages->filter(fn ($p) => mb_stripos($p->content.' '.$p->heading, $this->search) !== false)->pluck('page_no');
        }

        $notes = $doc ? Annotation::with('user')->where('document_id', $doc->id)
            ->where(fn ($q) => $q->where('user_id', $me->id)->orWhere('visibility', 'bersama'))->orderBy('page_no')->latest()->get() : collect();

        $content = $current ? e($current->content) : '';
        if ($content && mb_strlen(trim($this->search)) >= 3) {
            $content = preg_replace('/('.preg_quote(e($this->search), '/').')/iu', '<mark>$1</mark>', $content);
        }

        return [
            'me' => $me,
            'doc' => $doc,
            'current' => $current,
            'content' => $content,
            'toc' => $this->toc(),
            'hits' => $hits,
            'notes' => $notes,
            'meeting' => $this->meetingId ? Meeting::find($this->meetingId) : null,
        ];
    }
}
