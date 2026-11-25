<?php

namespace App\Filament\Resources\TryoutQuestions\Pages;

use App\Contracts\TryoutBankImportServiceInterface;
use App\Filament\Resources\TryoutQuestions\TryoutQuestionResource;
use App\Models\TryoutQuestion;
use App\Models\TryoutUnit;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * List page of the Bank Soal with "Impor JSON" (dry run first) and "Ekspor JSON".
 */
class ListTryoutQuestions extends ListRecords
{
    protected static string $resource = TryoutQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importJson')
                ->label('Impor JSON')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->authorize('create')
                ->schema([
                    FileUpload::make('file')
                        ->label('File bank soal (.json)')
                        ->acceptedFileTypes(['application/json', 'text/plain'])
                        ->maxSize(5120)
                        ->storeFiles(false)
                        ->required(),
                    Toggle::make('dry_run')
                        ->label('Mode coba (cek saja, tidak menyimpan)')
                        ->default(true),
                ])
                ->modalDescription('Format: {"units": [...], "questions": [...]}, nama kolom mengikuti spesifikasi bank soal (id, subtes, tipe_ui, pertanyaan, pilihan, kunci, ...). Soal yang sudah dijawab mentee tidak diubah.')
                ->action(function (array $data): void {
                    $file = $data['file'];
                    $raw = $file instanceof TemporaryUploadedFile ? (string) file_get_contents($file->getRealPath()) : '';
                    $payload = json_decode($raw, true);

                    if (! is_array($payload)) {
                        Notification::make()->title('File bukan JSON yang valid')->danger()->send();

                        return;
                    }

                    $report = app(TryoutBankImportServiceInterface::class)->import($payload, (bool) ($data['dry_run'] ?? true));
                    $errors = array_slice($report->errors, 0, 15);

                    Notification::make()
                        ->title($report->summary())
                        ->body($errors === [] ? null : new HtmlString('<ul style="list-style: disc; margin-inline-start: 1rem;">'.collect($errors)->map(fn (string $line): string => '<li>'.e($line).'</li>')->implode('').'</ul>'))
                        ->color($report->errors === [] ? 'success' : 'warning')
                        ->persistent()
                        ->send();
                }),
            Action::make('exportJson')
                ->label('Ekspor JSON')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->authorize('viewAny')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    echo json_encode([
                        'units' => TryoutUnit::query()->orderBy('code')->get()->map(fn (TryoutUnit $unit): array => [
                            'code' => $unit->code, 'skill' => $unit->skill->value, 'title' => $unit->title, 'context' => $unit->context,
                            'passage' => $unit->passage, 'transcript' => $unit->transcript, 'attachment' => $unit->attachment, 'status' => $unit->status->value,
                        ])->all(),
                        'questions' => TryoutQuestion::query()->with('unit:id,code')->orderBy('code')->get()->map(fn (TryoutQuestion $question): array => [
                            'id' => $question->code, 'subtes' => $question->skill->value, 'unit' => $question->unit?->code, 'nomor' => $question->number,
                            'part' => $question->part, 'tipe_ui' => $question->question_type->value, 'level' => $question->level,
                            'pertanyaan' => $question->prompt, 'pilihan' => $question->options, 'kunci' => $question->answer_key,
                            'jawaban_alternatif' => $question->accepted_answers, 'batas_kata' => $question->word_limit, 'minimal_kata' => $question->min_words,
                            'daftar_pertanyaan' => $question->prompt_items, 'tabel' => $question->prompt_table, 'waktu_persiapan' => $question->prep_seconds,
                            'waktu_bicara' => $question->speak_seconds, 'skor' => $question->max_score, 'rubrik' => $question->rubric_key,
                            'kerangka' => $question->answer_outline, 'pembahasan' => $question->explanation, 'tag_teori' => $question->theory_tags,
                            'status' => $question->status->value,
                        ])->all(),
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }, 'bank-soal-tryout-'.now()->format('Ymd-His').'.json', ['Content-Type' => 'application/json'])),
            CreateAction::make(),
        ];
    }
}
