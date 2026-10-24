<?php

namespace App\Services\Ai;

use App\Enums\InfoRequestStatus;
use App\Enums\TaskStatus;
use App\Models\AgendaItem;
use App\Models\AiInteraction;
use App\Models\AiScenario;
use App\Models\Company;
use App\Models\Decision;
use App\Models\Document;
use App\Models\InformationRequest;
use App\Models\Meeting;
use App\Models\Organ;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkPlanItem;
use App\Services\FinancialAnalysis;
use App\Support\Access;
use App\Support\Audit;
use App\Support\Fmt;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Asisten AI Ruci MUFAKAT.
 *
 * Prinsip:
 * - Hanya potongan dokumen yang boleh dibaca pengguna (dan diizinkan untuk AI) yang dikirim ke model.
 * - Setiap pernyataan faktual wajib menunjuk dokumen dan halaman; rujukan yang tidak ada dalam konteks dibuang.
 * - Fakta, hasil hitungan aplikasi, dan analisis dibedakan; angka rasio dihitung aplikasi, bukan AI.
 * - Informasi yang belum tersedia disebutkan; seluruh keluaran berstatus DRAF.
 * - AI tidak menentukan kuorum, memberi suara, menandatangani, atau mengesahkan keputusan.
 */
class AiAssistant
{
    public const FEATURES = [
        'ringkasan_rapat' => ['Ringkasan sebelum rapat', 'Ringkasan bahan, perubahan penting, isu, dan pertanyaan untuk satu rapat.', 'meeting', 'heroicon-o-document-text'],
        'tanya_dokumen' => ['Tanya jawab berbukti', 'Jawaban dengan rujukan dokumen, versi, dan halaman.', 'question', 'heroicon-o-chat-bubble-left-right'],
        'pertanyaan_kritis' => ['Pertanyaan kritis Komisaris', 'Pertanyaan tentang asumsi, risiko, alternatif, dan data yang belum ada.', 'agenda', 'heroicon-o-light-bulb'],
        'draft_agenda' => ['Draft agenda', 'Usulan agenda dari isu terbuka, tugas belum selesai, dan rencana kerja.', 'organ', 'heroicon-o-list-bullet'],
        'draft_risalah' => ['Draft risalah', 'Mengolah catatan/transkrip menjadi draf pembahasan dan keputusan.', 'meeting_notes', 'heroicon-o-pencil-square'],
        'ekstraksi_tugas' => ['Ekstraksi calon tugas', 'Menemukan penugasan, PIC, dan tenggat dari teks pembahasan.', 'text', 'heroicon-o-clipboard-document-list'],
        'memori_keputusan' => ['Memori keputusan', 'Menelusuri keputusan terdahulu dan tindak lanjutnya.', 'question', 'heroicon-o-archive-box'],
        'bandingkan_versi' => ['Perbandingan dokumen', 'Menjelaskan perubahan angka, narasi, atau klausul antarversi.', 'document', 'heroicon-o-arrows-right-left'],
        'cek_kelengkapan' => ['Pemeriksaan kelengkapan usulan', 'Menandai alternatif, bukti, risiko, atau dasar kewenangan yang belum lengkap.', 'agenda', 'heroicon-o-check-badge'],
        'penjelasan_kinerja' => ['Penjelasan kinerja', 'Menjelaskan tren dan rasio yang dihitung aplikasi.', 'company', 'heroicon-o-chart-bar'],
        'terjemahan' => ['Penerjemahan', 'Draf Indonesia–Inggris dengan glosarium perusahaan.', 'text', 'heroicon-o-language'],
        'laporan_pengawasan' => ['Draft laporan pengawasan', 'Rangkuman kegiatan, rekomendasi, tanggapan, dan tindak lanjut.', 'organ', 'heroicon-o-document-chart-bar'],
    ];

