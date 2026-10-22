<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Scholarships\ScholarshipResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The editorial dashboard of the curriculum team.
 *
 * It answers the questions an admin actually asks when they log in — what is
 * unfinished, what needs reviewing, and what to work on next — and offers a
 * direct route into those tasks instead of decorative numbers.
 */
class Dashboard extends BaseDashboard
{
    public function getSubheading(): string|Htmlable|null
    {
        return 'Ringkasan kurikulum, konten yang perlu diselesaikan, dan proses belajar mentee.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Actions::make($this->quickActions()),
                ...parent::content($schema)->getComponents(),
            ]);
    }

    /**
     * Actions that jump straight into the most common curriculum tasks.
     *
     * @return array<int, Action>
     */
    protected function quickActions(): array
    {
        return [
            Action::make('createModule')
                ->label('Buat modul baru')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => ModuleResource::getUrl('create'))
                ->visible(fn (): bool => ModuleResource::canCreate()),
            Action::make('draftModules')
                ->label('Modul draf')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url(fn (): string => ModuleResource::getUrl('index', [
                    // Filament reads table filters from the `filters` query key.
                    'filters' => [
                        'content_status' => ['value' => 'draft'],
                    ],
                ]))
                ->visible(fn (): bool => ModuleResource::canViewAny()),
            Action::make('curriculum')
                ->label('Semua modul')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->url(fn (): string => ModuleResource::getUrl('index'))
                ->visible(fn (): bool => ModuleResource::canViewAny()),
            Action::make('createScholarship')
                ->label('Tambah beasiswa')
                ->icon('heroicon-o-academic-cap')
                ->color('gray')
                ->url(fn (): string => ScholarshipResource::getUrl('create'))
                ->visible(fn (): bool => ScholarshipResource::canCreate()),
        ];
    }
}
