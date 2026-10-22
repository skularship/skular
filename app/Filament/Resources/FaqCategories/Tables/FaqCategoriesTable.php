<?php

namespace App\Filament\Resources\FaqCategories\Tables;

use App\Models\FaqCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FaqCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('faqs'))
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('faqs_count')
                    ->label('Jumlah FAQ')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'gray' : 'info'),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Terakhir diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Hapus')
                    ->disabled(fn (FaqCategory $record): bool => (int) $record->faqs_count > 0)
                    ->tooltip(fn (FaqCategory $record): ?string => (int) $record->faqs_count > 0
                        ? 'Kategori masih digunakan oleh FAQ. Pindahkan atau hapus FAQ tersebut terlebih dahulu.'
                        : null),
            ])
            ->emptyStateHeading('Belum ada kategori FAQ')
            ->emptyStateDescription('Buat kategori terlebih dahulu, lalu kelompokkan FAQ ke dalamnya agar halaman publik mudah dijelajahi.')
            ->emptyStateIcon('heroicon-o-tag');
    }
}
