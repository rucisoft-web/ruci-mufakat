<?php

namespace Tests\Feature;

use App\Models\Decision;
use App\Models\Document;
use App\Models\InformationRequest;
use App\Models\Meeting;
use App\Models\Task;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanelPagesTest extends TestCase
{
    public static function accounts(): array
    {
        return [['komut'], ['kom.independen'], ['komisaris.b'], ['dirkom'], ['dirut'], ['sekper'], ['sekdekom'], ['tamu'], ['admin'], ['auditor'], ['konsultan'], ['vp.akuntansi']];
    }

    #[DataProvider('accounts')]
    public function test_every_page_renders_for_role(string $account): void
    {
        $user = $this->demo($account);
        $this->actingAs($user);

        $urls = ['/app/beranda-saya', '/app/dasbor-pimpinan', '/app/panduan-demo', '/app/manfaat', '/app/asisten-ai', '/app/ruang-baca',
            '/app/kepatuhan-rapat', '/app/kinerja-keuangan', '/app/meetings', '/app/decisions', '/app/tasks', '/app/circular-resolutions',
            '/app/information-requests', '/app/documents', '/app/companies', '/app/organs', '/app/memberships', '/app/historical-officials',
            '/app/policies', '/app/authority-rules', '/app/meeting-rules', '/app/work-plan-items', '/app/risks', '/app/compliance-items',
            '/app/evaluations', '/app/trainings', '/app/declarations', '/app/users', '/app/ai-interactions', '/app/audit-logs'];

        $meeting = Meeting::where('title', 'like', '%gabungan%')->orderByDesc('scheduled_at')->first();
        $urls[] = '/app/meetings/'.$meeting->id;
        $urls[] = '/app/decisions/'.Decision::first()->id;
        $urls[] = '/app/tasks/'.Task::where('code', 'TL-2026-002')->first()->id;
        $urls[] = '/app/information-requests/'.InformationRequest::where('code', 'PI-2026-011')->first()->id;
        $urls[] = '/app/ruang-baca?dokumen='.Document::where('kind', 'laporan_tahunan')->first()->id.'&halaman=19';

        foreach ($urls as $url) {
            $status = $this->get($url)->getStatusCode();
            if (getenv('MATRIX')) {
                fwrite(STDERR, "{$account}\t{$status}\t{$url}\n");
            }
            $this->assertContains($status, [200, 403], "{$account} {$url} → {$status}");
        }
    }

    public function test_public_pages(): void
    {
        $this->get('/untuk-pimpinan')->assertOk()->assertSee('MUFAKAT');
        $this->get('/app/login')->assertOk()->assertSee('Masuk sebagai tamu');
        $this->get('/demo/tamu')->assertRedirect();
        $this->assertAuthenticatedAs($this->demo('tamu'));
    }
}
