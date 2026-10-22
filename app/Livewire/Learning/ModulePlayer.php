<?php

namespace App\Livewire\Learning;

use App\Contracts\ModuleFeedbackServiceInterface;
use App\Contracts\ModuleOutlineServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Data\ModuleProgressData;
use App\Enums\AiFeedbackStatus;
use App\Enums\CheckpointStatus;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\QuickQuestion;
use App\Models\User;
use App\Models\UserPracticeResponse;
use App\Services\ModuleFeedbackService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Plays a single module through the Skular learning loop:
 * pembukaan → pahami → quick question → praktik → feedback AI → checkpoint.
 *
 * @property-read array<string, string> $steps
 * @property-read Collection<int, PracticeForm> $activeForms
 * @property-read Collection<int, QuickQuestion> $questions
 * @property-read int $progress
 * @property-read int $practicePending
 * @property-read ModuleProgressData|null $nextModule
 */
#[Title('Belajar Modul')]
class ModulePlayer extends Component
{
    use WithFileUploads;

    /**
     * Maximum AI feedback requests a single mentee may trigger per minute.
     */
    private const AI_RATE_LIMIT_PER_MINUTE = 10;

    #[Locked]
    public Module $module;

    #[Url(as: 'section')]
    public string $section = 'opening';

    /**
     * Practice answers, grouped by practice form id.
     *
     * @var array<int|string, array<string, mixed>>
     */
    public array $answers = [];

    /**
     * Quick question answers, keyed by question id.
     *
     * @var array<int, string>
     */
    public array $quick_answers = [];

    public bool $quick_checked = false;

    /**
     * Module feedback checklist selected by the mentee.
     *
     * @var array<int, string>
     */
    public array $ratings = [];

    public ?string $suggestion = null;

    public bool $module_completed = false;

    /**
     * Practice responses for this module, loaded at most once per request.
     *
     * @var Collection<int, UserPracticeResponse>|null
     */
    private ?Collection $responses_cache = null;

    /**
     * The section being left by a query string (back/forward) update.
     */
    private ?string $previous_section = null;

    /**
     * Resolve the module, verify access and hydrate stored answers.
     */
    public function mount(Module $module, ProgressServiceInterface $progress_service): void
    {
        // Draft modules are still being written, so mentees must not open them
        // even when they guess the URL.
        abort_unless($module->isPublished(), 404);

        $user = $this->user();

        // checkpointFor resolves the derived unlock state without writing a
        // progress row, and returns null for a module outside the active path.
        $checkpoint = $progress_service->checkpointFor($user, $module);

        if ($checkpoint === null || $checkpoint->status === CheckpointStatus::Locked) {
            throw new AuthorizationException('Modul ini masih terkunci. Selesaikan modul sebelumnya dulu ya.');
        }

        $this->module = $module->loadMissing(['fase', 'practiceForms', 'quickQuestions', 'scholarship']);

        if (! array_key_exists($this->section, $this->steps)) {
            $this->section = 'opening';
        }

        $this->module_completed = $checkpoint->isCompleted();

        if (! $this->module_completed && $checkpoint->status === CheckpointStatus::Available) {
            $progress_service->startModule($user, $this->module);
        }

        $this->prefill($user);
    }

    /**
     * Open the module and jump to the Pahami section.
     */
    public function start(): void
    {
        $this->progressService()->startModule($this->user(), $this->module);

        $this->section = 'pahami';

        $this->notifySectionChanged();
    }

    /**
     * Move to another learning section.
     */
    public function goToSection(string $section): void
    {
        if (! array_key_exists($section, $this->steps) || $section === $this->section) {
            return;
        }

        if (! $this->module_completed && $section !== 'opening') {
            $this->progressService()->startModule($this->user(), $this->module);
        }

        $this->section = $section;

        $this->notifySectionChanged();
    }

    /**
     * Remember the section that is being left by a query string update.
     */
    public function updatingSection(): void
    {
        $this->previous_section = $this->section;
    }

    /**
     * Scroll to the top when the section changes through the URL/history API.
     *
     * Section buttons call notifySectionChanged() directly because Livewire
     * only runs `updated` hooks for frontend property updates. This hook covers
     * the back/forward buttons, which set `section` from the query string.
     */
    public function updatedSection(string $section): void
    {
        if ($section === $this->previous_section) {
            return;
        }

        if (! array_key_exists($section, $this->steps)) {
            return;
        }

        $this->notifySectionChanged();
    }

    /**
     * Move one section forward in the learning loop.
     */
    public function nextSection(): void
    {
        $keys = array_keys($this->steps);
        $index = array_search($this->section, $keys, true);
        $index = $index === false ? 0 : (int) $index;

        $this->goToSection($keys[min($index + 1, count($keys) - 1)]);
    }

    /**
     * Move one section back in the learning loop.
     */
    public function previousSection(): void
    {
        $keys = array_keys($this->steps);
        $index = array_search($this->section, $keys, true);
        $index = $index === false ? 0 : (int) $index;

        $this->goToSection($keys[max($index - 1, 0)]);
    }

