<?php

namespace App\Filament\Resources\TryoutUnits\Schemas;

use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use App\Enums\TryoutSkill;
use App\Filament\Resources\TryoutQuestions\Schemas\TryoutQuestionForm;
use App\Models\TryoutUnit;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Create/edit form of a Try Out unit. Fields follow the skill: passage for
 * Reading; audio, transcript (Pro/admin only), table and map for Listening.
 */
class TryoutUnitForm
{
    /**
     * Add the form components to the schema.
     *
     * @param  Schema  $schema  The schema to configure.
     * @return Schema The configured schema.
     */
    public static function configure(Schema $schema): Schema
    {
        $is = fn (TryoutSkill $skill): \Closure => fn (Get $get): bool => $get('skill') === $skill->value || $get('skill') === $skill;

        return $schema
            ->components([
                Section::make('Unit')
                    ->schema([
                        TextInput::make('code')->label('Kode')->helperText('Contoh LB-U1 (Listening), RB-U1 (Reading).')->required()->maxLength(32)
                            ->unique(TryoutUnit::class, 'code', ignoreRecord: true),
                        Select::make('skill')->label('Subtes')->options(TryoutSkill::options())->required()->live(),
                        Select::make('exam_type')->label('Jenis ujian')->options(TryoutExamType::enabledOptions())->default(TryoutExamType::Ielts->value)->required(),
                        Select::make('status')->label('Status')->options(ContentStatus::options())->default(ContentStatus::Draft->value)->required(),
                        TextInput::make('title')->label('Judul')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('context')->label('Konteks')->placeholder('Staff perpustakaan dan mahasiswa')->maxLength(255)->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Teks bacaan')
                    ->visible($is(TryoutSkill::Reading))
                    ->schema([
                        MarkdownEditor::make('passage')->label('Teks (Markdown)')->required($is(TryoutSkill::Reading))->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Audio Listening')
                    ->visible($is(TryoutSkill::Listening))
                    ->schema([
                        FileUpload::make('audio_path')
                            ->label('Audio (mp3)')
                            ->acceptedFileTypes(['audio/mpeg', 'audio/mp3'])
                            ->maxSize(20480)
                            ->disk(TryoutUnit::MEDIA_DISK)
                            ->directory(TryoutUnit::AUDIO_DIRECTORY)
                            ->visibility('public')
                            ->columnSpanFull(),
                        TextInput::make('audio_duration_seconds')->label('Durasi audio (detik)')->numeric(),
                        Textarea::make('transcript')->label('Naskah (khusus Pro dan admin)')->rows(8)->helperText('Pakai penanda pembicara, mis. [Staff]. Dibaca mentor AI, tidak tampil saat tes.')->columnSpanFull(),
                        Textarea::make('attachment')
                            ->label('Tabel di bawah audio (opsional)')
                            ->rows(5)
                            ->helperText('Satu baris per baris tabel, kolom dipisah |. Baris pertama = judul kolom.')
                            ->formatStateUsing(fn (mixed $state): string => TryoutQuestionForm::tableToText($state))
                            ->dehydrateStateUsing(fn (mixed $state): ?array => TryoutQuestionForm::textToTable($state))
                            ->columnSpanFull(),
                        FileUpload::make('image_path')->label('Gambar peta (opsional)')->image()->disk(TryoutUnit::MEDIA_DISK)->directory(TryoutUnit::IMAGE_DIRECTORY)->visibility('public')->maxSize(4096),
                        KeyValue::make('image_points')->label('Titik peta (huruf → label)')->keyLabel('Huruf')->valueLabel('Label')
                            ->helperText('Mentee memilih huruf di bawah peta (klik langsung di peta belum di v1).'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
