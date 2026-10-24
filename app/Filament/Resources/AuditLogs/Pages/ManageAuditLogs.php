<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAuditLogs extends ManageRecords
{
    protected static string $resource = AuditLogResource::class;

    public function getSubheading(): ?string
    {
        return 'Catatan tidak dapat diubah atau dihapus dari aplikasi. Termasuk pembukaan dokumen, keluaran AI, dan percobaan akses yang ditolak.';
    }
}
