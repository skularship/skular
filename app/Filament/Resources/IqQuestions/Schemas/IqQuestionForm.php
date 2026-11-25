<?php

namespace App\Filament\Resources\IqQuestions\Schemas;

use App\Enums\IqSubtest;
use App\Models\IqQuestion;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

/**
 * Create/edit form of a Tes IQ question: test, number and subtest, optional
 * reading text (Teks), image + alt text (required for Visuospasial), the
 * prompt, four required options, exactly one key A-D and the explanation.
 * "Aktif" is refused while IqQuestion::activationErrors() is not empty.
 */
class IqQuestionForm
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
                Section::make('Soal')
                    ->schema([
                        Select::make('iq_test_id')
                            ->label('Tes')
                            ->relationship('test', 'title')
                            ->preload()
                            ->required(),
                        TextInput::make('number')
                            ->label('Nomor')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->required()
                            ->unique(IqQuestion::class, 'number', ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('iq_test_id', $get('iq_test_id'))),
                        Select::make('subtest')
                            ->label('Subtes')
                            ->options(IqSubtest::options())
                            ->required()
                            ->live(),
                        Toggle::make('is_active')
                            ->label('Aktif (tampil ke mentee)')
                            ->helperText('Hanya bisa aktif bila soal, empat pilihan, kunci, dan (Visuospasial) gambar + teks alternatif sudah lengkap.')
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                if (! $value) {
                                    return;
                                }

                                foreach (self::draftFromForm($get)->activationErrors() as $message) {
                                    $fail($message);
                                }
                            }),
                        Textarea::make('passage')
                            ->label('Bacaan (subtes Teks, opsional)')
                            ->helperText('Ditampilkan di atas soal. Soal dengan bacaan yang sama memakai teks yang sama.')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('prompt')
                            ->label('Teks soal')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Gambar')
                    ->description('Wajib untuk soal Visuospasial (nomor 8-14). Pastikan tetap jelas di layar HP.')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Gambar soal')
                            ->image()
                            ->disk((string) config('iq.images.disk', 'public'))
                            ->directory((string) config('iq.images.directory', 'iq-questions'))
                            ->visibility('public')
                            ->maxSize((int) config('iq.images.max_kb', 2048))
                            ->required(fn (Get $get): bool => $get('subtest') === IqSubtest::Visuospasial->value && (bool) $get('is_active')),
                        Textarea::make('image_alt')
                            ->label('Teks alternatif gambar')
                            ->helperText('Jelaskan isi gambar tanpa membocorkan jawaban, misalnya "Tiga baris bentuk; baris ketiga berisi lingkaran, segitiga, dan satu kotak kosong."')
                            ->rows(2)
                            ->maxLength(500)
                            ->requiredWith('image_path'),
                    ])
                    ->columns(2),
                Section::make('Pilihan dan kunci')
                    ->schema([
                        TextInput::make('option_a')->label('Pilihan A')->required()->maxLength(500),
                        TextInput::make('option_b')->label('Pilihan B')->required()->maxLength(500),
                        TextInput::make('option_c')->label('Pilihan C')->required()->maxLength(500),
                        TextInput::make('option_d')->label('Pilihan D')->required()->maxLength(500),
                        Select::make('answer_key')
                            ->label('Kunci')
                            ->options(array_combine(IqQuestion::LETTERS, IqQuestion::LETTERS))
                            ->required(),
                        Textarea::make('explanation')
                            ->label('Pembahasan (untuk mentee berbayar)')
                            ->helperText('Pakai "kamu", sederhana, jelaskan cara menemukan jawabannya.')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    /**
     * An unsaved question built from the current form state, for the activation check.
     */
    private static function draftFromForm(Get $get): IqQuestion
    {
        $image = $get('image_path');

        if (is_array($image)) {
            $image = $image === [] ? null : 'upload';
        }

        return new IqQuestion([
            'subtest' => IqSubtest::tryFrom((string) $get('subtest')) ?? IqSubtest::Numerik,
            'prompt' => (string) $get('prompt'),
            'image_path' => filled($image) ? (string) $image : null,
            'image_alt' => $get('image_alt'),
            'option_a' => $get('option_a'),
            'option_b' => $get('option_b'),
            'option_c' => $get('option_c'),
            'option_d' => $get('option_d'),
            'answer_key' => $get('answer_key'),
        ]);
    }
}
