<?php

namespace App\Filament\Widgets;

use App\Contracts\ModuleReadinessServiceInterface;
use App\Enums\ContentStatus;
use App\Enums\ModuleCategory;
use App\Filament\Resources\Modules\ModuleResource;
use App\Models\Module;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Curriculum health at a glance: how much content exists, how much is still a
 * draft, and how many modules are not ready to be published yet.
 *
 * Every number comes from the modules table, and "belum siap terbit" uses the
 * same readiness rules the publish action enforces.
 */
class CurriculumOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return ModuleResource::canViewAny();
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $modules = Module::query()
            ->with(['practiceForms', 'quickQuestions'])
            ->get();

        $published = $modules->where('content_status', ContentStatus::Published)->count();
        $drafts = $modules->where('content_status', ContentStatus::Draft)->count();

        $notReady = $modules
            ->filter(fn (Module $module): bool => ! app(ModuleReadinessServiceInterface::class)->isReady($module))
            ->count();

        $templates = $modules->where('category', ModuleCategory::Template)->count();
        $library = $modules->where('category', ModuleCategory::Perpustakaan)->count();

        return [
            Stat::make('Modul terbit', $published)
                ->description('Sudah tampil pada jalur belajar mentee')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Modul draf', $drafts)
                ->description($drafts === 0 ? 'Tidak ada konten tertunda' : 'Belum terlihat oleh mentee')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color($drafts === 0 ? 'gray' : 'warning'),

            Stat::make('Belum siap terbit', $notReady)
                ->description($notReady === 0 ? 'Semua modul sudah lengkap' : 'Masih ada syarat kelengkapan yang kurang')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($notReady === 0 ? 'gray' : 'danger'),

            Stat::make('Modul Template', $templates)
                ->description('Terhubung ke beasiswa tertentu')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Modul Perpustakaan', $library)
                ->description('Pelatihan tambahan lintas beasiswa')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('gray'),
        ];
    }
}
