<?php

namespace App\Filament\Pages;

use App\Models\AgendaItem;
use App\Models\AiInteraction;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\Organ;
use App\Services\Ai\AiAssistant;
use App\Support\Access;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

class AsistenAi extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Asisten AI';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Asisten AI';

    protected static ?string $title = 'Asisten AI';

    protected static ?string $slug = 'asisten-ai';

    protected string $view = 'filament.pages.asisten-ai';

    #[Url(as: 'fitur')]
    public string $feature = 'ringkasan_rapat';

    #[Url(as: 'rapat')]
    public ?int $meetingId = null;

    #[Url(as: 'agenda')]
    public ?int $agendaId = null;

    #[Url(as: 'dokumen')]
    public ?int $documentId = null;

    #[Url(as: 'organ')]
    public ?int $organId = null;

    #[Url(as: 'hasil')]
    public ?int $resultId = null;

    public string $question = '';

    public string $text = '';

    public function getHeading(): string
    {
        return '';
    }

    public function mount(): void
    {
        if (! isset(AiAssistant::FEATURES[$this->feature])) {
            $this->feature = 'ringkasan_rapat';
        }
    }

    public function choose(string $feature): void
    {
        if (isset(AiAssistant::FEATURES[$feature])) {
            $this->feature = $feature;
            $this->resultId = null;
        }
    }

    public function run(): void
    {
        $input = AiAssistant::FEATURES[$this->feature][2];
        $rules = match ($input) {
            'meeting' => ['meetingId' => 'required'],
            'meeting_notes' => ['meetingId' => 'required', 'text' => 'required|min:20'],
            'agenda' => ['agendaId' => 'required'],
            'document' => ['documentId' => 'required'],
            'organ' => ['organId' => 'required'],
            'question' => ['question' => 'required|min:5'],
            'text' => ['text' => 'required|min:5'],
            default => [],
        };
        $this->validate($rules, ['required' => 'Wajib diisi.', 'min' => 'Terlalu pendek.']);

        $interaction = app(AiAssistant::class)->run(auth()->user(), $this->feature, array_filter([
            'meeting_id' => $this->meetingId, 'agenda_item_id' => $this->agendaId, 'document_id' => $this->documentId,
            'organ_id' => $this->organId, 'question' => $this->question, 'text' => $this->text,
        ], fn ($v) => $v !== null && $v !== ''));

        $this->resultId = $interaction->id;

        if (in_array($interaction->status, ['timeout', 'gagal'], true)) {
            Notification::make()->title($interaction->status === 'timeout' ? 'Layanan AI tidak merespons tepat waktu' : 'AI gagal')->body($interaction->error)->warning()->send();
        }
    }

    protected function getViewData(): array
    {
        $me = auth()->user();
        $input = AiAssistant::FEATURES[$this->feature][2];
        $meetings = Access::scopeMeetings(Meeting::query(), $me)->with('organ')->orderByDesc('scheduled_at')->limit(40)->get();
        $result = $this->resultId ? AiInteraction::where('user_id', $me->id)->find($this->resultId) : null;

        return [
            'me' => $me,
            'input' => $input,
            'features' => AiAssistant::FEATURES,
            'live' => app(AiAssistant::class)->isLive() && ! $me->isDemoGuest(),
            'meetings' => $meetings,
            'agendas' => in_array($input, ['agenda'], true) ? AgendaItem::with('meeting')->whereIn('meeting_id', $meetings->pluck('id'))->get()
                ->filter(fn ($i) => Access::canSeeAgendaItem($me, $i))->sortByDesc(fn ($i) => $i->meeting->scheduled_at)->take(60) : collect(),
            'documents' => in_array($input, ['document', 'question'], true) ? Document::whereIn('id', Access::readableDocumentIds($me))
                ->when($input === 'document', fn ($q) => $q->whereNotNull('previous_version_id'))->orderBy('title')->get() : collect(),
            'organs' => Organ::whereIn('id', Access::organIds($me))->orderBy('sort')->get(),
            'result' => $result,
            'history' => AiInteraction::where('user_id', $me->id)->latest()->limit(8)->get(),
        ];
    }
}
