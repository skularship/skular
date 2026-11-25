<?php

namespace App\Filament\Resources\TryoutQuestions\Schemas;

use App\Enums\ContentStatus;
use App\Enums\TryoutQuestionType;
use App\Enums\TryoutSkill;
use App\Models\TryoutQuestion;
use App\Models\TryoutUnit;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * Create/edit form of a Try Out question (Bank Soal and the unit questions
 * tab). The question type drives the fields: options A, B, C... (default 4,
 * at least 2, soft warning when not 4, D2) with the key picked from the
 * filled options; short text with alternatives and word limit; essay with
 * minimum words, data table, chart and outline; recording with questions and
 * timings. The code is locked once the question has mentee answers.
 */
class TryoutQuestionForm
{
    /**
     * Add the form components to the schema.
     *
     * @param  Schema  $schema  The schema to configure.
     * @param  bool  $in_unit  Rendered inside a unit (skill and unit come from the owner).
     * @return Schema The configured schema.
     */
    public static function configure(Schema $schema, bool $in_unit = false): Schema
    {
        return $schema->components(self::components($in_unit))->columns(2);
    }

    /**
     * The form components.
     *
     * @return array<int, mixed>
     */
    public static function components(bool $in_unit = false): array
    {
        return [
            Section::make('Soal')
                ->schema([
                    TextInput::make('code')
                        ->label('Kode')
                        ->helperText('Contoh LB-U1-01, RB-U3-07, W-L3-T2. Terkunci setelah soal dijawab mentee.')
                        ->required()
                        ->maxLength(32)
                        ->unique(TryoutQuestion::class, 'code', ignoreRecord: true)
                        ->disabled(fn (?TryoutQuestion $record): bool => $record?->hasAnswers() ?? false),
                    $in_unit
                        // Inside a unit the skill comes from the unit and is stored on create.
                        ? Hidden::make('skill')
                        : Select::make('skill')
                            ->label('Subtes')
                            ->options(TryoutSkill::options())
                            ->required()
                            ->live(),
                    Select::make('tryout_unit_id')
                        ->label('Unit (teks / audio)')
                        ->relationship('unit', 'title', fn (Builder $query, Get $get): Builder => $query->where('skill', self::skillValue($get)))
                        ->getOptionLabelFromRecordUsing(fn (TryoutUnit $record): string => $record->code.' · '.$record->title)
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => self::skill($get)?->isObjective() ?? false)
                        ->hidden($in_unit),
                    Select::make('question_type')
                        ->label('Tipe soal')
                        ->options(fn (Get $get): array => TryoutQuestionType::optionsFor(self::skill($get)))
                        ->default(TryoutQuestionType::SingleChoice->value)
                        ->required()
                        ->live(),
                    Select::make('level')->label('Level')->options(TryoutQuestion::levelOptions())->default(3)->required(),
                    Select::make('part')
                        ->label('Task / Part')
                        ->options([1 => '1', 2 => '2', 3 => '3'])
                        ->required(fn (Get $get): bool => ! (self::skill($get)?->isObjective() ?? true))
                        ->visible(fn (Get $get): bool => ! (self::skill($get)?->isObjective() ?? true)),
                    TextInput::make('number')->label('Nomor di dokumen sumber')->numeric(),
                    TextInput::make('source_type')->label('Label tipe (opsional)')->placeholder('TRUE / FALSE / NOT GIVEN')->maxLength(40),
                    Textarea::make('prompt')->label('Pertanyaan / prompt')->required()->rows(3)->columnSpanFull(),
                    Select::make('status')->label('Status')->options(ContentStatus::options())->default(ContentStatus::Draft->value)->required()
                        ->helperText('Soal yang sudah dijawab tidak bisa dihapus; jadikan draf (arsip).'),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Pilihan jawaban')
                ->visible(fn (Get $get): bool => self::type($get)?->hasOptions() ?? false)
                ->schema([
                    Repeater::make('options')
                        ->label('Pilihan (huruf otomatis A, B, C, ...)')
                        ->simple(TextInput::make('text')->label('Teks pilihan')->required()->maxLength(500))
                        ->defaultItems((int) config('tryout.options.recommended', 4))
                        ->minItems((int) config('tryout.options.min', 2))
                        ->maxItems(12)
                        ->reorderable()
                        ->live()
                        ->addActionLabel('Tambah pilihan')
                        // Lettered (A, B, C...) by TryoutQuestion::booted() on save.
                        ->helperText('Ideal A sampai D (4 pilihan). Boleh ditambah atau dikurangi, minimal 2.'),
                    Text::make('Jumlah pilihan bukan 4. Pastikan memang disengaja (ideal A sampai D).')
                        ->color('warning')
                        ->visible(fn (Get $get): bool => self::type($get) === TryoutQuestionType::SingleChoice && count(array_filter((array) $get('options'))) !== 4),
                    Select::make('answer_key')
                        ->label('Kunci jawaban')
                        ->options(fn (Get $get): array => self::letterOptions($get('options')))
                        ->required()
                        ->rule(fn (Get $get) => Rule::in(array_keys(self::letterOptions($get('options')))))
                        ->helperText('Dipilih dari pilihan yang sudah diisi.'),
                ])
                ->columnSpanFull(),

            Section::make('Jawaban teks isian')
                ->visible(fn (Get $get): bool => self::type($get) === TryoutQuestionType::ShortText)
                ->schema([
                    TextInput::make('answer_key')->label('Kunci jawaban')->required()->maxLength(255),
                    TagsInput::make('accepted_answers')->label('Jawaban alternatif')->placeholder('mis. 6, six')->helperText('Huruf besar/kecil, spasi, titik akhir, dan 2,600 = 2600 sudah otomatis disamakan.'),
                    TextInput::make('word_limit')->label('Batas kata')->numeric()->minValue(1)->maxValue(10),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Writing')
                ->visible(fn (Get $get): bool => self::type($get) === TryoutQuestionType::Essay)
                ->schema([
                    TextInput::make('min_words')->label('Minimal kata')->numeric()->default(150),
                    TextInput::make('rubric_key')->label('Rubrik')->default('writing_v1'),
                    Textarea::make('prompt_table')
                        ->label('Tabel data Task 1 (opsional)')
                        ->rows(5)
                        ->helperText('Satu baris per baris tabel, kolom dipisah tanda |. Baris pertama = judul kolom. Contoh: Season | Temp (°C) | Rainfall (mm)')
                        ->formatStateUsing(fn (mixed $state): string => self::tableToText($state))
                        ->dehydrateStateUsing(fn (mixed $state): ?array => self::textToTable($state))
                        ->columnSpanFull(),
                    FileUpload::make('prompt_image_path')->label('Grafik Task 1 (opsional)')->image()->disk(TryoutUnit::MEDIA_DISK)->directory(TryoutUnit::IMAGE_DIRECTORY)->visibility('public')->maxSize(4096)->columnSpanFull(),
                    TagsInput::make('answer_outline')->label('Kerangka jawaban (panduan penilai AI, tidak tampil ke mentee)')->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Speaking')
                ->visible(fn (Get $get): bool => self::type($get) === TryoutQuestionType::AudioRecording)
                ->schema([
                    Repeater::make('prompt_items')
                        ->label('Daftar pertanyaan / poin cue card')
                        ->simple(TextInput::make('item')->required()->maxLength(300))
                        ->defaultItems(2)
                        ->addActionLabel('Tambah pertanyaan')
                        ->columnSpanFull(),
                    TextInput::make('prep_seconds')->label('Waktu persiapan (detik)')->numeric()->helperText('Part 2: 60. Kosongkan untuk Part 1 dan 3.'),
                    TextInput::make('speak_seconds')->label('Waktu bicara maks (detik)')->numeric()->default(120),
                    TextInput::make('rubric_key')->label('Rubrik')->default('speaking_v1'),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Pembahasan (khusus Pro)')
                ->schema([
                    Textarea::make('explanation')->label('Pembahasan')->rows(4)->helperText('Bahasa Indonesia, pakai "kamu". Dibaca mentor AI dan hanya tampil untuk Pro.')->columnSpanFull(),
                    TagsInput::make('theory_tags')->label('Tag teori')->placeholder('distraktor-listening'),
                    TextInput::make('max_score')->label('Skor maks')->numeric()->default(1),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * Turn the repeater list into letter => text (A, B, C...).
     *
     * @return array<string, string>
     */
    public static function lettered(mixed $state): array
    {
        $options = [];

        $texts = array_map(self::optionText(...), array_values((array) $state));

        foreach (array_values(array_filter($texts, fn (string $text): bool => trim($text) !== '')) as $index => $text) {
            $options[TryoutQuestion::letter($index)] = trim($text);
        }

        return $options;
    }

    /**
     * Text of one repeater item (a plain string, or {text: ...} on a fresh form).
     */
    private static function optionText(mixed $item): string
    {
        if (is_array($item)) {
            $item = $item['text'] ?? reset($item);
        }

        return is_scalar($item) ? (string) $item : '';
    }

    /**
     * Letter => "A. text" options of the key select.
     *
     * @return array<string, string>
     */
    private static function letterOptions(mixed $state): array
    {
        $options = [];

        foreach (self::lettered($state) as $letter => $text) {
            $options[$letter] = $letter.'. '.mb_strimwidth($text, 0, 60, '...');
        }

        return $options;
    }

    /**
     * "a | b" lines to {columns, rows}.
     *
     * @return array{columns: array<int, string>, rows: array<int, array<int, string>>}|null
     */
    public static function textToTable(mixed $state): ?array
    {
        if (! is_string($state) || trim($state) === '') {
            return null;
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $state) ?: [])));
        $cells = array_map(fn (string $line): array => array_map('trim', explode('|', $line)), $lines);

        return ['columns' => array_shift($cells) ?? [], 'rows' => $cells];
    }

    /**
     * {columns, rows} back to "a | b" lines.
     */
    public static function tableToText(mixed $state): string
    {
        if (! is_array($state) || empty($state['columns'])) {
            return '';
        }

        $lines = [implode(' | ', (array) $state['columns'])];

        foreach ((array) ($state['rows'] ?? []) as $row) {
            $lines[] = implode(' | ', (array) $row);
        }

        return implode("\n", $lines);
    }

    /**
     * Current skill of the form (owner unit inside the relation manager).
     */
    private static function skill(Get $get): ?TryoutSkill
    {
        $value = self::skillValue($get);

        return $value === '' ? null : TryoutSkill::tryFrom($value);
    }

    /**
     * Raw skill value of the form.
     */
    private static function skillValue(Get $get): string
    {
        $skill = $get('skill');

        return $skill instanceof TryoutSkill ? $skill->value : (string) $skill;
    }

    /**
     * Current question type of the form.
     */
    private static function type(Get $get): ?TryoutQuestionType
    {
        $type = $get('question_type');

        return $type instanceof TryoutQuestionType ? $type : TryoutQuestionType::tryFrom((string) $type);
    }
}
