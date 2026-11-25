<?php

namespace App\Filament\Resources\Tryouts\Schemas;

use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use App\Models\Module;
use App\Models\Tryout;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Create/edit form of the Tryout resource. The status is read only here:
 * publishing goes through the "Terbitkan" action so the pool is checked first.
 * The theory PDF is stored on the private local disk (only Pro can download it).
 */
class TryoutForm
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
                Section::make('Paket Try Out')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul untuk mentee')
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
                            ->helperText('Dipakai di URL /tryout/{slug}.')
                            ->required()
                            ->alphaDash()
                            ->maxLength(150)
                            ->unique(Tryout::class, 'slug', ignoreRecord: true),
                        TextInput::make('code')
                            ->label('Kode')
                            ->helperText('Contoh TO1-AC. Tidak bisa diubah setelah ada mentee yang mengerjakan.')
                            ->required()
                            ->maxLength(32)
                            ->unique(Tryout::class, 'code', ignoreRecord: true)
                            ->disabled(fn (?Tryout $record): bool => $record?->hasMenteeHistory() ?? false),
                        Select::make('exam_type')
                            ->label('Jenis ujian')
                            ->options(TryoutExamType::enabledOptions())
                            ->default(TryoutExamType::Ielts->value)
                            ->required(),
                        TextInput::make('variant')
                            ->label('Varian')
                            ->default('academic')
                            ->maxLength(16),
                        Select::make('status')
                            ->label('Status')
                            ->options(ContentStatus::options())
                            ->default(ContentStatus::Draft->value)
                            ->disabled()
                            ->dehydrated(fn (?Tryout $record): bool => $record === null)
                            ->helperText('Pakai tombol Terbitkan / Jadikan draf di halaman edit. Pool soal dicek dulu sebelum terbit.'),
                        Toggle::make('is_trial')->label('Versi uji')->helperText('Menampilkan label "Versi uji" ke mentee.'),
                        Toggle::make('requires_pro')->label('Khusus Pro')->helperText('Hanya mentee Pro yang bisa mulai.'),
                        TextInput::make('ordering')->label('Urutan tampil')->numeric()->default(0),
                        MarkdownEditor::make('description')->label('Deskripsi (halaman intro)')->helperText('Markdown. Pakai sapaan "kamu".')->columnSpanFull(),
                        MarkdownEditor::make('instructions')->label('Aturan umum')->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Teori IELTS')
                    ->description('Tiga lapis teori: modul teori dibaca di website (Pro), PDF teori dibaca di viewer website tanpa bisa diunduh (Pro), dan link ke PDF materi berbayar (semua mentee).')
                    ->schema([
                        Select::make('theory_module_id')
                            ->label('Modul teori')
                            ->options(fn (): array => Module::query()->where('is_tryout_theory', true)->orderBy('title')->pluck('title', 'id')->all())
                            ->searchable()
                            ->helperText('Hanya modul dengan centang "Modul teori Try Out". Isinya juga dibaca mentor AI.')
                            ->columnSpanFull(),
                        FileUpload::make('theory_pdf_path')
                            ->label('PDF teori Try Out')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize((int) config('tryout.theory_pdf.max_kb', 20480))
                            ->disk((string) config('tryout.theory_pdf.disk', 'local'))
                            ->directory((string) config('tryout.theory_pdf.directory', 'tryout-theory'))
                            ->visibility('private')
                            ->helperText('Maks 20 MB, disimpan privat. Mentee Pro hanya bisa membacanya di website (tanpa unduh, tanpa cetak, dengan watermark nama dan email). File lama dihapus saat diganti.')
                            ->columnSpanFull(),
                        TextInput::make('theory_pdf_name')
                            ->label('Judul PDF di viewer')
                            ->placeholder('Teori IELTS Skular')
                            ->maxLength(120),
                        TextInput::make('materials_label')
                            ->label('Teks tombol materi berbayar')
                            ->placeholder(Tryout::DEFAULT_MATERIALS_LABEL)
                            ->maxLength(120),
                        TextInput::make('materials_url')
                            ->label('Link jual PDF materi lengkap')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
