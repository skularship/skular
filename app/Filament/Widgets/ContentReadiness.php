<?php

namespace App\Filament\Widgets;

use App\Contracts\ModuleReadinessServiceInterface;
use App\Data\ModuleReadinessCheckData;
use App\Enums\ContentStatus;
use App\Filament\Actions\PublishModuleAction;
use App\Filament\Resources\Modules\ModuleResource;
use App\Models\Module;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The work queue of the curriculum team: modules that are still drafts, what is
 * missing in each of them, and a direct link to continue editing.
 */
class ContentReadiness extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ModuleResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Modul draf yang perlu diselesaikan')
            ->description('Daftar ini diambil dari modul berstatus draf. Kolom kelengkapan dihitung dari isi modul, bukan centang manual.')
            ->query(fn (): Builder => Module::query()
                ->where('content_status', ContentStatus::Draft->value)
                ->with(['fase', 'scholarship', 'practiceForms', 'quickQuestions'])
                ->orderByDesc('updated_at'))
            ->columns([
                TextColumn::make('title')
                    ->label('Modul')
                    ->description(fn (Module $record): string => collect([
                        $record->fase->title,
                        $record->category->getLabel(),
                    ])->implode(' · '))
                    ->wrap()
                    ->weight('semibold'),
                TextColumn::make('readiness')
                    ->label('Kelengkapan')
                    ->state(fn (Module $record): int => app(ModuleReadinessServiceInterface::class)->progress($record))
                    ->formatStateUsing(fn (int $state): string => $state.'%')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('missing')
                    ->label('Yang masih kurang')
                    ->state(function (Module $record): string {
                        $blockers = app(ModuleReadinessServiceInterface::class)->blockers($record);

                        if ($blockers === []) {
                            return 'Siap diterbitkan';
                        }

                        return collect($blockers)
                            ->map(fn (ModuleReadinessCheckData $check): string => $check->label)
                            ->implode(', ');
                    })
                    ->color(fn (string $state): string => $state === 'Siap diterbitkan' ? 'success' : 'danger')
                    ->wrap(),
                TextColumn::make('updated_at')
                    ->label('Terakhir diubah')
                    ->since()
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10])
            ->recordActions([
                Action::make('continue')
                    ->label('Lanjutkan')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Module $record): string => ModuleResource::getUrl('edit', ['record' => $record])),
                PublishModuleAction::make(),
                Action::make('preview')
                    ->label('Pratinjau')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Module $record): string => ModuleResource::getUrl('preview', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->recordUrl(fn (Module $record): string => ModuleResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading('Tidak ada modul draf')
            ->emptyStateDescription('Semua modul sudah diterbitkan. Buat modul baru bila ada materi yang perlu ditambahkan.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