    /**
     * Grade every quick question the mentee has answered.
     */
    public function checkQuickAnswers(): void
    {
        if ($this->questions->isEmpty()) {
            $this->section = $this->hasPracticeForms() ? 'praktik' : 'checkpoint';

            $this->notifySectionChanged();

            return;
        }

        if ($this->questions->contains(fn (QuickQuestion $question): bool => ! array_key_exists($question->id, $this->quick_answers))) {
            throw ValidationException::withMessages([
                'quick_answers' => 'Jawab semua pertanyaan dulu sebelum memeriksa jawabanmu.',
            ]);
        }

        $this->quick_checked = true;
        $this->resetErrorBag('quick_answers');
    }

    /**
     * Submit a practice form and request structured AI feedback.
     *
     * @throws ValidationException
     */
    public function submitPractice(int $form_id): void
    {
        $form = $this->activeForms->firstWhere('id', $form_id);

        if (! $form instanceof PracticeForm) {
            return;
        }

        if ($this->aiRateLimited()) {
            $this->dispatch('toast', variant: 'warning', text: __('Terlalu banyak permintaan AI. Tunggu sebentar lalu coba lagi.'));

            return;
        }

        try {
            $response = app(PracticeSubmissionServiceInterface::class)->submit(
                $this->user(),
                $form,
                $this->answers[$form_id] ?? [],
            );
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages($this->prefixErrorKeys($form_id, $exception->errors()));
        }

        $this->responses_cache = null;

        // The submission is saved before the AI call, so a failed evaluation
        // still gets an honest message and a retry action instead of a
        // success toast for work the AI never completed.
        if ($response->ai_status === AiFeedbackStatus::Completed) {
            $this->dispatch('toast', variant: 'success', text: __('Jawabanmu tersimpan dan feedback AI sudah siap.'));
        } elseif ($response->ai_status === AiFeedbackStatus::Failed) {
            $this->dispatch('toast', variant: 'warning', text: __('Jawabanmu tersimpan, tapi feedback AI belum tersedia. Kamu bisa coba lagi.'));
        } else {
            $this->dispatch('toast', variant: 'info', text: __('Jawabanmu tersimpan. AI sedang meninjau praktikmu.'));
        }
    }

    /**
     * Ask the AI evaluator again after a failed feedback request.
     */
    public function retryFeedback(int $form_id): void
    {
        $response = $this->responseFor($form_id);

        if ($response === null) {
            return;
        }

        if ($this->aiRateLimited()) {
            $this->dispatch('toast', variant: 'warning', text: __('Terlalu banyak permintaan AI. Tunggu sebentar lalu coba lagi.'));

            return;
        }

        $response = app(PracticeSubmissionServiceInterface::class)->retryAiFeedback($response);
        $this->responses_cache = null;

        $this->dispatch(
            'toast',
            variant: $response->ai_status === AiFeedbackStatus::Completed ? 'success' : 'warning',
            text: $response->ai_status === AiFeedbackStatus::Completed
                ? __('Feedback AI berhasil diperbarui.')
                : __('Feedback AI masih belum tersedia. Coba lagi beberapa saat lagi.'),
        );
    }

    /**
     * Store the feedback the mentee leaves for this module.
     */
    public function saveModuleFeedback(): void
    {
        app(ModuleFeedbackServiceInterface::class)->save(
            $this->user(),
            $this->module,
            $this->ratings,
            $this->suggestion,
        );

        $this->dispatch('toast', variant: 'success', text: __('Terima kasih, masukanmu tersimpan!'));
    }

    /**
     * Confirm the checkpoint manually and unlock the next module.
     */
    public function confirmCheckpoint(ProgressServiceInterface $progress_service): void
    {
        if ($this->module_completed) {
            return;
        }

        if ($this->practicePending > 0) {
            $this->addError('checkpoint', 'Selesaikan semua latihan praktik modul ini dulu sebelum mengunci checkpoint.');

            return;
        }

        $progress_service->completeModule($this->user(), $this->module, $this->averageScore());

        $this->module_completed = true;

        $this->dispatch('toast', variant: 'success', text: __('Modul selesai! Modul berikutnya sudah terbuka.'));
    }

    /**
     * The learning sections available for this module.
     *
     * Labels come from the shared outline service so the mentee loop and the
     * admin preview can never disagree about the order of the steps.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function steps(): array
    {
        return app(ModuleOutlineServiceInterface::class)->stepLabels($this->module);
    }

    /**
     * The practice forms mentees can submit for this module.
     *
     * @return Collection<int, PracticeForm>
     */
    #[Computed]
    public function activeForms(): Collection
    {
        return $this->module->practiceForms
            ->filter(fn (PracticeForm $form): bool => $form->is_active)
            ->sortBy(['ordering', 'id'])
            ->values();
    }

