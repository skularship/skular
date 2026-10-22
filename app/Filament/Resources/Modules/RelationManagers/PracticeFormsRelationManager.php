<?php

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Filament\Resources\PracticeForms\Schemas\PracticeFormForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Builds the practice exercises of a module from the module editor itself,
 * using the same schema as the stand-alone practice form resource.
 */
class PracticeFormsRelationManager extends RelationManager
{
    protected static string $relationship = 'practiceForms';

    /**
     * Render with the module editor so the whole module is visible at once.
     */
    protected static bool $isLazy = false;

    protected static ?string $title = 'Latihan praktik';

    protected static ?string $modelLabel = 'latihan praktik';

    protected static ?string $pluralModelLabel = 'Latihan praktik';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Latihan')
                    ->schema(PracticeFormForm::exerciseFields())
                    ->columns(2),

                PracticeFormForm::questionBuilder(),
                PracticeFormForm::cvSettings(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('ordering')
            ->columns([
                TextColumn::make('ordering')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Judul latihan')
                    ->searchable()
                    ->wrap()
                    ->weight('semibold'),
                TextColumn::make('fields')
                    ->label('Jumlah pertanyaan')
                    ->state(fn ($record): int => count($record->fields ?? []))
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'success'),
                IconColumn::make('include_in_cv')
                    ->label('Masuk CV')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Tampil')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah latihan'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('5xl'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada latihan praktik')
            ->emptyStateDescription('Latihan bersifat opsional. Tambahkan bila modul perlu menghasilkan jawaban tertulis yang dinilai AI dan bisa masuk CV mentee.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
