<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Dokumen berversi: setiap revisi menjadi dokumen baru yang menunjuk versi sebelumnya,
 * disertai sidik SHA-256 dan teks per halaman untuk ruang baca serta rujukan AI.
 */
class DocumentService
{
    public function hash(Document $document): string
    {
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            return hash_file('sha256', Storage::disk('local')->path($document->file_path));
        }

        return hash('sha256', $document->pages()->orderBy('page_no')->pluck('content')->implode("\f"));
    }

    public function refresh(Document $document): Document
    {
        $document->forceFill([
            'page_count' => $document->pages()->count(),
            'sha256' => $this->hash($document),
        ])->save();

        return $document;
    }

    /** Ekstrak teks per halaman dari PDF (pdftotext bila ada, cadangan pustaka PHP). */
    public function extractPdf(Document $document): int
    {
        $path = Storage::disk('local')->path($document->file_path);
        $pages = [];
        $pdftotext = config('mufakat.pdftotext');

        if ($pdftotext && is_executable($pdftotext) && function_exists('shell_exec')) {
            $text = (string) @shell_exec(escapeshellarg($pdftotext).' -layout '.escapeshellarg($path).' - 2>/dev/null');
            $pages = $text !== '' ? explode("\f", rtrim($text, "\f")) : [];
        }

        if (! $pages) {
            try {
                foreach ((new Parser)->parseFile($path)->getPages() as $page) {
                    $pages[] = $page->getText();
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $document->pages()->delete();

        foreach ($pages as $i => $text) {
            $document->pages()->create(['page_no' => $i + 1, 'content' => trim($text) !== '' ? trim($text) : '(halaman tanpa teks; perlu OCR)']);
        }

        $this->refresh($document);

        return count($pages);
    }

    /** Teks biasa dipecah menjadi halaman ±3.000 karakter. */
    public function storeText(Document $document, string $text): void
    {
        $document->pages()->delete();
        $chunks = preg_split('/\f|\n-{3,}\n/', $text) ?: [$text];
        $n = 0;

        foreach ($chunks as $chunk) {
            foreach (str_split(trim($chunk), 3000) as $part) {
                if (trim($part) !== '') {
                    $document->pages()->create(['page_no' => ++$n, 'content' => $part]);
                }
            }
        }

        $this->refresh($document);
    }

    /** Revisi: salin metadata, naikkan versi, tautkan ke versi sebelumnya. */
    public function newVersion(Document $previous, array $attributes, ?User $user = null): Document
    {
        $document = $previous->replicate(['sha256', 'page_count', 'file_path', 'mime', 'size']);
        $document->fill($attributes + [
            'previous_version_id' => $previous->id,
            'series_key' => $previous->series_key ?? 'doc-'.$previous->id,
            'version_no' => $previous->version_no + 1,
            'uploaded_by' => $user?->id,
        ]);
        $document->save();

        if (! $previous->series_key) {
            $previous->forceFill(['series_key' => $document->series_key])->save();
        }

        Audit::log('dokumen_versi_baru', "Versi {$document->version_no} dokumen \"{$document->title}\"", $document);

        return $document;
    }
}
