<?php

namespace App\Filament\Widgets;

use App\Enums\AdminRole;
use App\Enums\ApplicationStatus;
use App\Enums\CheckpointStatus;
use App\Enums\ScholarshipStatus;
use App\Filament\Resources\Scholarships\ScholarshipResource;
use App\Models\Checkpoint;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    /**
     * These are operations metrics, so curriculum staff who cannot read
     * scholarship or mentee data do not see them at all.
     */
    public static function canView(): bool
    {
        return ScholarshipResource::canViewAny();
    }

    /**
     * Build the overview metrics from real database counts.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $mentees = User::query()
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', AdminRole::values()))
            ->count();

        $active_scholarships = Scholarship::query()
            ->where('status', ScholarshipStatus::Active)
            ->count();

        $modules = Module::query()->count();

        $awaiting_result = ScholarshipApplication::query()
            ->where('status', ApplicationStatus::Submitted)
            ->count();

        $completed_checkpoints = Checkpoint::query()
            ->where('status', CheckpointStatus::Completed)
            ->count();

        $total_checkpoints = Checkpoint::query()->count();

        $progress = $total_checkpoints === 0
            ? 0
            : (int) round($completed_checkpoints / $total_checkpoints * 100);

        return [
            Stat::make('Total Mentee', $mentees)
                ->description('Pengguna non-admin terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Beasiswa Aktif', $active_scholarships)
                ->description('Beasiswa berstatus aktif')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Total Modul', $modules)
                ->description('Seluruh kategori modul')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('gray'),

            Stat::make('Menunggu Hasil', $awaiting_result)
                ->description('Pendaftaran sudah submit, belum dilaporkan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($awaiting_result > 0 ? 'warning' : 'gray'),

            Stat::make('Progress Modul', $progress.'%')
                ->description($completed_checkpoints.' dari '.$total_checkpoints.' checkpoint selesai')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }
}
