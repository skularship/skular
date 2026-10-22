<?php

namespace App\Filament\Resources\FaqCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FaqCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi kategori')
                    ->description('Kategori mengelompokkan FAQ pada halaman publik dan dapat diurutkan sendiri.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama kategori')
                            ->placeholder('Contoh: Administrasi & Pemberkasan')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->rules(['alpha_dash'])
                            ->unique(ignoreRecord: true)
                            ->helperText('Dipakai pada alamat filter halaman FAQ, misalnya /faq?kategori=administrasi-pemberkasan. Gunakan huruf, angka, dan tanda hubung.'),
                        Textarea::make('description')
                            ->label('Deskripsi singkat')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Opsional. Bisa dipakai sebagai penjelasan grup pada tampilan publik.'),
                        TextInput::make('sort_order')
                            ->label('Urutan tampilan')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('Angka lebih kecil tampil lebih dulu.'),
                        Toggle::make('is_active')
                            ->label('Aktif dan tampil di website')
                            ->default(true)
                            ->required()
                            ->helperText('Kategori nonaktif disembunyikan dari halaman publik, dan FAQ di dalamnya tidak tampil sampai kategori diaktifkan lagi.'),
                    ])
                    ->columns(2),
            ]);
    }
}
