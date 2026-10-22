<?php

namespace App\Filament\Resources\Modules\Tables;

use App\Contracts\ModuleReadinessServiceInterface;
use App\Enums\ContentStatus;
use App\Enums\ModuleCategory;
use App\Enums\ModuleType;
use App\Filament\Actions\PublishModuleAction;
use App\Filament\Actions\UnpublishModuleAction;
use App\Filament\Resources\Modules\ModuleResource;
use App\Models\Module;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fase_id', 'modul_order')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['fase', 'scholarship', 'practiceForms', 'quickQuestions']))
            ->columns([
                TextColumn::make('title')
                    ->label('Judul modul')
                    ->description(fn (Module $record): string => collect([
                        $record->fase->title,
                        $record->scholarship?->scholarship_name,
                    ])->filter()->implode(' · '))
                    ->searchable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('modul_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->hiddenFrom('md'),
                // Badge tipe modul dengan warna berbeda per kategori
                BadgeColumn::make('category')
                    ->label('Tipe')
                    ->colors([
                        'primary' => ModuleCategory::Inti->value,
                        'warning' => ModuleCategory::Template->value,
                        'secondary' => ModuleCategory::Perpustakaan->value,
                    ])
                    ->color(fn (Module $record): string => match ($record->category) {
                        ModuleCategory::Inti => 'primary',
                        ModuleCategory::Template => 'warning',
                        default => 'secondary',
                    }),
                TextColumn::make('content_status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('readiness')
                    ->label('Kelengkapan')
                    ->state(fn (Module $record): int => app(ModuleReadinessServiceInterface::class)->progress($record))
                    ->formatStateUsing(fn (int $state): string => $state.'%')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->tooltip('Dihitung dari informasi dasar, materi, quick question, dan latihan yang terhubung.'),
                TextColumn::make('practice_forms_count')
                    ->label('Latihan')
                    ->counts('practiceForms')
                    ->badge()
                    ->color('warning')
                    ->toggleable()
                    ->hiddenFrom('md'),
                TextColumn::make('quick_questions_count')
                    ->label('Kuis')
                    ->counts('quickQuestions')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->label('Format')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('content_status')
                    ->label('Status konten')
                    ->options(ContentStatus::options()),
                SelectFilter::make('category')
                    ->label('Tipe modul')
                    ->options(ModuleCategory::options()),
                SelectFilter::make('fase_id')
                    ->label('Fase')
                    ->relationship('fase', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('scholarship_id')
                    ->label('Beasiswa')
                    ->relationship('scholarship', 'scholarship_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('type')
                    ->label('Format penyajian')
                    ->options(ModuleType::options()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('preview')
                    ->label('Pratinjau')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Module $record): string => ModuleResource::getUrl('preview', ['record' => $record]))
                    ->openUrlInNewTab(),
                PublishModuleAction::make(),
                UnpublishModuleAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::publishSelectedAction(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada modul')
            ->emptyStateDescription('Mulai dengan membuat modul pertama, lengkapi materi, quick question, dan latihan, lalu terbitkan agar tampil pada jalur belajar mentee.')
            ->emptyStateIcon('heroicon-o-book-open')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Buat modul'),
            ]);
    }

    /**
     * Publish every selected module that already passes the completeness check.
     */
    private static function publishSelectedAction(): BulkAction
    {
        return BulkAction::make('publishSelected')
            ->label('Terbitkan yang siap')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Terbitkan modul yang sudah lengkap?')
            ->modalDescription('Modul yang belum lengkap dilewati dan tetap berstatus draf, sehingga tidak ada konten setengah jadi yang terlihat mentee.')
            ->modalSubmitActionLabel('Terbitkan')
            ->authorizeIndividualRecords('update')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $readiness = app(ModuleReadinessServiceInterface::class);

                /** @var Collection<int, Module> $records */
                $published = 0;
                $blocked = [];

                foreach ($records as $record) {
                    if ($record->isPublished()) {
                        continue;
                    }

                    if (! $readiness->isReady($record)) {
                        $blocked[] = $record->title;

                        continue;
                    }

                    $record->update(['content_status' => ContentStatus::Published]);
                    $published++;
                }

                if ($published > 0) {
                    Notification::make()
                        ->title($published.' modul diterbitkan')
                        ->body('Modul yang diterbitkan sekarang terlihat oleh mentee.')
                        ->success()
                        ->send();
                }

                if ($blocked !== []) {
                    Notification::make()
                        ->title(count($blocked).' modul belum lengkap')
                        ->body('Lengkapi dulu: '.implode(', ', $blocked))
                        ->warning()
                        ->persistent()
                        ->send();
                }

                if ($published === 0 && $blocked === []) {
                    Notification::make()
                        ->title('Tidak ada modul draf pada pilihan ini')
                        ->info()
                        ->send();
                }
            });
    }
}
