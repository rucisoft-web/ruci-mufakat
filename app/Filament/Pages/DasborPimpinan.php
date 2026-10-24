<?php

namespace App\Filament\Pages;

use App\Enums\MeetingStatus;
use App\Models\Company;
use App\Models\Meeting;
use App\Models\Risk;
use App\Services\BoardBookService;
use App\Services\FinancialAnalysis;
use App\Services\GovernanceMetrics;
use App\Services\QuorumService;
use App\Support\Access;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DasborPimpinan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Beranda';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Dasbor Pimpinan';

    protected static ?string $title = 'Dasbor Pimpinan';

    protected static ?string $slug = 'dasbor-pimpinan';

    /** Administrator teknis tidak otomatis membaca isi dewan. */
    public static function canAccess(): bool
    {
        return auth()->user()?->role?->value !== 'admin';
    }

    protected string $view = 'filament.pages.dasbor-pimpinan';

    public function getHeading(): string
    {
        return '';
    }

    protected function getViewData(): array
    {
        $me = auth()->user();
        $gov = app(GovernanceMetrics::class);
        $company = Company::whereNull('parent_id')->first();
        $fin = app(FinancialAnalysis::class);
        $growth = $company ? $fin->growth($company) : [];
        $ratios = $company ? collect($fin->ratios($company))->keyBy('code') : collect();

        $upcoming = Access::scopeMeetings(Meeting::query(), $me)->with('organ', 'boardBooks', 'participants')
            ->where('scheduled_at', '>=', now()->startOfDay())->whereNotIn('status', [MeetingStatus::Dibatalkan->value])
            ->orderBy('scheduled_at')->limit(6)->get();
        $materials = $gov->materials($me);
        $frequency = $gov->frequency($me);
        $tasks = $gov->tasks($me);
        $sla = $gov->infoSla($me);
        $attendance = $gov->attendance($me);
        $risks = Risk::with('owner')->orderByRaw('likelihood * impact desc')->limit(5)->get();

        $insights = collect();
        if ($t = $tasks['overdue']) {
            $insights->push(['bad', "{$t} tugas tindak lanjut melewati tenggat; ".$tasks['full_not_done'].' tugas sudah 100% tetapi buktinya belum diverifikasi.']);
        }
        if ($late = $materials->where('on_time', false)->count()) {
            $insights->push(['warn', "{$late} dari {$materials->count()} rapat tahun ini menerima bahan kurang dari H-".(int) ($materials->first()['rule'] ?? 5).' (aturan POJK 33/2014).']);
        }
        if ($fail = $frequency->sum('failed')) {
            $insights->push(['bad', "{$fail} rapat tidak mencapai kuorum dan tidak dihitung sebagai rapat terlaksana."]);
        }
        if ($behind = $frequency->where('on_track', false)->pluck('organ.short_name')->implode(', ')) {
            $insights->push(['warn', "Realisasi rapat di bawah laju minimum regulasi: {$behind}."]);
        }
        if ($sla['Terbuka, lewat SLA'] ?? 0) {
            $insights->push(['bad', $sla['Terbuka, lewat SLA'].' permintaan informasi Komisaris belum dijawab melewati SLA.']);
        }
        if (isset($growth['laba_bersih'])) {
            $insights->push(['good', 'Rugi tahun berjalan 2025 turun '.number_format(abs($growth['laba_bersih']['pct']), 2, ',', '.').'% dibanding 2024, sementara penjualan turun '.number_format(abs($growth['penjualan']['pct']), 2, ',', '.').'% (hitung aplikasi dari laporan hlm. 16).']);
        }
        if ($ratios->has('rasio_lancar')) {
            $insights->push(['warn', 'Rasio lancar 2025 = '.number_format($ratios['rasio_lancar']['values']['2025'], 2, ',', '.').' kali; laporan menulis "56,37 x" di hlm. 19, satuan perlu klarifikasi (PI-2026-011).']);
        }

        return [
            'me' => $me,
            'upcoming' => $upcoming,
            'quorum' => app(QuorumService::class),
            'books' => app(BoardBookService::class),
            'growth' => $growth,
            'ratios' => $ratios,
            'tasks' => $tasks,
            'sla' => $sla,
            'materials' => $materials,
            'frequency' => $frequency,
            'attendancePct' => $attendance->isEmpty() ? null : $attendance->sum('attended') / max(1, $attendance->sum('invited')) * 100,
            'risks' => $risks,
            'heat' => $gov->heatmap(),
            'insights' => $insights,
        ];
    }
}
