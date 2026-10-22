<?php

namespace App\Filament\Resources\Faqs\Tables;

use App\Filament\Actions\PublishFaqAction;
use App\Filament\Actions\UnpublishFaqAction;
use App\Models\Faq;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('category'))
            ->columns([
                TextColumn::make('question')
                    ->label('Pertanyaan')
                    ->description(fn (Faq $record): string => Str::limit(strip_tags($record->answer), 90))
                    ->searchable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('is_published')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Terbit' : 'Draf')
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Pilihan')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Terakhir diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('faq_category_id')
                    ->label('Kategori FAQ')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_published')
                    ->label('Status publikasi'),
                TernaryFilter::make('is_featured')
                    ->label('FAQ pilihan'),
            ])
            ->recordActions([
                EditAction::make(),
                PublishFaqAction::make(),
                UnpublishFaqAction::make(),
                DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::publishSelectedAction(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada FAQ')
            ->emptyStateDescription('Tambahkan pertanyaan pertama, lalu terbitkan agar tampil pada halaman FAQ publik.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }

    /**
     * Publish every selected draft FAQ.
     *
     * The action never touches featured status or ordering, so publishing a
     * batch cannot silently change which answers the landing page prioritises.
     */
    private static function publishSelectedAction(): BulkAction
    {
        return BulkAction::make('publishSelected')
            ->label('Terbitkan yang dipilih')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Terbitkan FAQ yang dipilih?')
            ->modalDescription('FAQ yang sudah terbit dilewati. FAQ draf akan langsung tampil di halaman publik setelah dikonfirmasi.')
            ->modalSubmitActionLabel('Terbitkan')
            ->authorizeIndividualRecords('update')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                /** @var Collection<int, Faq> $records */
                $published = 0;

                foreach ($records as $record) {
                    if ($record->is_published) {
                        continue;
                    }

                    $record->update(['is_published' => true]);
                    $published++;
                }

                Notification::make()
                    ->title($published === 0 ? 'Tidak ada FAQ draf pada pilihan ini' : $published.' FAQ diterbitkan')
                    ->body($published === 0
                        ? 'Semua FAQ yang dipilih sudah terbit.'
                        : 'FAQ yang diterbitkan sekarang dapat dibaca pengunjung website.')
                    ->success()
                    ->send();
            });
    }
}