    /**
     * The quick questions mentees answer between Pahami and Praktik.
     *
     * @return Collection<int, QuickQuestion>
     */
    #[Computed]
    public function questions(): Collection
    {
        return $this->module->quickQuestions
            ->filter(fn (QuickQuestion $question): bool => $question->is_active)
            ->sortBy(['ordering', 'id'])
            ->values();
    }

    /**
     * Count the active practice forms the mentee has not submitted yet.
     */
    #[Computed]
    public function practicePending(): int
    {
        $responses = $this->responses();

        return $this->activeForms
            ->reject(fn (PracticeForm $form): bool => $responses->has($form->id))
            ->count();
    }

    /**
     * Get how far the mentee is in this module as a percentage.
     */
    #[Computed]
    public function progress(): int
    {
        $keys = array_keys($this->steps);

        if (count($keys) <= 1) {
            return 100;
        }

        $index = array_search($this->section, $keys, true);

        if ($index === false) {
            return 0;
        }

        return (int) round($index / (count($keys) - 1) * 100);
    }

    /**
     * Get the roadmap entry that follows this module.
     */
    #[Computed]
    public function nextModule(): ?ModuleProgressData
    {
        return $this->progressService()->nextModule($this->user(), $this->module);
    }

    /**
     * Get the mentee practice responses for this module, keyed by form id.
     *
     * @return Collection<int, UserPracticeResponse>
     */
    private function responses(): Collection
    {
        return $this->responses_cache ??= UserPracticeResponse::query()
            ->where('user_id', $this->user()->id)
            ->whereIn('practice_form_id', $this->activeForms->pluck('id'))
            ->get()
            ->keyBy('practice_form_id');
    }

    /**
     * Get the stored response for a single practice form.
     */
    private function responseFor(int $form_id): ?UserPracticeResponse
    {
        return $this->responses()->get($form_id);
    }

    /**
     * The average score the AI gave across every practice of this module.
     */
    private function averageScore(): ?int
    {
        $scores = $this->responses()
            ->pluck('ai_score')
            ->filter(fn ($score): bool => is_numeric($score))
            ->map(fn ($score): int => (int) $score);

        if ($scores->isEmpty()) {
            return null;
        }

        return (int) round($scores->avg());
    }

    /**
     * Prefill the form with the mentee's stored answers and feedback.
     */
    private function prefill(User $user): void
    {
        foreach ($this->responses() as $response) {
            $this->answers[(string) $response->practice_form_id] = $response->answers ?? [];
        }

        $feedback = $user->moduleFeedbacks()->where('module_id', $this->module->id)->first();

        if ($feedback !== null) {
            $this->ratings = array_values(is_array($feedback->ratings) ? $feedback->ratings : []);
            $this->suggestion = $feedback->suggestion;
        }
    }

    /**
     * Namespace service validation errors under the practice form they belong to.
     *
     * The service validates a flat answer payload, so its keys arrive as
     * "answers.field" and have to be re-keyed to "answers.{form}.field".
     *
     * @param  array<string, array<int, string>>  $errors
     * @return array<string, array<int, string>>
     */
    private function prefixErrorKeys(int $form_id, array $errors): array
    {
        $prefixed = [];

        foreach ($errors as $field => $messages) {
            $key = preg_replace('/^answers\./', '', (string) $field);

            $prefixed['answers.'.$form_id.'.'.$key] = $messages;
        }

        return $prefixed;
    }

    /**
     * Determine whether the mentee still has practice forms to submit.
     */
    private function hasPracticeForms(): bool
    {
        return $this->activeForms->isNotEmpty();
    }

    /**
     * Determine whether the mentee exceeded the AI feedback rate limit.
     *
     * This protects the provider budget from duplicated clicks and retry
     * loops; the stored submission itself is never blocked by this guard.
     */
    private function aiRateLimited(): bool
    {
        $key = 'ai-practice:'.$this->user()->id;

        if (RateLimiter::tooManyAttempts($key, self::AI_RATE_LIMIT_PER_MINUTE)) {
            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    /**
     * Tell the browser a new learning section is now rendered.
     *
     * Livewire dispatches this after morphing the response, so the listener in
     * app.js scrolls only once the opening content of the new section exists.
     */
    private function notifySectionChanged(): void
    {
        $this->dispatch('module-section-changed', section: $this->section);
    }

    /**
     * Get the progress service bound in the container.
     */
    private function progressService(): ProgressServiceInterface
    {
        return app(ProgressServiceInterface::class);
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
        return view('livewire.learning.module-player', [
            'steps' => $this->steps,
            'forms' => $this->activeForms,
            'questions' => $this->questions,
            'responses' => $this->responses(),
            'rating_options' => ModuleFeedbackService::RATING_OPTIONS,
            'average_score' => $this->averageScore(),
            'practice_pending' => $this->practicePending,
            'next_module' => $this->nextModule,
        ]);
    }
}
