<?php

namespace App\Filament\Resources\Scholarships\Schemas;

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Enums\ScholarshipStatus;
use App\Filament\Schemas\ListRepeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScholarshipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Beasiswa')
                    ->schema([
                        TextInput::make('scholarship_name')
                            ->label('Nama beasiswa')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('institution')
                            ->label('Institusi penyelenggara'),
                        Select::make('level')
                            ->label('Jenjang')
                            ->options(EducationLevel::class)
                            ->required(),
                        TextInput::make('location')
                            ->label('Lokasi'),
                        Select::make('scope')
                            ->label('Cakupan')
                            ->options(LocationScope::class),
                        Select::make('cluster_id')
                            ->label('Cluster bidang')
                            ->relationship('cluster', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('link')
                            ->label('Link resmi')
                            ->url(),
                        Select::make('status')
                            ->label('Status')
                            ->options(ScholarshipStatus::class)
                            ->default(ScholarshipStatus::Draft->value)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Persyaratan')
                    ->schema([
                        Toggle::make('sktm_require')
                            ->label('Wajib SKTM/KHI')
                            ->default(false),
                        TextInput::make('minimum_value')
                            ->label('IPK minimum')
                            ->numeric(),
                        TextInput::make('maximum_age')
                            ->label('Usia maksimal (tahun)')
                            ->numeric(),
                        TextInput::make('estimated_quota')
                            ->label('Estimasi kuota')
                            ->numeric(),
                    ])
                    ->columns(2),

                Section::make('Jadwal')
                    ->schema([
                        DatePicker::make('opening_date')
                            ->label('Tanggal dibuka'),
                        DatePicker::make('closing_date')
                            ->label('Tanggal ditutup'),
                        DatePicker::make('verification_date')
                            ->label('Tanggal verifikasi'),
                    ])
                    ->columns(3),

                Section::make('Benefit Utama')
                    ->description('Daftar keuntungan yang ditawarkan beasiswa. Tampil sebagai list pada halaman detail.')
                    ->schema([
                        ListRepeater::make(
                            name: 'key_benefits',
                            addItemLabel: 'Tambah benefit',
                            placeholder: 'Contoh: Biaya kuliah penuh',
                        ),
                    ]),

                Section::make('Nilai yang Dicari')
                    ->description('Karakteristik atau nilai yang dihargai penyelenggara. Tidak diposisikan sebagai syarat mutlak yang menggugurkan.')
                    ->schema([
                        ListRepeater::make(
                            name: 'values_sought',
                            addItemLabel: 'Tambah nilai',
                            placeholder: 'Contoh: Kepemimpinan',
                        ),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('note')
                            ->hiddenLabel()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
