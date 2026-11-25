<?php

namespace App\Livewire\Tryout\Sections;

use App\Contracts\TryoutAnswerServiceInterface;
use App\Enums\TryoutQuestionType;
use App\Enums\TryoutSkill;
use App\Exceptions\TryoutAnswerRejectedException;
use App\Exceptions\TryoutSectionExpiredException;
use App\Livewire\Tryout\Concerns\InteractsWithAttemptSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Listening and Reading subtest (child of the Runner). One question at a
 * time; an answer is locked once sent because the next question depends
 * on it. Listening shows the audio card of the active block (plays counted
 * by the server), Reading the passage in a left panel (tabs on phones).
 * The component only ever holds TryoutQuestionViewData, never the key.
 */
class ObjectiveSection extends Component
{
    use InteractsWithAttemptSection;

    /**
     * Answers per minute a mentee may send (PRD 3.10: throttle 60,1).
     */
    private const ANSWERS_PER_MINUTE = 60;

    #[Locked]
    public int $attempt_section_id;

    /**
     * The active question as TryoutQuestionViewData::toArray(), or null.
     *
     * @var array<string, mixed>|null
     */
    #[Locked]
    public ?array $question = null;

    /**
     * Picked letter or typed text.
     */
    public string $answer = '';

    /**
     * Number of answered questions.
     */
    #[Locked]
    public int $answered = 0;

    /**
     * Listening: show the "Bagian X dari Y" card before a new unit.
     */
    public bool $show_block_intro = false;

    /**
     * Load the active (or resumed) question.
     */
    public function mount(int $attempt_section_id, TryoutAnswerServiceInterface $answers): void
    {
        $this->attempt_section_id = $attempt_section_id;
        $section = $this->attemptSection();

        $this->answered = $section->answers()->whereNotNull('answered_at')->count();
        $this->loadQuestion($answers);
        $this->show_block_intro = $section->skill === TryoutSkill::Listening && $this->question !== null && $this->isFirstOfBlock();
    }

    /**
     * Send the answer of the active question and show the next one.
     */
    public function submit(TryoutAnswerServiceInterface $answers): void
    {
        if ($this->question === null) {
            return;
        }

        $response = trim($this->answer);

        if ($response === '') {
            $this->addError('answer', __('Pilih atau tulis jawabanmu dulu ya.'));

            return;
        }

        if ($this->tooManyAttempts('answer', self::ANSWERS_PER_MINUTE)) {
            $this->addError('answer', __('Terlalu cepat. Tunggu sebentar lalu kirim lagi.'));

            return;
        }

        try {
            $result = $answers->answerObjective($this->attemptSection(), (string) $this->question['code'], $response);
        } catch (TryoutSectionExpiredException $exception) {
            $this->dispatch('toast', variant: 'info', text: $exception->getMessage());
            $this->dispatch('section-closed');

            return;
        } catch (TryoutAnswerRejectedException $exception) {
            $this->dispatch('toast', variant: 'warning', text: $exception->getMessage());
            $this->answer = '';
            $this->loadQuestion($answers);

            return;
        }

        $this->answer = '';
        $this->answered = $result->answered_count;
        $this->resetErrorBag();

        if ($result->section_finished) {
            $this->question = null;
            $this->dispatch('section-closed');

            return;
        }

        $this->question = $result->next_question?->toArray();
        $this->show_block_intro = $result->new_block && $this->attemptSection()->skill === TryoutSkill::Listening;
        $this->dispatch('tryout-question-changed', new_block: $result->new_block);
    }

    /**
     * Close the "Bagian X dari Y" card.
     */
    public function dismissBlockIntro(): void
    {
        $this->show_block_intro = false;
    }

    /**
     * Ask the server whether the audio of the active unit may play once more.
     *
     * @return bool True when the browser may play the audio.
     */
    public function playAudio(TryoutAnswerServiceInterface $answers): bool
    {
        $code = $this->question['unit']['code'] ?? null;

        if (! is_string($code)) {
            return false;
        }

        $allowed = $answers->registerAudioPlay($this->attemptSection(), $code);

        if ($allowed && is_array($this->question['unit'] ?? null)) {
            $this->question['unit']['plays_used'] = (int) ($this->question['unit']['plays_used'] ?? 0) + 1;
        }

        return $allowed;
    }

    /**
     * Render the subtest.
     *
     * @return View The livewire.tryout.sections.objective view.
     */
    public function render(): View
    {
        $section = $this->attemptSection();
        $type = TryoutQuestionType::tryFrom((string) ($this->question['type'] ?? ''));

        return view('livewire.tryout.sections.objective', [
            'section' => $section,
            'skill' => $section->skill,
            'type' => $type,
            'total' => $section->section->question_count,
            'mode' => $section->attempt->mode,
            ...$this->headerData($section),
        ]);
    }

    /**
     * Load the served-but-unanswered question (or serve the next one).
     */
    private function loadQuestion(TryoutAnswerServiceInterface $answers): void
    {
        $view = $answers->currentQuestion($this->attemptSection());
        $this->question = $view?->toArray();

        if ($view === null) {
            $this->dispatch('section-closed');
        }
    }

    /**
     * Whether the active question is the first of its unit block.
     */
    private function isFirstOfBlock(): bool
    {
        $rows = $this->attemptSection()->answers()->with('question:id,tryout_unit_id')->get();
        $last = $rows->last();
        $before = $rows->count() > 1 ? $rows->get($rows->count() - 2) : null;

        return $before === null || $before->question->tryout_unit_id !== $last?->question->tryout_unit_id;
    }
}
