<?php

namespace App\Filament\Resources\Modules\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Lets the curriculum team write the True/False questions of a module without
 * leaving the module editor.
 */
class QuickQuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'quickQuestions';

    /**
     * Render with the module editor so the whole module is visible at once.
     */
    protected static bool $isLazy = false;

    protected static ?string $title = 'Quick Question';

    protected static ?string $modelLabel = 'quick question';

    protected static ?string $pluralModelLabel = 'Quick Question';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('question')
                    ->label('Pernyataan untuk mentee')
                    ->placeholder('Contoh: Tujuan belajar yang baik harus spesifik dan terukur.')
                    ->rows(2)
                    ->required()
                    ->columnSpanFull()
                    ->helperText('Mentee menilai pernyataan ini Benar atau Salah setelah membaca materi.'),
                Toggle::make('answer')
                    ->label('Pernyataan ini benar')
                    ->default(true)
                    ->required()
                    ->helperText('Biarkan aktif bila pernyataan di atas memang benar.'),
                Toggle::make('is_active')
                    ->label('Tampilkan ke mentee')
                    ->default(true)
                    ->required()
                    ->helperText('Matikan untuk menyiapkan pertanyaan tanpa menampilkannya.'),
                TextInput::make('ordering')
                    ->label('Urutan')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->helperText('Angka lebih kecil tampil lebih dulu.'),
                Textarea::make('explanation')
                    ->label('Penjelasan jawaban')
                    ->placeholder('Contoh: Sebutkan angka target agar tujuannya bisa diukur.')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Muncul setelah mentee menjawab, untuk menjelaskan kunci jawaban.'),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->defaultSort('ordering')
            ->columns([
                TextColumn::make('ordering')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('question')
                    ->label('Pernyataan')
                    ->searchable()
                    ->wrap()
                    ->weight('semibold'),
                IconColumn::make('answer')
                    ->label('Kunci: Benar')
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
                    ->label('Tambah quick question'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada quick question')
            ->emptyStateDescription('Quick question bersifat opsional. Tambahkan bila modul perlu menguji pemahaman mentee sebelum latihan praktik.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }
}
