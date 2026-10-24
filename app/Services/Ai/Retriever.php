<?php

namespace App\Services\Ai;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\User;
use App\Support\Access;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pencarian dokumen berizin: hanya halaman dari dokumen yang boleh dibaca pengguna
 * dan diizinkan untuk AI yang pernah dikirim ke model.
 */
class Retriever
{
    public function allowedDocumentIds(User $user, ?array $restrictTo = null): Collection
    {
        $ids = Access::readableDocumentIds($user);
        $ids = Document::whereIn('id', $ids)->where('ai_allowed', true)->pluck('id');

        return $restrictTo === null ? $ids : $ids->intersect($restrictTo)->values();
    }

    /** @return Collection<int, DocumentPage> */
    public function search(User $user, string $query, ?array $restrictTo = null, ?int $limit = null): Collection
    {
        $limit ??= config('mufakat.ai.max_pages');
        $ids = $this->allowedDocumentIds($user, $restrictTo);

        if ($ids->isEmpty() || trim($query) === '') {
            return collect();
        }

        $terms = collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query)))
            ->filter(fn ($t) => mb_strlen($t) >= 3 && ! in_array($t, self::STOPWORDS, true))
            ->unique()->values();

        if ($terms->isEmpty()) {
            return collect();
        }

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $pages = DocumentPage::query()
                ->with('document')
                ->whereIn('document_id', $ids)
                ->selectRaw('document_pages.*, MATCH(heading, content) AGAINST (? IN NATURAL LANGUAGE MODE) as score', [$terms->implode(' ')])
                ->whereRaw('MATCH(heading, content) AGAINST (? IN NATURAL LANGUAGE MODE) > 0', [$terms->implode(' ')])
                ->orderByDesc('score')
                ->limit($limit)
                ->get();

            if ($pages->isNotEmpty()) {
                return $pages;
            }
        }

        // Cadangan: skor jumlah kemunculan istilah (juga dipakai pada SQLite saat pengujian).
        return DocumentPage::query()->with('document')->whereIn('document_id', $ids)
            ->where(function ($q) use ($terms) {
                foreach ($terms as $t) {
                    $q->orWhere('content', 'like', "%{$t}%")->orWhere('heading', 'like', "%{$t}%");
                }
            })
            ->get()
            ->map(function ($p) use ($terms) {
                $text = mb_strtolower($p->heading.' '.$p->content);
                $p->score = $terms->sum(fn ($t) => substr_count($text, $t));
                $p->coverage = $terms->filter(fn ($t) => str_contains($text, $t))->count() / $terms->count();

                return $p;
            })
            // Halaman yang hanya memuat satu kata umum tidak dianggap bukti.
            ->filter(fn ($p) => $p->coverage >= 0.5)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /** Seluruh halaman dokumen tertentu (mis. bahan satu rapat), tetap disaring hak akses. */
    public function pagesOf(User $user, array $documentIds, ?int $limit = null): Collection
    {
        $ids = $this->allowedDocumentIds($user, $documentIds);

        return DocumentPage::with('document')->whereIn('document_id', $ids)->orderBy('document_id')->orderBy('page_no')
            ->limit($limit ?? config('mufakat.ai.max_pages') * 2)->get();
    }

    /** Teks konteks bertanda rujukan untuk dikirim ke model. */
    public function toContext(Collection $pages): string
    {
        $max = config('mufakat.ai.max_chars_per_page');

        return $pages->map(fn (DocumentPage $p) => sprintf(
            "[SUMBER document_id=%d page=%d] %s (versi %d, %s)\n%s",
            $p->document_id, $p->page_no, $p->document->title, $p->document->version_no, $p->document->data_origin->getLabel(),
            mb_substr(($p->heading ? $p->heading."\n" : '').$p->content, 0, $max),
        ))->implode("\n\n---\n\n");
    }

    public const STOPWORDS = ['yang', 'dan', 'atau', 'untuk', 'dari', 'dengan', 'pada', 'dalam', 'adalah', 'ini', 'itu', 'apa', 'apakah', 'berapa', 'bagaimana', 'mengapa', 'kapan', 'siapa', 'tahun', 'sudah', 'belum', 'akan', 'ada', 'tidak', 'oleh', 'sebagai', 'juga', 'the', 'and', 'jelaskan', 'tolong', 'mohon', 'jumlah', 'total', 'nilai', 'data', 'berikan', 'sebutkan'];
}
