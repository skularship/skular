<?php

namespace App\Filament\Resources\Modules\Schemas;

use App\Contracts\ModuleReadinessServiceInterface;
use App\Data\ModuleReadinessCheckData;
use App\Enums\BrainstormQuestionType;
use App\Enums\ModuleCategory;
use App\Enums\ModuleType;
use App\Filament\Schemas\ListRepeater;
use App\Models\Module;
use App\Services\BrainstormingConfigService;
use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\UnorderedList;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * The curriculum editor for a single module.
 *
 * Fields are grouped by the part of the mentee experience they feed, labels
 * use curriculum language instead of database language, and the completeness
 * checklist is computed from stored data so it can never claim something is
 * finished when it is not.
 */
class ModuleForm
{
    /**
     * Determine whether the selected module category is Template.
     *
     * The Select state may arrive as the enum instance or its raw value, so
     * both are accepted.
     */
    private static function isTemplate(Get $get): bool
    {
        $category = $get('category');

        if ($category instanceof ModuleCategory) {
            return $category === ModuleCategory::Template;
        }

        return $category === ModuleCategory::Template->value;
    }

    /**
     * Explain what the selected curriculum category means for mentees.
     *
     * The wording mirrors the business rules: Inti is filled once and reused by
     * every scholarship, Template belongs to one scholarship, and Perpustakaan
     * is optional training that never gates a path.
     */
    private static function categoryHint(Get $get): string
    {
        $category = $get('category');

        return match (true) {
            $category instanceof ModuleCategory => self::hintFor($category),
            $category === ModuleCategory::Template->value => self::hintFor(ModuleCategory::Template),
            $category === ModuleCategory::Perpustakaan->value => self::hintFor(ModuleCategory::Perpustakaan),
            default => self::hintFor(ModuleCategory::Inti),
        };
    }

    /**
     * Get the contextual sentence of a single category.
     */
    private static function hintFor(ModuleCategory $category): string
    {
        return match ($category) {
            ModuleCategory::Inti => 'Modul Inti bersifat umum: dipakai bersama oleh semua jalur beasiswa. Mentee hanya menyelesaikannya sekali dan progresnya tetap selesai saat berpindah beasiswa.',
            ModuleCategory::Template => 'Modul Template disiapkan untuk satu beasiswa. Progresnya tidak berpindah ke beasiswa lain, dan modul ini tidak tampil pada jalur beasiswa yang tidak dipilih mentee.',
            ModuleCategory::Perpustakaan => 'Modul Perpustakaan adalah materi pendukung lintas beasiswa. Modul ini tidak otomatis menjadi syarat penyelesaian jalur beasiswa mana pun.',
        };
    }

    /**
     * Determine whether the selected brainstorming input type needs options.
     */
    private static function brainstormingTypeHasOptions(Get $get): bool
    {
        $type = $get('type');

        if ($type instanceof BrainstormQuestionType) {
            return $type->hasOptions();
        }

        return BrainstormQuestionType::tryFrom((string) $type)?->hasOptions() ?? false;
    }

    /**
     * Show a short preview so curriculum editors can sanity-check a question.
     */
    private static function brainstormingPreview(Get $get): string
    {
        $question = trim((string) ($get('question') ?? ''));

        if ($question === '') {
            return 'Pratinjau: teks pertanyaan belum diisi.';
        }

        $options = $get('options');
        $count = is_array($options) ? count($options) : 0;

        return 'Pratinjau: '.$question.($count > 0 ? ' ('.$count.' pilihan jawaban)' : ' (tanpa pilihan jawaban)');
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('1. Informasi dasar')
                    ->description('Identitas modul dan posisinya pada jalur belajar mentee.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul modul')
                            ->placeholder('Contoh: Menyusun Esai Motivasi yang Kuat')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('fase_id')
                            ->label('Fase belajar')
                            ->relationship('fase', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Fase mengelompokkan modul. Urutan fase menentukan urutan jalur belajar.'),
                        TextInput::make('modul_order')
                            ->label('Urutan di dalam fase')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Angka lebih kecil tampil lebih dulu.'),
                        Select::make('category')
                            ->label('Tipe modul')
                            ->options(ModuleCategory::class)
                            ->default(ModuleCategory::Inti->value)
                            ->required()
                            ->live()
                            ->helperText('Inti: untuk semua mentee. Template: hanya untuk beasiswa tertentu. Perpustakaan: pelatihan tambahan lintas beasiswa.'),
                        Select::make('scholarship_id')
                            ->label('Beasiswa terkait')
                            ->relationship('scholarship', 'scholarship_name')
                            ->searchable()
                            ->preload()
                            ->required(self::isTemplate(...))
                            ->visible(self::isTemplate(...))
                            ->helperText('Hanya modul Template yang terhubung ke satu beasiswa. Progres modul ini disimpan terpisah untuk setiap beasiswa.'),
                        Text::make(fn (Get $get): string => self::categoryHint($get))
                            ->color('gray')
                            ->columnSpanFull(),
                        Select::make('type')
                            ->label('Format penyajian')
                            ->options(ModuleType::class)
                            ->default(ModuleType::Reading->value)
                            ->required()
                            ->helperText('Menandai bentuk utama modul untuk mentee dan admin lain.'),
                        TextInput::make('duration')
                            ->label('Estimasi durasi (menit)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Tampil pada bagian pembukaan modul.'),
                        Textarea::make('goal')
                            ->label('Tujuan pembelajaran')
                            ->placeholder('Contoh: Mentee mampu menulis paragraf pembuka esai yang spesifik dan terukur.')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Ditampilkan pada pembukaan modul agar mentee tahu hasil yang diharapkan.'),
                    ])
                    ->columns(2),