    public const GLOSSARY = [
        'Dewan Komisaris' => 'Board of Commissioners', 'Direksi' => 'Board of Directors', 'Rapat Gabungan' => 'Joint Meeting',
        'Komite Audit' => 'Audit Committee', 'Risalah' => 'Minutes', 'Keputusan Sirkuler' => 'Circular Resolution',
        'Rencana Kerja dan Anggaran Perusahaan (RKAP)' => 'Company Work Plan and Budget (RKAP)', 'Tindak lanjut' => 'Follow-up action',
        'Penjualan neto' => 'Net sales', 'Rugi tahun berjalan' => 'Loss for the year', 'Liabilitas jangka pendek' => 'Current liabilities',
        'Restrukturisasi' => 'Restructuring', 'Entitas anak' => 'Subsidiary',
    ];

    public function __construct(protected OpenAiClient $client, protected Retriever $retriever, protected FinancialAnalysis $finance) {}

    public function isLive(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @param  array{meeting_id?: int, agenda_item_id?: int, document_id?: int, organ_id?: int, company_id?: int, question?: string, text?: string}  $params
     */
    public function run(User $user, string $feature, array $params): AiInteraction
    {
        abort_unless(isset(self::FEATURES[$feature]), 404);
        $started = microtime(true);

        try {
            $ctx = $this->build($user, $feature, $params);
        } catch (RuntimeException $e) {
            return $this->save($user, $feature, $params, ['prompt' => $params['question'] ?? $params['text'] ?? self::FEATURES[$feature][0], 'refs' => collect()], 'gagal', null, $e->getMessage());
        }

        // Bukti tidak cukup: jangan memanggil model, nyatakan apa yang belum tersedia.
        if ($ctx['requires_evidence'] && $ctx['refs']->isEmpty() && empty($ctx['facts'])) {
            return $this->save($user, $feature, $params, $ctx, 'bukti_tidak_cukup', [
                'summary' => 'Bukti belum tersedia. Tidak ada dokumen yang boleh Anda baca yang memuat informasi untuk menjawab permintaan ini, sehingga AI tidak menyusun jawaban.',
                'statements' => [],
                'questions' => [],
                'items' => [],
                'missing_information' => array_values(array_filter([
                    'Dokumen sumber yang memuat: '.mb_strimwidth($ctx['prompt'], 0, 160, '…'),
                    'Bila dokumen ada tetapi berklasifikasi lebih tinggi, ajukan akses kepada sekretariat.',
                ])),
                'draft_text' => '',
            ] + $ctx['deterministic']);
        }

        // Tamu demo tidak memakai kuota API: selalu memakai referensi demo.
        if (! $this->client->isConfigured() || $user->isDemoGuest()) {
            $scenario = config('mufakat.ai.demo_fallback') ? $this->scenarioFor($feature, $ctx) : null;

            if ($scenario) {
                return $this->save($user, $feature, $params, $ctx, $scenario->status, $scenario->output + $ctx['deterministic'], $scenario->status === 'timeout' ? 'Simulasi: layanan AI tidak merespons dalam batas waktu. Bahan dan fitur rapat lain tetap dapat dipakai.' : null, 'referensi-demo:'.$scenario->code);
            }

            return $this->save($user, $feature, $params, $ctx, 'ai_nonaktif', $this->offlineOutput($feature, $ctx), 'OPENAI_API_KEY belum diisi. Bagian yang dihitung aplikasi tetap ditampilkan.');
        }

        try {
            $result = $this->client->structured($this->instructions($feature), $this->input($ctx), self::schema());
        } catch (AiTimeoutException $e) {
            return $this->save($user, $feature, $params, $ctx, 'timeout', $this->offlineOutput($feature, $ctx), $e->getMessage().' Coba lagi beberapa saat; pekerjaan rapat lain tidak terganggu.');
        } catch (Throwable $e) {
            report($e);

            return $this->save($user, $feature, $params, $ctx, 'gagal', $this->offlineOutput($feature, $ctx), $e->getMessage());
        }

        $output = $this->validate($result['data'], $ctx['refs']) + $ctx['deterministic'];
        $interaction = $this->save($user, $feature, $params, $ctx, 'berhasil', $output, null, $result['model'], $result['usage'], $started);

        return $interaction;
    }

    /** Konteks per fitur: sumber berizin + data deterministik aplikasi. */
    protected function build(User $user, string $feature, array $p): array
    {
        $ctx = ['prompt' => '', 'refs' => collect(), 'facts' => [], 'deterministic' => [], 'requires_evidence' => true, 'subject' => null, 'extra' => ''];

        switch ($feature) {
            case 'ringkasan_rapat':
            case 'draft_risalah':
                $meeting = $this->meeting($user, $p['meeting_id'] ?? null);
                $ctx['subject'] = $meeting;
                $items = $meeting->agendaItems()->with('documents')->orderBy('seq')->get()->filter(fn ($i) => Access::canSeeAgendaItem($user, $i));
                $ctx['refs'] = $this->retriever->pagesOf($user, $items->flatMap->documents->pluck('id')->all());
                $book = $meeting->boardBooks()->latest('version_no')->first();
                $ctx['extra'] = "RAPAT: {$meeting->title} ({$meeting->organ->name}), ".$meeting->scheduled_at->translatedFormat('d F Y H:i')."\nAGENDA:\n"
                    .$items->map(fn ($i) => "{$i->seq}. {$i->title} [{$i->purpose->getLabel()}] — tujuan: {$i->objective}")->implode("\n")
                    .($book ? "\nBOARD BOOK: versi {$book->version_no}; perubahan: ".str_replace("\n", '; ', (string) $book->change_log) : "\nBOARD BOOK: belum terbit");
                if ($feature === 'draft_risalah') {
                    $ctx['requires_evidence'] = false;
                    $ctx['extra'] .= "\n\nCATATAN/TRANSKRIP RAPAT:\n".trim((string) ($p['text'] ?? ''))
                        ."\n\nKEHADIRAN AKTUAL: ".$meeting->participants()->with('user')->where('attended', true)->get()->pluck('user.name')->implode(', ')
                        ."\nKUORUM (dihitung aplikasi): ".app(\App\Services\QuorumService::class)->summary($meeting);
                }
                $ctx['prompt'] = self::FEATURES[$feature][0].': '.$meeting->title;
                break;

            case 'pertanyaan_kritis':
            case 'cek_kelengkapan':
                $item = AgendaItem::with('meeting.organ', 'documents')->findOrFail($p['agenda_item_id'] ?? 0);
                if (! Access::canSeeAgendaItem($user, $item)) {
                    Access::deny($user, 'AI atas agenda '.$item->title, $item);
                    throw new RuntimeException('Anda tidak berhak membuka agenda ini.');
                }
                $ctx['subject'] = $item;
                $ctx['refs'] = $this->retriever->pagesOf($user, $item->documents->pluck('id')->all());
                $ctx['extra'] = "AGENDA: {$item->title}\nSifat: {$item->purpose->getLabel()}\nTujuan: {$item->objective}\nLatar belakang: {$item->background}\nAlternatif: {$item->alternatives}\nDampak keuangan: {$item->financial_impact}\nRisiko: {$item->risks}\nRekomendasi: {$item->recommendation}\nDasar kewenangan: {$item->authority_basis}";
                $ctx['deterministic'] = ['completeness' => $this->completeness($item)];
                $ctx['requires_evidence'] = false;
                $ctx['prompt'] = self::FEATURES[$feature][0].': '.$item->title;
                break;

            case 'tanya_dokumen':
                $question = trim((string) ($p['question'] ?? ''));
                $ctx['prompt'] = $question;
                $ctx['refs'] = $this->retriever->search($user, $question, isset($p['document_id']) ? [(int) $p['document_id']] : null);
                break;

            case 'memori_keputusan':
                $question = trim((string) ($p['question'] ?? ''));
                $ctx['prompt'] = $question;
                $organIds = Access::organIds($user);
                $terms = collect(preg_split('/\s+/', mb_strtolower($question)))->filter(fn ($t) => mb_strlen($t) >= 4 && ! in_array($t, Retriever::STOPWORDS, true));
                $decisions = Decision::with('organ', 'tasks.pic', 'meeting')->whereIn('organ_id', $organIds)
                    ->where(fn ($q) => $terms->each(fn ($t) => $q->orWhere('title', 'like', "%{$t}%")->orWhere('text', 'like', "%{$t}%")))
                    ->orderByDesc('decided_on')->limit(10)->get();
                $ctx['facts'] = $decisions->map(fn ($d) => "[KEPUTUSAN {$d->number}] {$d->decided_on->format('d/m/Y')} {$d->organ->short_name} — {$d->kind->getLabel()} — {$d->result->getLabel()}: {$d->title}. {$d->text}"
                    .($d->tasks->isNotEmpty() ? ' | Tindak lanjut: '.$d->tasks->map(fn ($t) => "{$t->code} {$t->title} (PIC {$t->pic->name}, {$t->status->getLabel()}, tenggat {$t->due_date->format('d/m/Y')})")->implode('; ') : ''))->all();
                $ctx['refs'] = $this->retriever->search($user, $question, null, 4);
                break;

            case 'bandingkan_versi':
                $doc = Document::findOrFail($p['document_id'] ?? 0);
                if (! Access::canReadDocument($user, $doc) || ! $doc->previous_version_id || ! Access::canReadDocument($user, $doc->previous_version_id)) {
                    throw new RuntimeException('Dokumen tidak mempunyai versi sebelumnya yang boleh Anda baca.');
                }
                $ctx['subject'] = $doc;
                $ctx['refs'] = $this->retriever->pagesOf($user, [$doc->id, $doc->previous_version_id], 40);
                $ctx['extra'] = "Bandingkan versi {$doc->previousVersion->version_no} (document_id={$doc->previous_version_id}) dengan versi {$doc->version_no} (document_id={$doc->id}) dari \"{$doc->title}\". Ringkasan perubahan menurut pengunggah: {$doc->change_summary}";
                $ctx['prompt'] = "Perbandingan versi: {$doc->title}";
                break;

            case 'penjelasan_kinerja':
                $company = Company::findOrFail($p['company_id'] ?? Company::whereNull('parent_id')->value('id'));
                $ctx['subject'] = $company;
                $ctx['facts'] = $this->financialFacts($company);
                $ctx['refs'] = $this->retriever->search($user, 'ikhtisar keuangan rasio penjualan rugi likuiditas restrukturisasi', null, 6);
                $ctx['deterministic'] = ['ratios' => $this->finance->ratios($company), 'quality_flags' => $this->finance->qualityFlags($company)];
                $ctx['prompt'] = "Penjelasan kinerja {$company->short_name} 2025 vs 2024";
                break;

            case 'draft_agenda':
            case 'laporan_pengawasan':
                $organ = Organ::with('company')->findOrFail($p['organ_id'] ?? 0);
                if (! Access::organIds($user)->contains($organ->id)) {
                    throw new RuntimeException('Anda tidak berhak atas organ ini.');
                }
                $ctx['subject'] = $organ;
                $ctx['requires_evidence'] = false;
                $ctx['facts'] = $this->openIssues($organ);
                $ctx['prompt'] = self::FEATURES[$feature][0].': '.$organ->name;
                $ctx['refs'] = $feature === 'laporan_pengawasan' ? $this->retriever->search($user, 'pengawasan rekomendasi komisaris tindak lanjut', null, 6) : collect();
                break;

            case 'ekstraksi_tugas':
            case 'terjemahan':
                $text = trim((string) ($p['text'] ?? ''));
                if ($text === '') {
                    throw new RuntimeException('Teks belum diisi.');
                }
                $ctx['requires_evidence'] = false;
                $ctx['prompt'] = mb_strimwidth($text, 0, 200, '…');
                $ctx['extra'] = ($feature === 'terjemahan' ? "GLOSARIUM PERUSAHAAN:\n".collect(self::GLOSSARY)->map(fn ($en, $id) => "{$id} = {$en}")->implode("\n")."\n\nTEKS SUMBER:\n" : "TEKS PEMBAHASAN:\n").$text;
                $ctx['facts'] = $feature === 'ekstraksi_tugas' ? ['Pengguna yang dapat menjadi PIC: '.User::where('is_active', true)->whereIn('role', ['direksi', 'pic', 'sekper'])->pluck('name')->implode(', ')] : [];
                break;
        }

        return $ctx;
    }

    protected function meeting(User $user, ?int $id): Meeting
    {
        $meeting = Meeting::with('organ')->findOrFail($id ?? 0);

        if (! Access::canSeeMeeting($user, $meeting)) {
            Access::deny($user, 'AI atas rapat '.$meeting->title, $meeting);
            throw new RuntimeException('Anda tidak berhak membuka rapat ini.');
        }

        return $meeting;
    }

    /** Pemeriksaan kelengkapan usulan berbasis aturan (tanpa AI). */
    public function completeness(AgendaItem $item): array
    {
        $needsDecision = in_array($item->purpose->value, ['keputusan', 'persetujuan'], true);
        $checks = [
            ['Tujuan pembahasan', filled($item->objective)],
            ['Latar belakang', filled($item->background)],
            ['Alternatif yang dipertimbangkan', ! $needsDecision || filled($item->alternatives)],
            ['Dampak keuangan', ! $needsDecision || filled($item->financial_impact)],
            ['Risiko', ! $needsDecision || filled($item->risks)],
            ['Rekomendasi', ! $needsDecision || filled($item->recommendation)],
            ['Dasar kewenangan', ! $needsDecision || filled($item->authority_basis)],
            ['Bahan/bukti terlampir', $item->documents()->exists()],
            ['Bahan diserahkan tepat waktu', $item->materials_submitted_at !== null && $item->meeting->materials_due_at !== null && $item->materials_submitted_at->lessThanOrEqualTo($item->meeting->materials_due_at)],
        ];

        return collect($checks)->map(fn ($c) => ['label' => $c[0], 'ok' => (bool) $c[1]])->all();
    }

    protected function financialFacts(Company $company): array
    {
        $lines = [];
        foreach ($this->finance->growth($company) as $g) {
            $lines[] = sprintf('[HITUNG APLIKASI] %s: 2024 = %s; 2025 = %s Rp juta; perubahan %s%s (sumber hlm. %s)',
                $g['label'], Fmt::num($g['from']), Fmt::num($g['to']), Fmt::pct($g['pct']), $g['is_loss'] ? ' atas besaran rugi' : '', $g['page']);
        }
        foreach ($this->finance->ratios($company) as $r) {
            $lines[] = sprintf('[HITUNG APLIKASI] %s (%s): 2024 = %s; 2025 = %s %s', $r['label'], $r['formula'],
                Fmt::num($r['values']['2024'], 4), Fmt::num($r['values']['2025'], 4), $r['unit']);
        }
        foreach ($this->finance->qualityFlags($company) as $q) {
            $lines[] = "[KUALITAS DATA] {$q['metric']}: sumber {$q['source']}; hitung aplikasi {$q['computed']}. {$q['message']}";
        }

        return $lines;
    }

    protected function openIssues(Organ $organ): array
    {
        $companyId = $organ->company_id;
        $lines = [];

        foreach (Task::with('pic')->where('company_id', $companyId)->where('status', '!=', TaskStatus::Selesai->value)->orderBy('due_date')->limit(12)->get() as $t) {
            $lines[] = "[TUGAS {$t->code}] {$t->title} — PIC {$t->pic->name}, tenggat {$t->due_date->format('d/m/Y')}, status {$t->status->getLabel()}".($t->due_date->isPast() ? ' (TERLAMBAT)' : '');
        }
        foreach (InformationRequest::with('pic')->where('organ_id', $organ->id)->where('status', '!=', InfoRequestStatus::Diterima->value)->limit(10)->get() as $r) {
            $lines[] = "[PERMINTAAN INFO {$r->code}] {$r->subject} — {$r->status->getLabel()}, SLA {$r->sla_due_at->format('d/m/Y')}";
        }
        foreach (WorkPlanItem::where('organ_id', $organ->id)->where('year', now()->year)->where('month', '>=', now()->month)->where('status', 'direncanakan')->limit(8)->get() as $w) {
            $lines[] = "[RENCANA KERJA bulan {$w->month}] {$w->title} ({$w->kind})";
        }
        foreach (Decision::where('organ_id', $organ->id)->latest('decided_on')->limit(6)->get() as $d) {
            $lines[] = "[KEPUTUSAN {$d->number}] {$d->title} — {$d->result->getLabel()} {$d->decided_on->format('d/m/Y')}";
        }

        return $lines;
    }

    protected function instructions(string $feature): string
    {
        $task = match ($feature) {
            'ringkasan_rapat' => 'Susun ringkasan sebelum rapat: pokok tiap agenda, perubahan penting pada board book, isu yang perlu perhatian, dan pertanyaan yang layak diajukan. Isi "questions" dengan pertanyaan untuk rapat.',
            'tanya_dokumen' => 'Jawab pertanyaan pengguna hanya dari SUMBER. Bila sumber tidak memuat jawabannya, katakan demikian dan isi missing_information.',
            'pertanyaan_kritis' => 'Sebagai pendamping Dewan Komisaris, usulkan 6–10 pertanyaan kritis tentang asumsi, risiko, alternatif, kepatuhan, dan data yang belum tersedia. Isi "questions". Pertanyaan bersifat nasihat pengawasan, bukan keputusan.',
            'draft_agenda' => 'Usulkan draf agenda rapat berikutnya dari isu terbuka. Isi "items" (title = judul agenda, detail = alasan dan sifat: informasi/diskusi/keputusan, owner = penyaji yang disarankan, due = durasi menit).',
            'draft_risalah' => 'Ubah catatan/transkrip menjadi draf risalah formal di "draft_text" (Markdown). Pisahkan keputusan Direksi, persetujuan/nasihat Dewan Komisaris. Jangan mengarang suara, keputusan, atau kehadiran yang tidak ada di catatan. Isi "items" dengan calon tindak lanjut.',
            'ekstraksi_tugas' => 'Temukan calon tugas tindak lanjut. Isi "items": title, detail, owner (nama PIC bila disebut, kosong bila tidak), due (tanggal YYYY-MM-DD bila disebut, kosong bila tidak). Jangan menebak PIC atau tenggat.',
            'memori_keputusan' => 'Telusuri keputusan terdahulu dari DATA APLIKASI dan SUMBER. Sebutkan nomor keputusan, tanggal, organ, dan status tindak lanjutnya.',
            'bandingkan_versi' => 'Jelaskan perubahan angka, narasi, atau klausul antara dua versi. Setiap perubahan dirujuk ke halaman di kedua versi bila ada.',
            'cek_kelengkapan' => 'Periksa kelengkapan kertas usulan: alternatif, bukti, analisis risiko, dampak keuangan, dasar kewenangan. Isi "missing_information" dengan kekurangan dan "questions" dengan permintaan perbaikan kepada pengusul.',
            'penjelasan_kinerja' => 'Jelaskan hasil hitung aplikasi (tren dan rasio) dengan bahasa untuk komisaris dan direksi. Jangan menghitung rasio baru. Sebut isu kualitas data bila ada.',
            'terjemahan' => 'Terjemahkan TEKS SUMBER ke bahasa Inggris formal korporat di "draft_text", wajib memakai GLOSARIUM PERUSAHAAN. Daftar istilah yang tidak ada di glosarium di "missing_information".',
            'laporan_pengawasan' => 'Susun draf laporan pengawasan Dewan Komisaris di "draft_text" (Markdown): kegiatan, rekomendasi, tanggapan manajemen, dan status tindak lanjut.',
            default => '',
        };

        return <<<TXT
        Anda adalah asisten Ruci MUFAKAT untuk Direksi, Dewan Komisaris, komite, dan sekretariat perusahaan di Indonesia. Jawab dalam bahasa Indonesia baku, ringkas, dan netral.

        Aturan wajib:
        1. Gunakan hanya SUMBER, DATA APLIKASI, dan HITUNG APLIKASI yang diberikan. Jangan memakai pengetahuan luar untuk fakta perusahaan.
        2. Setiap pernyataan bertipe "fakta" wajib mencantumkan sources berisi document_id dan page persis seperti tanda [SUMBER ...]. Bila tidak ada sumbernya, jangan nyatakan sebagai fakta.
        3. Tipe "hitungan" hanya untuk angka yang ditandai [HITUNG APLIKASI]; jangan menghitung rasio atau persentase baru sendiri.
        4. Tipe "analisis" untuk penilaian/penafsiran Anda; tandai dengan jelas.
        5. Isi missing_information dengan informasi yang dibutuhkan tetapi belum tersedia.
        6. Anda tidak menentukan kuorum, tidak memberi suara, tidak menandatangani, dan tidak mengesahkan keputusan. Seluruh keluaran adalah DRAF untuk ditelaah manusia.
        7. Data berlabel Simulasi adalah data rekaan demo; sebutkan bila relevan.

        Tugas: {$task}
        Untuk kolom yang tidak relevan, isi string kosong atau array kosong.
        TXT;
    }

    protected function input(array $ctx): string
    {
        return trim(implode("\n\n", array_filter([
            'PERMINTAAN: '.$ctx['prompt'],
            $ctx['extra'] ?: null,
            $ctx['facts'] ? "DATA APLIKASI:\n".implode("\n", $ctx['facts']) : null,
            $ctx['refs']->isNotEmpty() ? "SUMBER:\n".$this->retriever->toContext($ctx['refs']) : 'SUMBER: (tidak ada)',
        ])));
    }

    public static function schema(): array
    {
        $str = ['type' => 'string'];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['summary', 'statements', 'questions', 'items', 'missing_information', 'draft_text'],
            'properties' => [
                'summary' => $str,
                'statements' => ['type' => 'array', 'items' => [
                    'type' => 'object', 'additionalProperties' => false, 'required' => ['text', 'type', 'sources'],
                    'properties' => [
                        'text' => $str,
                        'type' => ['type' => 'string', 'enum' => ['fakta', 'hitungan', 'analisis']],
                        'sources' => ['type' => 'array', 'items' => [
                            'type' => 'object', 'additionalProperties' => false, 'required' => ['document_id', 'page'],
                            'properties' => ['document_id' => ['type' => 'integer'], 'page' => ['type' => 'integer']],
                        ]],
                    ],
                ]],
                'questions' => ['type' => 'array', 'items' => $str],
                'items' => ['type' => 'array', 'items' => [
                    'type' => 'object', 'additionalProperties' => false, 'required' => ['title', 'detail', 'owner', 'due'],
                    'properties' => ['title' => $str, 'detail' => $str, 'owner' => $str, 'due' => $str],
                ]],
                'missing_information' => ['type' => 'array', 'items' => $str],
                'draft_text' => $str,
            ],
        ];
    }

