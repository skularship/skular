<?php

namespace App\Filament\Resources\IqQuestions\Pages;

use App\Contracts\IqBankImportServiceInterface;
use App\Filament\Resources\IqQuestions\IqQuestionResource;
use App\Models\IqTest;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * List page of the Bank Soal IQ with "Impor JSON" (dry run first) and
 * "Ekspor JSON", so the bank can be updated from a script without a deploy.
 */
class ListIqQuestions extends ListRecords
{
    protected static string $resource = IqQuestionResource::class;

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
                        ->maxSize(2048)
                        ->storeFiles(false)
                        ->required(),
                    Toggle::make('dry_run')
                        ->label('Mode coba (cek saja, tidak menyimpan)')
                        ->default(true),
                ])
                ->modalDescription('Format: {"tes": {"kode", "judul", "durasi_menit"}, "soal": [{"nomor", "subtes", "bacaan", "soal", "gambar", "alt_gambar", "pilihan": {"A","B","C","D"}, "kunci", "pembahasan", "aktif"}]}. Soal dicocokkan lewat kode tes + nomor. Gambar yang sudah diunggah tidak terhapus. Soal yang belum lengkap disimpan nonaktif.')
                ->action(function (array $data): void {
                    $file = $data['file'];
                    $raw = $file instanceof TemporaryUploadedFile ? (string) file_get_contents($file->getRealPath()) : '';
                    $payload = json_decode($raw, true);

                    if (! is_array($payload)) {
                        Notification::make()->title('File bukan JSON yang valid')->danger()->send();

                        return;
                    }

                    $report = app(IqBankImportServiceInterface::class)->import($payload, (bool) ($data['dry_run'] ?? true));
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
                ->schema([
                    Select::make('iq_test_id')
                        ->label('Tes')
                        ->options(fn (): array => IqTest::query()->orderByDesc('id')->pluck('title', 'id')->all())
                        ->required(),
                ])
                ->action(function (array $data): StreamedResponse {
                    $test = IqTest::query()->whereKey($data['iq_test_id'])->firstOrFail();
                    $payload = app(IqBankImportServiceInterface::class)->export($test);

                    return response()->streamDownload(function () use ($payload): void {
                        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }, 'bank-soal-iq-'.$test->code.'-'.now()->format('Ymd-His').'.json', ['Content-Type' => 'application/json']);
                }),
            CreateAction::make(),
        ];
    }
}
