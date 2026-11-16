<?php

namespace App\Filament\Resources\Scholarships\Schemas;

use App\Contracts\ScholarshipImageServiceInterface;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Enums\ScholarshipStatus;
use App\Filament\Schemas\ListRepeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Create/edit form schema of the Beasiswa resource.
 */
class ScholarshipForm
{
    /**
     * Add the form components to the schema.
     *
     * @param  Schema  $schema  The schema to configure.
     * @return Schema The configured schema.
     */
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
                            ->label('Institusi penyelenggara')
                            ->maxLength(255),
                        Select::make('level')
                            ->label('Jenjang')
                            ->options(EducationLevel::class)
                            ->required(),
                        TextInput::make('location')
                            ->label('Lokasi')
                            ->maxLength(255),
                        Select::make('scope')
                            ->label('Lokasi studi')
                            ->options(LocationScope::studyDestinationOptions())
                            ->helperText('Hanya untuk cakupan studi. Kategori pendanaan dicatat pada bagian Benefit Utama.'),
                        Select::make('cluster_id')
                            ->label('Cluster bidang')
                            ->relationship('cluster', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('link')
                            ->label('Link resmi')
                            ->url()
                            ->maxLength(255),
                        Select::make('status')
                            ->label('Status')
                            ->options(ScholarshipStatus::class)
                            ->default(ScholarshipStatus::Draft->value)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Gambar kartu')
                    ->description('Tampil di kartu rekomendasi mentee. Opsional: tanpa gambar, kartu memakai ilustrasi bawaan Skular.')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Gambar kartu')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(ScholarshipImageServiceInterface::MAX_UPLOAD_KB)
                            ->disk(ScholarshipImageServiceInterface::DISK)
                            ->directory(ScholarshipImageServiceInterface::DIRECTORY)
                            ->visibility('public')
                            // The 5 MB upload is downscaled to a small WebP and
                            // the original is discarded (see ScholarshipImageService).
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(ScholarshipImageServiceInterface::class)->store($file))
                            ->helperText('JPG, PNG, atau WebP, maksimal 5 MB. Otomatis diperkecil. Pakai ilustrasi Skular atau gambar netral. Jangan unggah logo penyelenggara beasiswa.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Persyaratan')
                    ->schema([
                        Toggle::make('sktm_require')
                            ->label('Wajib SKTM/KHI')
                            ->default(false),
                        TextInput::make('minimum_value')
                            ->label('IPK minimum')
                            // Same 0–4 scale as the mentee profile (ProfilingRules)
                            // and the decimal(3,2) column.
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(4)
                            ->step(0.01)
                            ->rule('decimal:0,2')
                            ->helperText('Skala 0–4, maksimal 2 angka di belakang koma. Kosongkan bila tidak ada batas.'),
                        TextInput::make('maximum_age')
                            ->label('Usia maksimal (tahun)')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(100),
                        TextInput::make('estimated_quota')
                            ->label('Estimasi kuota')
                            // Upper bound of the unsigned int column.
                            ->integer()
                            ->minValue(1)
                            ->maxValue(4294967295),
                    ])
                    ->columns(2),

                Section::make('Jadwal')
                    ->schema([
                        DatePicker::make('opening_date')
                            ->label('Tanggal dibuka'),
                        DatePicker::make('closing_date')
                            ->label('Tanggal ditutup')
                            ->afterOrEqual(fn (Get $get): ?string => filled($get('opening_date')) ? 'opening_date' : null)
                            ->validationMessages(['after_or_equal' => 'Tanggal ditutup tidak boleh sebelum tanggal dibuka.']),
                        DatePicker::make('verification_date')
                            ->label('Tanggal verifikasi')
                            ->afterOrEqual(fn (Get $get): ?string => filled($get('opening_date')) ? 'opening_date' : null)
                            ->validationMessages(['after_or_equal' => 'Tanggal verifikasi tidak boleh sebelum tanggal dibuka.']),
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