                Section::make('2. Kata pengantar')
                    ->description('Orientasi singkat sebelum mentee mengerjakan quick question dan latihan praktik. Kosongkan bila modul tidak memerlukannya.')
                    ->schema([
                        Textarea::make('introduction')
                            ->label('Kata pengantar modul')
                            ->placeholder('Contoh: Sebelum berlatih, pahami dulu apa yang dinilai penyelenggara beasiswa pada bagian ini.')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Tampil dua kali di sisi mentee: sebelum Quick Question dan sebelum latihan praktik.'),
                    ]),

                Section::make('3. Materi langkah Pahami')
                    ->description('Bahan bacaan yang dibuka mentee pada langkah Pahami. Isi minimal salah satu bagian.')
                    ->schema([
                        Textarea::make('summary_material')
                            ->label('Ringkasan materi')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Ikhtisar singkat yang dibaca mentee paling awal.'),
                        ListRepeater::make(
                            name: 'insights',
                            addItemLabel: 'Tambah poin penting',
                            placeholder: 'Contoh: Tujuan belajar harus spesifik dan dapat diukur.',
                            label: 'Poin penting',
                            helperText: 'Ditampilkan sebagai kartu berurutan pada langkah Pahami. Susun ulang dengan menarik barisnya.',
                            maxLength: 255,
                        ),
                        Textarea::make('inti_material')
                            ->label('Inti materi')
                            ->rows(6)
                            ->columnSpanFull()
                            ->helperText('Pembahasan utama yang dibaca mentee setelah ringkasan dan poin penting.'),
                        Toggle::make('has_video')
                            ->label('Modul ini punya video')
                            ->dehydrated(false)
                            ->live()
                            ->default(fn (?Module $record): bool => filled($record?->link_video))
                            ->helperText('Aktifkan untuk menampilkan pemutar video di langkah Pahami.'),
                        TextInput::make('link_video')
                            ->label('Link embed video')
                            ->placeholder('https://www.youtube.com/embed/xxxxxxx')
                            ->url()
                            ->visible(fn (Get $get): bool => (bool) $get('has_video'))
                            ->helperText('Gunakan tautan embed, bukan tautan halaman tonton.'),
                    ])
                    ->columns(2),

