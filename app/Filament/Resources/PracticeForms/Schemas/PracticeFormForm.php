<?php

namespace App\Filament\Resources\PracticeForms\Schemas;

use App\Enums\PracticeFieldType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * The practice form builder.
 *
 * Everything here is expressed in curriculum language: an admin adds
 * questions, picks an answer type, and flags which answers feed the mentee CV.
 * The stored JSON schema is produced by the form itself, so nobody has to
 * write JSON by hand — and the stored shape stays compatible with the mentee
 * practice view and the AI evaluation contract.
 */
class PracticeFormForm
{
    /**
     * Determine whether the selected answer type needs a list of options.
     */
    public static function typeHasOptions(Get $get): bool
    {
        $type = $get('type');

        if ($type instanceof PracticeFieldType) {
            return $type->hasOptions();
        }

        return PracticeFieldType::tryFrom((string) $type)?->hasOptions() ?? false;
    }

    /**
     * The fields that describe the exercise itself.
     *
     * Shared with the practice form section of the module editor.
     *
     * @return array<int, TextInput|Textarea|Toggle>
     */
    public static function exerciseFields(): array
    {
        return [
            TextInput::make('title')
                ->label('Judul latihan')
                ->placeholder('Contoh: Latihan menulis esai motivasi')
                ->required()
                ->maxLength(255)
                ->helperText('Tampil sebagai judul kartu latihan dan sebagai bagian CV bila diaktifkan.'),
            Textarea::make('description')
                ->label('Arahan latihan')
                ->placeholder('Contoh: Tulis paragraf pembuka esai motivasimu, maksimal 150 kata.')
                ->rows(2)
                ->columnSpanFull()
                ->helperText('Jelaskan apa yang harus dikerjakan mentee sebelum mengisi kolom jawaban.'),
            TextInput::make('ordering')
                ->label('Urutan tampil')
                ->required()
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText('Angka lebih kecil tampil lebih dulu pada langkah Praktik.'),
            Toggle::make('is_active')
                ->label('Tampilkan ke mentee')
                ->default(true)
                ->required()
                ->helperText('Matikan untuk menyiapkan latihan tanpa menampilkannya di modul.'),
        ];
    }

    /**
     * The question builder an admin uses to compose the practice form.
     */
    public static function questionBuilder(): Section
    {
        return Section::make('Pertanyaan latihan')
            ->description('Setiap pertanyaan menjadi satu input di halaman modul mentee. Tarik barisnya untuk mengubah urutan, atau duplikat untuk membuat pertanyaan serupa.')
            ->schema([
                Repeater::make('fields')
                    ->label('Pertanyaan')
                    ->schema([
                        TextInput::make('label')
                            ->label('Pertanyaan untuk mentee')
                            ->placeholder('Contoh: Peran / Jabatan')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Kode jawaban (internal)')
                            ->placeholder('contoh: peran')
                            ->helperText('Tidak tampil ke mentee. Dipakai sistem untuk menyimpan jawaban, jadi harus unik di dalam satu latihan.')
                            ->rules(['regex:/^[a-z0-9_]+$/'])
                            ->validationMessages([
                                'regex' => 'Gunakan huruf kecil, angka, atau garis bawah saja. Contoh: nama_prestasi.',
                                'distinct' => 'Kode ini sudah dipakai pertanyaan lain. Gunakan kode yang berbeda.',
                            ])
                            ->distinct()
                            ->required(),
                        Select::make('type')
                            ->label('Jenis jawaban')
                            ->options(PracticeFieldType::class)
                            ->default(PracticeFieldType::Text->value)
                            ->required()
                            ->live()
                            ->helperText('Menentukan bentuk input yang dilihat mentee.'),
                        Toggle::make('required')
                            ->label('Wajib diisi mentee')
                            ->default(false)
                            ->helperText('Latihan tidak bisa dikirim sebelum kolom ini diisi.'),
                        TextInput::make('placeholder')
                            ->label('Contoh jawaban di dalam kolom')
                            ->placeholder('Contoh: Ketua BEM 2025'),
                        Textarea::make('help_text')
                            ->label('Petunjuk pengisian')
                            ->placeholder('Contoh: Tuliskan jabatan dan periode.')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Muncul di bawah kolom sebagai arahan tambahan.'),
                        TagsInput::make('options')
                            ->label('Pilihan jawaban')
                            ->placeholder('Ketik pilihan lalu tekan Enter')
                            ->helperText('Dipakai hanya untuk jenis Pilihan Dropdown, Pilihan Tunggal, dan Pilihan Ganda.')
                            ->visible(self::typeHasOptions(...))
                            ->required(self::typeHasOptions(...))
                            ->columnSpanFull(),
                    ])
                    ->itemLabel(fn (array $state): string => filled($state['label'] ?? null)
                        ? (string) $state['label']
                        : 'Pertanyaan baru')
                    ->addActionLabel('Tambah pertanyaan')
                    ->reorderable()
                    ->cloneable()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * The settings that decide whether answers appear on the mentee CV.
     */
    public static function cvSettings(): Section
    {
        return Section::make('Tampilan di CV mentee')
            ->description('Aktifkan bila jawaban latihan ini menjadi salah satu bagian CV mentee.')
            ->schema([
                Toggle::make('include_in_cv')
                    ->label('Tampilkan di halaman CV')
                    ->default(false)
                    ->required()
                    ->live()
                    ->helperText('Jawaban terbaru mentee dipakai sebagai isi bagian ini.'),
                TextInput::make('cv_ordering')
                    ->label('Urutan di CV')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->visible(fn (Get $get): bool => (bool) $get('include_in_cv'))
                    ->helperText('Angka lebih kecil tampil lebih atas.'),
            ])
            ->columns(2);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Latihan')
                    ->description('Latihan praktik dinilai AI dan tidak mengubah progres modul sampai mentee menekan checkpoint.')
                    ->schema([
                        Select::make('module_id')
                            ->label('Modul')
                            ->relationship('module', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Latihan ini muncul pada modul yang dipilih.'),
                        ...self::exerciseFields(),
                    ])
                    ->columns(2),

                self::questionBuilder(),
                self::cvSettings(),
            ]);
    }
}
