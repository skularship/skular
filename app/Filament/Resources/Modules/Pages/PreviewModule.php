<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Contracts\ModuleOutlineServiceInterface;
use App\Contracts\ModuleReadinessServiceInterface;
use App\Data\ModuleReadinessCheckData;
use App\Data\PracticeFieldData;
use App\Enums\PracticeFieldType;
use App\Filament\Actions\PublishModuleAction;
use App\Filament\Actions\UnpublishModuleAction;
use App\Filament\Resources\Modules\ModuleResource;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\QuickQuestion;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\UnorderedList;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * Shows the curriculum team exactly what mentees will see, in the order the
 * learning loop runs, before the module is published.
 *
 * The page is read-only on purpose: opening a preview never starts a
 * checkpoint, never submits an answer and never touches anybody's progress.
 */
class PreviewModule extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ModuleResource::class;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Pratinjau: '.$this->module()->title;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Tampilan mentee untuk modul ini. Membuka halaman ini tidak mengubah progres, jawaban latihan, atau checkpoint siapa pun.';
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToEditor')
                ->label('Kembali ke editor')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn (): string => ModuleResource::getUrl('edit', ['record' => $this->module()])),
            PublishModuleAction::make(),
            UnpublishModuleAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $module = $this->module();

        return $schema
            ->components([
                Callout::make('Pratinjau pengalaman mentee')
                    ->description('Urutan langkah, materi, dan pertanyaan di bawah ini dibaca langsung dari data modul dan dari latihan yang terhubung.')
                    ->info(),

                ...$this->readinessSummary($module),
                ...$this->stepSections($module),
            ]);
    }

    /**
     * Warn the reviewer about anything that still blocks publication.
     *
     * @return array<int, Callout>
     */
    private function readinessSummary(Module $module): array
    {
        $blockers = app(ModuleReadinessServiceInterface::class)->blockers($module);

        if ($blockers !== []) {
            return [
                Callout::make('Belum bisa diterbitkan')
                    ->description(new HtmlString(
                        '<ul style="list-style: disc; margin-inline-start: 1rem;">'
                        .collect($blockers)
                            ->map(fn (ModuleReadinessCheckData $check): string => '<li><strong>'.e($check->label).'</strong> — '.e($check->hint).'</li>')
                            ->implode('')
                        .'</ul>',
                    ))
                    ->warning(),
            ];
        }

        if ($module->isDraft()) {
            return [
                Callout::make('Siap diterbitkan')
                    ->description('Semua syarat kelengkapan terpenuhi. Gunakan tombol Terbitkan di atas untuk menampilkan modul ini ke mentee.')
                    ->success(),
            ];
        }

        return [];
    }

    /**
     * Render every step of the learning loop in mentee order.
     *
     * @return array<int, Section>
     */
    private function stepSections(Module $module): array
    {
        $sections = [];
        $number = 0;

        foreach (app(ModuleOutlineServiceInterface::class)->steps($module) as $step) {
            $number++;

            $sections[] = Section::make($number.'. '.$step->label)
                ->description($step->purpose)
                ->schema($this->stepSchema($module, $step->key))
                ->columns(1);
        }

        return $sections;
    }

    /**
     * Build the preview body for one learning step.
     *
     * @return array<int, mixed>
     */
    private function stepSchema(Module $module, string $key): array
    {
        return match ($key) {
            'opening' => $this->openingSchema($module),
            'pahami' => $this->materialSchema($module),
            'quick' => $this->quickQuestionSchema($module),
            'praktik' => $this->practiceSchema($module),
            default => [
                Text::make('Mentee menekan tombol konfirmasi setelah semua latihan praktik dikirim. Progres modul tersimpan dan modul berikutnya terbuka.'),
            ],
        };
    }

    /**
     * @return array<int, mixed>
     */
    private function openingSchema(Module $module): array
    {
        $forms = $this->activeForms($module);

        return [
            Text::make($module->goal !== null && $module->goal !== ''
                ? 'Tujuan modul: '.$module->goal
                : 'Tujuan modul belum diisi.')
                ->icon('heroicon-m-flag')
                ->color(filled($module->goal) ? 'success' : 'warning'),
            Text::make($module->duration !== null
                ? 'Estimasi waktu: '.$module->duration.' menit'
                : 'Estimasi waktu belum diisi.')
                ->icon('heroicon-m-clock')
                ->color($module->duration !== null ? 'gray' : 'warning'),
            Text::make($forms->isEmpty()
                ? 'Belum ada latihan tertulis pada modul ini.'
                : 'Output yang harus dibuat: '.$forms->pluck('title')->implode(', '))
                ->icon('heroicon-m-clipboard-document-check')
                ->color('gray'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function materialSchema(Module $module): array
    {
        $schema = [];

        if (filled($module->link_video)) {
            $schema[] = Text::make(new HtmlString(
                'Video materi: <a href="'.e((string) $module->link_video).'" target="_blank" rel="noopener noreferrer">'.e((string) $module->link_video).'</a>',
            ))->icon('heroicon-m-play-circle');
        }

        if (filled($module->summary_material)) {
            $schema[] = Text::make('Ringkasan materi')->weight('bold')->size('sm');
            $schema[] = $this->paragraph($module->summary_material);
        }

        $insights = array_values(array_filter($module->insights, filled(...)));

        if ($insights !== []) {
            $schema[] = Text::make('Poin penting')->weight('bold')->size('sm');
            $schema[] = UnorderedList::make(array_map(
                fn (string $insight): Text => Text::make($insight)->icon('heroicon-m-light-bulb')
                    ->color('warning'),
                $insights,
            ));
        }

        if (filled($module->inti_material)) {
            $schema[] = Text::make('Inti materi')->weight('bold')->size('sm');
            $schema[] = $this->paragraph($module->inti_material);
        }

        if ($schema === []) {
            $schema[] = Text::make('Materi Pahami masih kosong, mentee akan melihat pesan bahwa materi belum tersedia.')
                ->icon('heroicon-m-exclamation-triangle')
                ->color('warning');
        }

        return $schema;
    }

    /**
     * @return array<int, mixed>
     */
    private function quickQuestionSchema(Module $module): array
    {
        $schema = [
            Text::make('Mentee menjawab setiap pernyataan dengan Benar atau Salah, lalu jawabannya langsung dikoreksi.')
                ->icon('heroicon-m-information-circle')
                ->color('gray'),
            Text::make('Kata pengantar yang dibaca sebelum kuis:')->weight('bold')->size('sm'),
            $this->paragraph($module->introduction ?? ''),
            Text::make('Pertanyaan:')->weight('bold')->size('sm'),
        ];

        foreach ($this->activeQuestions($module) as $question) {
            $schema[] = Text::make($question->question)
                ->icon('heroicon-m-question-mark-circle')
                ->weight('semibold');

            $explanation = filled($question->explanation) ? ' — '.$question->explanation : '';

            $schema[] = Text::make('Kunci jawaban: '.($question->answer ? 'Benar' : 'Salah').$explanation)
                ->badge()
                ->color('info');
        }

        return $schema;
    }

    /**
     * @return array<int, mixed>
     */
    private function practiceSchema(Module $module): array
    {
        $forms = $this->activeForms($module);

        if ($forms->isEmpty()) {
            return [
                Text::make('Belum ada latihan praktik. Mentee langsung diarahkan ke checkpoint.')
                    ->icon('heroicon-m-information-circle')
                    ->color('gray'),
            ];
        }

        $schema = [
            Text::make('Kata pengantar yang dibaca sebelum latihan:')->weight('bold')->size('sm'),
            $this->paragraph($module->introduction ?? ''),
        ];

        foreach ($forms as $form) {
            $schema[] = Section::make($form->title)
                ->description($form->description ?? '')
                ->schema($this->formSchema($form))
                ->columns(1);
        }

        return $schema;
    }

    /**
     * @return array<int, mixed>
     */
    private function formSchema(PracticeForm $form): array
    {
        $fields = PracticeFieldData::collectFromSchema($form->fields ?? []);

        if ($fields === []) {
            return [
                Text::make('Latihan ini belum punya pertanyaan, mentee akan melihat pesan bahwa latihan belum tersedia.')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }

        $schema = [];

        foreach ($fields as $field) {
            $schema[] = Text::make($field->label)->weight('semibold');

            $details = [
                'Jenis: '.$field->type->getLabel(),
                $field->required ? 'Wajib diisi' : 'Opsional',
            ];

            if ($field->options !== []) {
                $details[] = 'Pilihan: '.implode(', ', $field->options);
            }

            if (filled($field->help_text)) {
                $details[] = 'Petunjuk: '.$field->help_text;
            }

            if (filled($field->placeholder)) {
                $details[] = 'Contoh: '.$field->placeholder;
            }

            $schema[] = Text::make(implode(' · ', $details))
                ->icon($field->type === PracticeFieldType::File ? 'heroicon-m-paper-clip' : 'heroicon-m-pencil-square')
                ->color('gray')
                ->size('sm');
        }

        return $schema;
    }

    /**
     * Render stored long text the way the mentee view renders it.
     */
    private function paragraph(?string $text): Text
    {
        if (! filled($text)) {
            return Text::make('Kata pengantar belum diisi — bagian ini tidak tampil untuk mentee.')
                ->icon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->size('sm');
        }

        return Text::make(new HtmlString(nl2br(e($text))));
    }

    /**
     * @return Collection<int, PracticeForm>
     */
    private function activeForms(Module $module): Collection
    {
        return $module->practiceForms()
            ->where('is_active', true)
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, QuickQuestion>
     */
    private function activeQuestions(Module $module): Collection
    {
        return $module->quickQuestions()
            ->where('is_active', true)
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get the module being previewed.
     */
    private function module(): Module
    {
        $record = $this->getRecord();

        if (! $record instanceof Module) {
            abort(404);
        }

        return $record;
    }

    /**
     * Only users allowed to view the module may open its preview.
     */
    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
}
