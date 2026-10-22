<?php

namespace App\Filament\Resources\QuickQuestions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuickQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quick Question')
                    ->description('Pernyataan Benar/Salah yang dikerjakan mentee setelah membaca materi pada langkah Pahami.')
                    ->schema([
                        Select::make('module_id')
                            ->label('Modul')
                            ->relationship('module', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pertanyaan ini tampil pada modul yang dipilih.'),
                        Toggle::make('is_active')
                            ->label('Tampilkan ke mentee')
                            ->default(true)
                            ->required()
                            ->helperText('Matikan untuk menyiapkan pertanyaan tanpa menampilkannya.'),
                        Textarea::make('question')
                            ->label('Pernyataan untuk mentee')
                            ->placeholder('Contoh: Tujuan belajar yang baik harus spesifik dan terukur.')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        Toggle::make('answer')
                            ->label('Pernyataan ini benar')
                            ->default(true)
                            ->required()
                            ->helperText('Biarkan aktif bila pernyataan di atas memang benar.'),
                        TextInput::make('ordering')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Angka lebih kecil tampil lebih dulu.'),
                        Textarea::make('explanation')
                            ->label('Penjelasan jawaban')
                            ->placeholder('Contoh: Sebutkan angka target agar tujuannya bisa diukur.')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Muncul setelah mentee menjawab, untuk menjelaskan kunci jawaban.'),
                    ])
                    ->columns(2),
            ]);
    }
}
