<?php

namespace App\Livewire\Learning;

use App\Contracts\BrainstormingSessionServiceInterface;
use App\Contracts\ModuleBrainstormingServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Data\BrainstormOptionData;
use App\Data\BrainstormOutlineData;
use App\Data\BrainstormQuestionData;
use App\Data\BrainstormResultData;
use App\Enums\BrainstormQuestionType;
use App\Enums\CheckpointStatus;
use App\Exceptions\AiBrainstormingException;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Services\BrainstormingConfigService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Admin-configured brainstorming that gathers a mentee's ideas and asks the AI
 * for a writing outline without ever touching their practice answer.
 */
class Brainstorming extends Component
{
    /**
     * Maximum outline requests a mentee may make per minute.
     */
    private const RATE_LIMIT_PER_MINUTE = 8;

    #[Locked]
    public Module $module;

    public int $form_id;

    public ?string $field_label = null;

    public bool $open = false;

    /**
     * One of: idle, active, loading, summary, empty.
     */
    public string $state = 'idle';

    /**
     * Admin-configured questions, ready to render.
     *
     * @var array<int, array{
     *     id: string,
     *     question: string,
     *     type: string,
     *     hint: string|null,
     *     required: bool,
     *     options: array<int, array{id: string, label: string}>
     * }>
     */
    public array $questions = [];

    /**
     * Answers keyed by the stable question identifier.
     *
     * @var array<string, mixed>
     */
    public array $answers = [];

    /**
     * Structured outline returned by the AI.
     *
     * @var array<string, mixed>|null
     */
    public ?array $outline = null;

    public ?string $summary = null;

    public ?string $error = null;

    public bool $show_outline = true;

    public function mount(Module $module, int $form_id, ?string $field_label = null): void
    {
        $user = $this->user();

        // Read-only access check: opening the brainstorm panel must not write
        // checkpoint rows just because the page rendered.
        $checkpoint = app(ProgressServiceInterface::class)->checkpointFor($user, $module);

        if ($checkpoint === null || $checkpoint->status === CheckpointStatus::Locked) {
            throw new AuthorizationException('Modul ini masih terkunci.');
        }

        $form = $module->practiceForms()->whereKey($form_id)->where('is_active', true)->first();

        if (! $form instanceof PracticeForm) {
            throw new AuthorizationException('Latihan ini tidak tersedia untuk modul tersebut.');
        }

        $this->module = $module;
        $this->form_id = $form->id;
        $this->field_label = $field_label;
        $this->loadQuestions();

        if ($this->questions !== []) {
            $this->restoreSession();
        }
    }

