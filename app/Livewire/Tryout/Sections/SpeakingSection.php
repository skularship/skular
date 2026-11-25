<?php

namespace App\Livewire\Tryout\Sections;

use App\Contracts\TryoutAnswerServiceInterface;
use App\Contracts\TryoutAttemptServiceInterface;
use App\Exceptions\TryoutAnswerRejectedException;
use App\Exceptions\TryoutSectionExpiredException;
use App\Livewire\Tryout\Concerns\InteractsWithAttemptSection;
use App\Models\TryoutAnswer;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Speaking subtest (child of the Runner): Part 1, 2 (60 s preparation, up to
 * 120 s speaking) and 3. The mentee's browser transcribes the answer live
 * with the Web Speech API (free, no audio upload) and can play the take back
 * locally before sending; only the transcript and the speaking time reach
 * the server. Parts can be opened in any order (D9).
 */
class SpeakingSection extends Component
{
    use InteractsWithAttemptSection;

    /**
     * Parts a mentee may send per minute.
     */
    private const SENDS_PER_MINUTE = 10;

    #[Locked]
    public int $attempt_section_id;

    /**
     * The parts as TryoutQuestionViewData::toArray(), in part order.
     *
     * @var array<int, array<string, mixed>>
     */
    #[Locked]
    public array $prompts = [];

    /**
     * Index of the open part.
     */
    public int $active = 0;

    /**
     * Load the parts.
     */
    public function mount(int $attempt_section_id, TryoutAnswerServiceInterface $answers): void
    {
        $this->attempt_section_id = $attempt_section_id;
        $this->prompts = array_map(fn ($prompt): array => $prompt->toArray(), $answers->productivePrompts($this->attemptSection()));
    }

    /**
     * Open another part.
     */
    public function goToPart(int $index): void
    {
        if (isset($this->prompts[$index])) {
            $this->active = $index;
        }
    }

    /**
     * Store the browser-made transcript of a part and move to the next part.
     *
     * @param  int  $index  Part index.
     * @param  string  $transcript  Transcript from the Web Speech API.
     * @param  int  $seconds  How long the mentee spoke.
     * @return bool True when the part was stored.
     */
    public function submitPart(int $index, string $transcript, int $seconds, TryoutAnswerServiceInterface $answers): bool
    {
        $prompt = $this->prompts[$index] ?? null;

        if ($prompt === null) {
            return false;
        }

        if ($this->tooManyAttempts('speaking', self::SENDS_PER_MINUTE)) {
            $this->addError('transcript', __('Terlalu cepat. Tunggu sebentar lalu kirim lagi.'));

            return false;
        }

        try {
            $answers->saveSpokenAnswer($this->attemptSection(), (string) $prompt['code'], $transcript, $seconds);
        } catch (ValidationException $exception) {
            $this->addError('transcript', (string) collect($exception->errors())->flatten()->first());

            return false;
        } catch (TryoutSectionExpiredException|TryoutAnswerRejectedException $exception) {
            $this->dispatch('toast', variant: 'info', text: $exception->getMessage());
            $this->dispatch('section-closed');

            return false;
        }

        $this->resetErrorBag('transcript');
        $this->dispatch('toast', variant: 'success', text: __('Part :n terkirim.', ['n' => $prompt['part'] ?? $index + 1]));

        if (isset($this->prompts[$index + 1])) {
            $this->active = $index + 1;
        }

        return true;
    }

    /**
     * Send the Speaking subtest.
     */
    public function submitSpeaking(TryoutAttemptServiceInterface $attempts): void
    {
        $attempts->submitSection($this->attemptSection());
        $this->dispatch('section-closed');
    }

    /**
     * Render the subtest.
     *
     * @return View The livewire.tryout.sections.speaking view.
     */
    public function render(): View
    {
        $section = $this->attemptSection();
        $rows = $section->answers()->with('question:id,code')->get()->keyBy(fn (TryoutAnswer $row): string => $row->question->code);

        $states = [];

        foreach ($this->prompts as $index => $prompt) {
            /** @var TryoutAnswer|null $row */
            $row = $rows[$prompt['code']] ?? null;
            $states[$index] = [
                'sent' => $row?->answered_at !== null,
                'transcript' => (string) ($row->transcript ?? ''),
            ];
        }

        return view('livewire.tryout.sections.speaking', [
            'section' => $section,
            'states' => $states,
            'language' => (string) config('tryout.speaking.recognition_language', 'en-US'),
            ...$this->headerData($section),
        ]);
    }
}
