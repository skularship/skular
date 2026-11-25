<?php

namespace App\Livewire\Tryout\Sections;

use App\Contracts\TryoutAnswerServiceInterface;
use App\Contracts\TryoutAttemptServiceInterface;
use App\Exceptions\TryoutAnswerRejectedException;
use App\Exceptions\TryoutSectionExpiredException;
use App\Livewire\Tryout\Concerns\InteractsWithAttemptSection;
use App\Models\TryoutAnswer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Writing subtest (child of the Runner): Task 1 / Task 2 tabs, the prompt
 * (with its data table) beside the editor, a live word counter and autosave
 * every few seconds and on every tab switch (US5). The mentee may move
 * freely between tasks (D9). The AI grades each task after the subtest is
 * sent, one task per request, on the result page.
 */
class WritingSection extends Component
{
    use InteractsWithAttemptSection;

    #[Locked]
    public int $attempt_section_id;

    /**
     * The tasks as TryoutQuestionViewData::toArray(), in task order.
     *
     * @var array<int, array<string, mixed>>
     */
    #[Locked]
    public array $prompts = [];

    /**
     * Essay text per task index.
     *
     * @var array<int, string>
     */
    public array $texts = [];

    /**
     * Index of the open task.
     */
    public int $active = 0;

    /**
     * Time (WIB, H.i) of the last successful save.
     */
    public ?string $saved_at = null;

    /**
     * Load the tasks and the essays saved so far.
     */
    public function mount(int $attempt_section_id, TryoutAnswerServiceInterface $answers): void
    {
        $this->attempt_section_id = $attempt_section_id;
        $section = $this->attemptSection();

        $this->prompts = array_map(fn ($prompt): array => $prompt->toArray(), $answers->productivePrompts($section));

        $saved = $section->answers()->with('question:id,code')->get()->keyBy(fn (TryoutAnswer $row): string => $row->question->code);

        foreach ($this->prompts as $index => $prompt) {
            $this->texts[$index] = (string) ($saved[$prompt['code']]->response ?? '');
        }
    }

    /**
     * Autosave the task that changed (wire:model.live.debounce).
     */
    public function updatedTexts(mixed $value, int|string $key): void
    {
        $this->save((int) $key);
    }

    /**
     * Open another task, saving the open one first.
     */
    public function switchTask(int $index): void
    {
        if (! isset($this->prompts[$index])) {
            return;
        }

        $this->save($this->active);
        $this->active = $index;
    }

    /**
     * Save every task and send the Writing subtest.
     */
    public function submitWriting(TryoutAttemptServiceInterface $attempts): void
    {
        foreach (array_keys($this->prompts) as $index) {
            if (! $this->save($index)) {
                return;
            }
        }

        $attempts->submitSection($this->attemptSection());
        $this->dispatch('section-closed');
    }

    /**
     * Render the subtest.
     *
     * @return View The livewire.tryout.sections.writing view.
     */
    public function render(): View
    {
        $section = $this->attemptSection();

        return view('livewire.tryout.sections.writing', [
            'section' => $section,
            ...$this->headerData($section),
        ]);
    }

    /**
     * Store one task; a closed or expired subtest closes the runner view.
     *
     * @return bool False when the subtest no longer accepts the essay.
     */
    private function save(int $index): bool
    {
        $prompt = $this->prompts[$index] ?? null;

        if ($prompt === null) {
            return true;
        }

        try {
            app(TryoutAnswerServiceInterface::class)->saveEssay($this->attemptSection(), (string) $prompt['code'], (string) ($this->texts[$index] ?? ''));
        } catch (TryoutSectionExpiredException|TryoutAnswerRejectedException $exception) {
            $this->dispatch('toast', variant: 'info', text: $exception->getMessage());
            $this->dispatch('section-closed');

            return false;
        }

        $this->saved_at = now()->setTimezone('Asia/Jakarta')->format('H.i');

        return true;
    }
}
