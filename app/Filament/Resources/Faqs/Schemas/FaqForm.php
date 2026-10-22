<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Isi FAQ')
                    ->description('Tulis pertanyaan dan jawaban yang akan dibaca calon pendaftar beasiswa.')
                    ->schema([
                        Select::make('faq_category_id')
                            ->label('Kategori FAQ')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Kelola kategori dari menu Kategori FAQ bila perlu menambah kelompok baru.'),
                        TextInput::make('sort_order')
                            ->label('Urutan tampilan')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('Angka lebih kecil tampil lebih dulu. Bila angkanya sama, FAQ yang lebih lama tampil lebih dulu.'),
                        Textarea::make('question')
                            ->label('Pertanyaan')
                            ->placeholder('Contoh: Apakah Skular bisa digunakan oleh siswa kelas 12?')
                            ->rows(2)
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('answer')
                            ->label('Jawaban')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Gunakan paragraf, daftar, atau tautan bila jawaban perlu beberapa bagian. HTML disanitasi sebelum ditampilkan di website.'),
                    ])
                    ->columns(2),

                Section::make('Publikasi')
                    ->description('Draf tidak akan terlihat oleh pengunjung website maupun muncul pada pencarian publik.')
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Tampilkan di website')
                            ->default(false)
                            ->required()
                            ->helperText('Aktifkan setelah jawaban siap dibaca publik. Biarkan mati untuk menyimpan draf.'),
                        Toggle::make('is_featured')
                            ->label('Tampilkan sebagai FAQ pilihan')
                            ->default(false)
                            ->required()
                            ->helperText('FAQ pilihan diprioritaskan pada landing page. Maksimal lima FAQ pilihan yang tampil, diurutkan sesuai urutan tampilan.'),
                    ])
                    ->columns(2),
            ]);
    }
}
