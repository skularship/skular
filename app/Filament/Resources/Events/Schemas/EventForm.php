<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventAudience;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Create/edit form schema of the Event resource. Times are entered in WIB
 * and stored in UTC.
 */
class EventForm
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
                Section::make('Acara')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(150)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                if (blank($get('slug'))) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Alamat (slug)')
                            ->helperText('Dipakai di URL /event/{slug}. Huruf kecil dan tanda hubung.')
                            ->required()
                            ->alphaDash()
                            ->maxLength(150)
                            ->unique(Event::class, 'slug', ignoreRecord: true),
                        Select::make('type')
                            ->label('Jenis')
                            ->options(EventType::options())
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(EventStatus::options())
                            ->default(EventStatus::Draft->value)
                            ->helperText('Hanya event Terbit yang tampil ke mentee. Event yang sudah punya pendaftar tidak bisa dihapus; pakai Dibatalkan.')
                            ->required(),
                        MarkdownEditor::make('description')
                            ->label('Deskripsi')
                            ->helperText('Markdown. Pakai sapaan "kamu".')
                            ->columnSpanFull(),
                        FileUpload::make('image_path')
                            ->label('Poster')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->disk(Event::IMAGE_DISK)
                            ->directory(Event::IMAGE_DIRECTORY)
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Waktu dan tempat')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Mulai (WIB)')
                            ->timezone(Event::DISPLAY_TIMEZONE)
                            ->seconds(false)
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->label('Selesai (WIB)')
                            ->timezone(Event::DISPLAY_TIMEZONE)
                            ->seconds(false)
                            ->after('starts_at')
                            ->required(),
                        TextInput::make('location_url')
                            ->label('Link Zoom/Meet')
                            ->helperText('Hanya tampil untuk mentee yang terdaftar, mulai H-1.')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('recording_url')
                            ->label('Link rekaman')
                            ->helperText('Isi setelah acara; tampil di tab Rekaman.')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('is_free_preview')
                            ->label('Pratinjau gratis')
                            ->helperText('Rekaman event khusus Pro ini bisa ditonton mentee Gratis sebagai contoh (1 rekaman pratinjau). Hanya satu event yang bisa ditandai: menandai event ini akan melepas tanda di event lain.')
                            ->visible(fn (Get $get): bool => $get('audience') === EventAudience::Pro->value || $get('audience') === EventAudience::Pro)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Peserta')
                    ->schema([
                        TextInput::make('capacity')
                            ->label('Kapasitas')
                            ->helperText('Kosongkan untuk tanpa batas.')
                            ->numeric()
                            ->minValue(1),
                        Select::make('audience')
                            ->label('Siapa yang bisa daftar')
                            ->options(EventAudience::options())
                            ->default(EventAudience::Semua->value)
                            ->live()
                            ->required(),
                        TextInput::make('invite_tag')
                            ->label('Tag undangan')
                            ->helperText('Hanya mentee dengan tag ini (diatur di menu Pengguna) yang bisa melihat dan mendaftar. Contoh: alumni_akademi_unggulan.')
                            ->alphaDash()
                            ->maxLength(64)
                            ->visible(fn (Get $get): bool => $get('audience') === EventAudience::Undangan->value || $get('audience') === EventAudience::Undangan)
                            ->required(fn (Get $get): bool => $get('audience') === EventAudience::Undangan->value || $get('audience') === EventAudience::Undangan),
                        Repeater::make('registration_questions')
                            ->label('Pertanyaan pendaftaran (pengganti Google Form)')
                            ->simple(
                                TextInput::make('question')
                                    ->label('Pertanyaan')
                                    ->maxLength(200)
                                    ->required(),
                            )
                            ->maxItems(Event::MAX_QUESTIONS)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah pertanyaan')
                            ->helperText('Maksimal '.Event::MAX_QUESTIONS.' pertanyaan singkat, semuanya wajib dijawab saat mendaftar.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