                Section::make('4. Pertanyaan pemancing (brainstorming)')
                    ->description('Pertanyaan ini membantu mentee menemukan ide sebelum mengisi latihan praktik. Pertanyaan dan pilihan jawaban tampil apa adanya, tanpa memanggil AI; AI hanya menyusun kerangka draf setelah mentee menjawab.')
                    ->schema([
                        Repeater::make('brainstorming_questions')
                            ->label('Pertanyaan')
                            ->schema([
                                Hidden::make('id')
                                    ->default(fn (): string => 'q_'.Str::lower((string) Str::ulid())),
                                TextInput::make('question')
                                    ->label('Teks pertanyaan')
                                    ->placeholder('Contoh: Pengalaman apa yang paling ingin kamu ceritakan?')
                                    ->required()
                                    ->maxLength(BrainstormingConfigService::MAX_QUESTION_LENGTH)
                                    ->columnSpanFull(),
                                Select::make('type')
                                    ->label('Tipe jawaban')
                                    ->options(BrainstormQuestionType::class)
                                    ->default(BrainstormQuestionType::SingleChoice->value)
                                    ->required()
                                    ->live()
                                    ->helperText('Pilihan tunggal: satu jawaban. Pilihan ganda: beberapa jawaban. Teks singkat: mentee menulis sendiri.'),
                                Toggle::make('required')
                                    ->label('Wajib dijawab')
                                    ->default(true)
                                    ->helperText('Mentee tidak bisa meminta kerangka sebelum pertanyaan wajib terjawab.'),
                                Toggle::make('active')
                                    ->label('Tampilkan ke mentee')
                                    ->default(true)
                                    ->helperText('Matikan untuk menyimpan pertanyaan sebagai draf tanpa menampilkannya.'),
                                Textarea::make('hint')
                                    ->label('Petunjuk singkat (opsional)')
                                    ->rows(2)
                                    ->maxLength(BrainstormingConfigService::MAX_HINT_LENGTH)
                                    ->helperText('Muncul di bawah pertanyaan untuk membantu mentee memahami maksudnya.')
                                    ->columnSpanFull(),
                                Repeater::make('options')
                                    ->label('Pilihan jawaban')
                                    ->schema([
                                        Hidden::make('id')
                                            ->default(fn (): string => 'o_'.Str::lower((string) Str::ulid())),
                                        TextInput::make('label')
                                            ->label('Teks pilihan')
                                            ->placeholder('Contoh: Pengalaman organisasi')
                                            ->required()
                                            ->maxLength(BrainstormingConfigService::MAX_OPTION_LENGTH),
                                        Toggle::make('active')
                                            ->label('Tampilkan pilihan ini')
                                            ->default(true),
                                    ])
                                    ->itemLabel(fn (array $state): string => filled($state['label'] ?? null)
                                        ? Str::limit((string) $state['label'], 60)
                                        : 'Pilihan baru')
                                    ->addActionLabel('Tambah pilihan jawaban')
                                    ->reorderable()
                                    ->defaultItems(2)
                                    ->maxItems(BrainstormingConfigService::MAX_OPTIONS)
                                    ->visible(self::brainstormingTypeHasOptions(...))
                                    ->required(fn (Get $get): bool => self::brainstormingTypeHasOptions($get) && (bool) $get('active'))
                                    ->helperText('Tarik barisnya untuk mengubah urutan pilihan. Hanya dipakai pada tipe pilihan.')
                                    ->columnSpanFull(),
                                Text::make(fn (Get $get): string => self::brainstormingPreview($get))
                                    ->color('gray')
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(fn (array $state): string => filled($state['question'] ?? null)
                                ? Str::limit((string) $state['question'], 70)
                                : 'Pertanyaan baru')
                            ->addActionLabel('Tambah pertanyaan pemancing')
                            ->reorderable()
                            ->defaultItems(0)
                            ->maxItems(BrainstormingConfigService::MAX_QUESTIONS)
                            ->rules([
                                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                    foreach (app(BrainstormingConfigService::class)->validationMessages($value) as $message) {
                                        $fail($message);
                                    }
                                },
                            ])
                            ->helperText('Pertanyaan tampil berurutan. Mentee menjawab semuanya sebelum meminta AI menyusun kerangka draf.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Callout::make('Modul baru disimpan sebagai draf')
                    ->description('Mentee belum bisa membuka modul ini. Lengkapi materi dan latihan, lihat pratinjau, lalu terbitkan dari tombol di halaman ini.')
                    ->info()
                    ->visibleOn('create'),

                Section::make('Kelengkapan modul')
                    ->description('Dihitung otomatis dari data modul ini dan dari latihan serta quick question yang terhubung. Perbaiki item merah sebelum menerbitkan.')
                    ->visibleOn('edit')
                    ->schema([
                        Text::make(fn (?Module $record): string => $record instanceof Module
                            ? 'Kelengkapan saat ini: '.app(ModuleReadinessServiceInterface::class)->progress($record).'%'
                            : '')
                            ->weight('bold'),
                        UnorderedList::make(fn (?Module $record): array => self::readinessItems($record)),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Konteks untuk AI')
                    ->description('Rubrik dan basis pengetahuan yang dipakai AI Mentor, Brainstorming, dan penilaian latihan. Pengaturan teknis AI — penyedia, model, dan API key — tidak diubah dari halaman ini.')
                    ->schema([
                        Textarea::make('ai_knowledge')
                            ->label('Rubrik dan catatan penilaian')
                            ->rows(6)
                            ->columnSpanFull()
                            ->helperText('Tuliskan kriteria penilaian yang harus dipakai AI saat menanggapi jawaban mentee pada modul ini.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Build the checklist rows from the module's real stored content.
     *
     * @return array<int, Text>
     */
    private static function readinessItems(?Module $record): array
    {
        if (! $record instanceof Module) {
            return [
                Text::make('Simpan modul terlebih dahulu untuk melihat daftar kelengkapan.'),
            ];
        }

        return array_map(
            fn (ModuleReadinessCheckData $check): Text => Text::make(
                $check->passed
                    ? $check->label
                    : $check->label.' — '.$check->hint,
            )
                ->icon(match (true) {
                    $check->passed => 'heroicon-m-check-circle',
                    $check->required => 'heroicon-m-x-circle',
                    default => 'heroicon-m-exclamation-triangle',
                })
                ->color(match (true) {
                    $check->passed => 'success',
                    $check->required => 'danger',
                    default => 'warning',
                }),
            app(ModuleReadinessServiceInterface::class)->checks($record),
        );
    }
}
