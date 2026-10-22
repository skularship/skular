<?php

namespace App\Filament\Resources\Fases\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fase pembelajaran')
                    ->description('Fase mengelompokkan modul pada jalur belajar mentee. Urutan fase menentukan urutan roadmap.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Nama fase')
                            ->placeholder('Contoh: Fase 1 — Fondasi Diri')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('subtitle')
                            ->label('Subjudul')
                            ->placeholder('Contoh: Kenali dirimu sebelum melamar')
                            ->maxLength(255),
                        TextInput::make('fase_order')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Urutan menentukan posisi di roadmap mentee.'),
                        Textarea::make('short_description')
                            ->label('Deskripsi')
                            ->placeholder('Contoh: Mentee membangun pemahaman tentang tujuan, kekuatan, dan cerita dirinya.')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Tampil pada roadmap mentee sebagai penjelasan fase.'),
                    ])
                    ->columns(2),
            ]);
    }
}