    /** Buang rujukan yang tidak ada dalam konteks; fakta tanpa rujukan sah diturunkan menjadi "belum terverifikasi". */
    public function validate(array $data, Collection $refs): array
    {
        $allowed = $refs->map(fn ($p) => $p->document_id.':'.$p->page_no)->flip();
        $dropped = 0;

        $data['statements'] = collect($data['statements'] ?? [])->map(function ($s) use ($allowed, &$dropped) {
            $valid = collect($s['sources'] ?? [])->filter(fn ($src) => $allowed->has(($src['document_id'] ?? 0).':'.($src['page'] ?? 0)))->values()->all();
            $dropped += count($s['sources'] ?? []) - count($valid);
            $s['sources'] = $valid;
            $s['unverified'] = $s['type'] === 'fakta' && ! $valid;

            return $s;
        })->all();

        $data['warnings'] = array_values(array_filter([
            $dropped ? "{$dropped} rujukan yang tidak ada dalam sumber berizin dibuang oleh aplikasi." : null,
            collect($data['statements'])->contains('unverified', true) ? 'Ada pernyataan fakta tanpa rujukan sah; diperlakukan sebagai belum terverifikasi.' : null,
        ]));

        return $data;
    }

    protected function offlineOutput(string $feature, array $ctx): array
    {
        $base = ['summary' => '', 'statements' => [], 'questions' => [], 'items' => [], 'missing_information' => [], 'draft_text' => ''] + $ctx['deterministic'];

        if ($feature === 'cek_kelengkapan' && isset($ctx['deterministic']['completeness'])) {
            $missing = collect($ctx['deterministic']['completeness'])->where('ok', false)->pluck('label');
            $base['summary'] = $missing->isEmpty() ? 'Pemeriksaan aturan: seluruh unsur kertas usulan terisi.' : 'Pemeriksaan aturan menemukan '.$missing->count().' unsur yang belum lengkap.';
            $base['missing_information'] = $missing->all();
        }

        if ($feature === 'penjelasan_kinerja') {
            $base['summary'] = 'Rasio di bawah ini dihitung aplikasi secara deterministik. Penjelasan naratif memerlukan layanan AI.';
            $base['statements'] = collect($ctx['facts'])->map(fn ($l) => ['text' => $l, 'type' => 'hitungan', 'sources' => []])->all();
        }

        if ($ctx['refs']->isNotEmpty()) {
            $base['statements'] = array_merge($base['statements'], $ctx['refs']->take(5)->map(fn ($p) => [
                'text' => 'Halaman relevan: '.$p->document->title.' hlm. '.$p->page_no.($p->heading ? ' — '.$p->heading : ''),
                'type' => 'fakta', 'sources' => [['document_id' => $p->document_id, 'page' => $p->page_no]],
            ])->all());
        }

        return $base;
    }