    /**
     * Persist answers as soon as the mentee changes them.
     *
     * This hook never calls the AI provider; selecting or typing an answer is
     * only a local autosave.
     */
    public function updated(string $property): void
    {
        if ($property === 'answers' || str_starts_with($property, 'answers.')) {
            $this->persistAnswers();
        }
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    /**
     * (Re)load the admin configuration without calling the AI provider.
     */
    public function start(): void
    {
        $this->resetErrorBag();
        $this->error = null;
        $this->loadQuestions();
    }

    /**
     * Ask the AI to turn the validated answers into a draft outline.
     */
    public function finish(ModuleBrainstormingServiceInterface $brainstorm_service): void
    {
        // A second request while the first one is still running would duplicate
        // provider cost and could overwrite newer output with an older reply.
        if ($this->state === 'loading') {
            return;
        }

        $form = $this->form();

        if ($form === null) {
            return;
        }

        $this->resetErrorBag();
        $this->error = null;

        $resolution = app(BrainstormingConfigService::class)->resolveAnswers($this->module, $this->answers);

        if ($resolution['errors'] !== []) {
            foreach ($resolution['errors'] as $key => $message) {
                $this->addError($key === 'answers' ? 'answers' : 'answers.'.$key, $message);
            }

            return;
        }

        // Keep the answers even if the request is rate limited or the AI fails.
        $this->persistAnswers();

        if ($this->rateLimited()) {
            $this->error = __('Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.');

            return;
        }

        $this->state = 'loading';
        $this->error = null;
        $this->outline = null;
        $this->summary = null;

        try {
            $result = $brainstorm_service->summary($this->module, $form, $this->answers);
        } catch (AiBrainstormingException $exception) {
            // The answers stay untouched so the mentee only needs to retry.
            $this->state = 'active';
            $this->error = $exception->getMessage();

            return;
        }

        $this->outline = $result->outline?->toArray();
        $this->summary = $result->summary;
        $this->show_outline = true;
        $this->state = $this->outline === null ? 'active' : 'summary';

        $this->persistOutline($result);
    }

    /**
     * Hide or reveal the generated outline without losing it.
     */
    public function toggleOutline(): void
    {
        $this->show_outline = ! $this->show_outline;
    }

    /**
     * Clear the session and read the configuration again.
     */
    public function restart(): void
    {
        $form = $this->form();

        if ($form !== null) {
            try {
                app(BrainstormingSessionServiceInterface::class)->reset($this->user(), $this->module, $form);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $this->answers = [];
        $this->outline = null;
        $this->summary = null;
        $this->error = null;
        $this->state = 'idle';
        $this->show_outline = true;
        $this->resetErrorBag();
        $this->loadQuestions();
    }

    /**
     * Get the practice form this session is anchored to.
     */
    public function form(): ?PracticeForm
    {
        return $this->module->practiceForms()
            ->whereKey($this->form_id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Load the questions saved by the curriculum team.
     */
    private function loadQuestions(): void
    {
        $form = $this->form();

        if ($form === null) {
            $this->questions = [];
            $this->state = 'empty';

            return;
        }

        $result = app(ModuleBrainstormingServiceInterface::class)->questions($this->module, $form);

        $this->questions = array_map(
            fn (BrainstormQuestionData $question): array => [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type->value,
                'hint' => $question->hint,
                'required' => $question->required,
                'options' => array_map(
                    fn (BrainstormOptionData $option): array => [
                        'id' => $option->id,
                        'label' => $option->label,
                    ],
                    $question->options,
                ),
            ],
            $result->questions,
        );

        $this->ensureMultipleChoiceAnswers();

        $this->state = $this->questions === [] ? 'empty' : 'active';
    }

    /**
     * Restore the mentee's previous answers and outline for this form.
     */
    private function restoreSession(): void
    {
        $form = $this->form();

        if ($form === null) {
            return;
        }

        $session = app(BrainstormingSessionServiceInterface::class)->find($this->user(), $this->module, $form);

        if ($session === null) {
            return;
        }

        $this->answers = app(BrainstormingConfigService::class)->sanitizeAnswers(
            $this->module,
            is_array($session->answers) ? $session->answers : [],
        );

        $this->ensureMultipleChoiceAnswers();

        $outline = is_array($session->outline) ? $session->outline : [];

        // Restore the outline only when at least one answer still matches the
        // current configuration; otherwise it would describe an obsolete form.
        if ($outline !== [] && $this->hasAnswers()) {
            $this->outline = $outline;
            $this->summary = $session->summary;
            $this->state = 'summary';
        }
    }

    /**
     * Determine whether at least one answer carries a value.
     */
    private function hasAnswers(): bool
    {
        foreach ($this->answers as $value) {
            if (is_array($value)) {
                if ($value !== []) {
                    return true;
                }

                continue;
            }

            if ($value !== null && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensure every multiple-choice question has an array value.
     */
    private function ensureMultipleChoiceAnswers(): void
    {
        // A checkbox group needs an array state before the first interaction;
        // otherwise Livewire would bind the first checked value as a string.
        foreach ($this->questions as $question) {
            if ($question['type'] === BrainstormQuestionType::MultipleChoice->value
                && ! array_key_exists($question['id'], $this->answers)) {
                $this->answers[$question['id']] = [];
            }
        }
    }

    /**
     * Autosave the answers without blocking the mentee when storage fails.
     */
    private function persistAnswers(): void
    {
        if ($this->questions === []) {
            return;
        }

        $form = $this->form();

        if ($form === null) {
            return;
        }

        try {
            app(BrainstormingSessionServiceInterface::class)->saveAnswers(
                $this->user(),
                $this->module,
                $form,
                $this->answers,
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Persist the generated outline together with the answers that produced it.
     */
    private function persistOutline(BrainstormResultData $result): void
    {
        if (! $result->outline instanceof BrainstormOutlineData) {
            return;
        }

        $form = $this->form();

        if ($form === null) {
            return;
        }

        try {
            app(BrainstormingSessionServiceInterface::class)->saveOutline(
                $this->user(),
                $this->module,
                $form,
                $this->answers,
                $result->outline,
                $result->summary ?? '',
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Determine whether the mentee has exceeded the outline rate limit.
     */
    private function rateLimited(): bool
    {
        $key = 'ai-brainstorm:'.$this->user()->id;

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_PER_MINUTE)) {
            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    /**
     * Get the authenticated mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    public function render(): View
    {
        return view('livewire.learning.brainstorming');
    }
}
