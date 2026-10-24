<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    public function getSubheading(): ?string
    {
        return 'Kemajuan 100% belum berarti selesai: tugas ditutup setelah verifikator menerima bukti.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tugas baru')];
    }

    public function getTabs(): array
    {
        return [
            'terbuka' => Tab::make('Terbuka')->modifyQueryUsing(fn (Builder $query) => $query->where('status', '!=', TaskStatus::Selesai->value)),
            'verifikasi' => Tab::make('Menunggu verifikasi')->modifyQueryUsing(fn (Builder $query) => $query->where('status', TaskStatus::Diajukan->value)),
            'selesai' => Tab::make('Selesai')->modifyQueryUsing(fn (Builder $query) => $query->where('status', TaskStatus::Selesai->value)),
            'semua' => Tab::make('Semua'),
        ];
    }
}