    protected function scenarioFor(string $feature, array $ctx): ?AiScenario
    {
        $subject = $ctx['subject'];
        $query = AiScenario::where('feature', $feature);

        if ($subject) {
            $match = (clone $query)->where('subject_type', class_basename($subject))->where('subject_id', $subject->getKey())->first();
            if ($match) {
                return $match;
            }
        }

        // Pertanyaan bebas: cocokkan kata kunci skenario.
        if (in_array($feature, ['tanya_dokumen', 'memori_keputusan', 'ekstraksi_tugas', 'terjemahan'], true)) {
            $prompt = mb_strtolower($ctx['prompt']);

            return $query->get()->sortByDesc(fn ($s) => collect(preg_split('/\W+/u', mb_strtolower($s->prompt)))->filter(fn ($w) => mb_strlen($w) > 4 && str_contains($prompt, $w))->count())
                ->first(fn ($s) => collect(preg_split('/\W+/u', mb_strtolower($s->prompt)))->filter(fn ($w) => mb_strlen($w) > 4 && str_contains($prompt, $w))->count() >= 2);
        }

        return $subject ? null : $query->first();
    }

    protected function save(User $user, string $feature, array $params, array $ctx, string $status, ?array $output, ?string $error = null, ?string $model = null, array $usage = [], ?float $started = null): AiInteraction
    {
        $subject = $ctx['subject'] ?? null;

        $interaction = AiInteraction::create([
            'user_id' => $user->id,
            'feature' => $feature,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'prompt' => $ctx['prompt'] ?: self::FEATURES[$feature][0],
            'context_refs' => $ctx['refs']->map(fn ($p) => ['document_id' => $p->document_id, 'page' => $p->page_no, 'title' => $p->document->title, 'version' => $p->document->version_no])->values()->all(),
            'output' => $output,
            'status' => $status,
            'model' => $model,
            'input_tokens' => $usage['input_tokens'] ?? null,
            'output_tokens' => $usage['output_tokens'] ?? null,
            'latency_ms' => $started ? (int) ((microtime(true) - $started) * 1000) : null,
            'error' => $error,
            'is_draft' => true,
        ]);

        Audit::log('ai_'.$status, self::FEATURES[$feature][0].': '.mb_strimwidth($interaction->prompt, 0, 120, '…'), $interaction, ['pages_sent' => count($interaction->context_refs ?? [])]);

        return $interaction;
    }
}
