<?php

namespace App\Livewire\Learning;

use App\Contracts\ModuleFeedbackServiceInterface;
use App\Models\Module;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Free-access commitment screen (route feedback.commitment): the mentee agrees
 * to give honest feedback at the end of every module. Shown once, after
 * profiling or when first opening a module, whichever comes first; modules
 * stay closed until it is accepted (checked in ModulePlayer::mount).
 */
#[Title('Akses Gratis Skular')]
class FeedbackCommitment extends Component
{
    /**
     * The module the mentee wanted to open, so "Saya setuju" can continue there.
     */
    #[Url(as: 'modul')]
    #[Locked]
    public ?int $module_id = null;

    /**
     * Skip the screen for mentees who already agreed.
     */
    public function mount(): void
    {
        if ($this->user()->hasFeedbackCommitment()) {
            $this->redirect($this->nextUrl(), navigate: true);
        }
    }

    /**
     * "Saya setuju": record the commitment and continue.
     */
    public function accept(ModuleFeedbackServiceInterface $feedback_service): void
    {
        $feedback_service->acceptCommitment($this->user());

        $this->redirect($this->nextUrl(), navigate: true);
    }

    /**
     * Where to go after agreeing: the module that was requested, else the recommendations.
     */
    private function nextUrl(): string
    {
        $module = $this->module_id === null ? null : Module::query()->find($this->module_id);

        return $module instanceof Module
            ? route('modules.show', $module)
            : route('profiling.recommendation');
    }

    /**
     * Where "Nanti saja" leads: modules need the commitment, so it never returns to one.
     */
    private function laterUrl(): string
    {
        return $this->module_id === null ? route('profiling.recommendation') : route('dashboard');
    }

    /**
     * Get the authenticated mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * Render the commitment screen.
     *
     * @return View The livewire.learning.feedback-commitment view.
     */
    public function render(): View
    {
        return view('livewire.learning.feedback-commitment', [
            'later_url' => $this->laterUrl(),
        ]);
    }
}
