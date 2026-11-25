<?php

namespace App\Filament\Resources\IqTests\Schemas;

use App\Enums\ContentStatus;
use App\Models\IqTest;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Create/edit form of a Tes IQ: identity and duration, the material slides
 * (PPT exported to PDF, private, read only in the viewer) and the internal
 * notes the AI mentor reads. Questions are managed in "Bank Soal IQ".
 */
class IqTestForm
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
                Section::make('Tes')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->helperText('Contoh IQ-V01. Dipakai untuk impor/ekspor JSON.')
                            ->required()
                            ->maxLength(32)
                            ->unique(IqTest::class, 'code', ignoreRecord: true),
                        TextInput::make('title')
                            ->label('Judul')
                            ->default('Tes IQ Latihan')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('duration_minutes')
                            ->label('Durasi (menit)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(180)
                            ->default((int) config('iq.duration_minutes', 20))
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(ContentStatus::options())
                            ->default(ContentStatus::Draft->value)
                            ->helperText('Tes terbit paling baru yang dikerjakan mentee.')
                            ->required(),
                        Textarea::make('intro')
                            ->label('Teks pembuka (opsional)')
                            ->helperText('Kosongkan untuk teks bawaan. Pakai "kamu".')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Materi dan mentor AI')
                    ->schema([
                        FileUpload::make('material_pdf_path')
                            ->label('Materi (PPT diekspor ke PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize((int) config('iq.material_pdf.max_kb', 20480))
                            ->disk((string) config('iq.material_pdf.disk', 'local'))
                            ->directory((string) config('iq.material_pdf.directory', 'iq-material'))
                            ->visibility('private')
                            ->helperText('Simpan PPT sebagai PDF lalu unggah di sini (maks 20 MB, privat). Mentee berbayar hanya bisa membacanya di website, tanpa unduh dan cetak, dengan watermark nama dan email.')
                            ->columnSpanFull(),
                        TextInput::make('material_pdf_name')
                            ->label('Judul materi di viewer')
                            ->placeholder(IqTest::DEFAULT_MATERIAL_NAME)
                            ->maxLength(120),
                        Textarea::make('mentor_notes')
                            ->label('Catatan materi untuk mentor AI')
                            ->helperText('Ringkasan materi internal Skular / isi PPT dalam teks. Dibaca mentor AI sebagai sumber pengetahuan (maks 6.000 karakter yang dipakai). Jangan menempel teks dari sumber luar.')
                            ->rows(8)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }
}
