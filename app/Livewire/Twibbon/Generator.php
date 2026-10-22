<?php

namespace App\Livewire\Twibbon;

use App\Contracts\TwibbonServiceInterface;
use App\Exceptions\TwibbonException;
use App\Models\ScholarshipApplication;
use App\Models\TwibbonTemplate;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Generates a shareable twibbon for a mentee who passed a scholarship.
 *
 * @property-read Collection<int, TwibbonTemplate> $templates
 * @property-read string|null $generatedPath
 */
#[Title('Twibbon Skular')]
class Generator extends Component
{
    public ScholarshipApplication $application;

    public ?int $template_id = null;

    /**
     * Resolve the application and make sure the mentee earned this twibbon.
     */
    public function mount(ScholarshipApplication $application): void
    {
        $user = $this->user();

        $this->application = $application->loadMissing('scholarship');

        if ($application->user_id !== $user->id) {
            abort(403);
        }

        if (! $application->qualifiesForTwibbon()) {
            $this->redirect(route('scholarships.tracker'));

            return;
        }

        $this->template_id = $this->templates->first()?->id;
    }

    /**
     * Pick the template used for the next render.
     */
    public function selectTemplate(int $template_id): void
    {
        if ($this->templates->contains('id', $template_id)) {
            $this->template_id = $template_id;
        }
    }

    /**
     * Render the twibbon and remember it on the application.
     */
    public function generate(TwibbonServiceInterface $twibbon_service): void
    {
        $template = $this->selectedTemplate();

        if ($template === null) {
            $this->dispatch('toast', variant: 'warning', text: __('Belum ada template twibbon yang aktif.'));

            return;
        }

        try {
            $path = $twibbon_service->generate($this->user(), $this->application, $template);
        } catch (TwibbonException $exception) {
            report($exception);

            $this->dispatch('toast', variant: 'error', text: __('Twibbon gagal dibuat. Coba lagi ya.'));

            return;
        }

        $metadata = is_array($this->application->metadata) ? $this->application->metadata : [];

        $this->application->fill([
            'metadata' => array_merge($metadata, ['twibbon_path' => $path]),
        ])->save();

        $this->application->refresh();

        $this->dispatch('toast', variant: 'success', text: __('Twibbon siap diunduh dan dibagikan!'));
    }

    /**
     * Get the active twibbon templates offered to the mentee.
     *
     * @return Collection<int, TwibbonTemplate>
     */
    #[Computed]
    public function templates()
    {
        return TwibbonTemplate::query()->activeOrdered()->get();
    }

    /**
     * Get the path of the last generated twibbon when it still exists.
     */
    #[Computed]
    public function generatedPath(): ?string
    {
        $path = is_array($this->application->metadata)
            ? ($this->application->metadata['twibbon_path'] ?? null)
            : null;

        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    /**
     * Get the template selected for rendering.
     */
    private function selectedTemplate(): ?TwibbonTemplate
    {
        return $this->templates->firstWhere('id', $this->template_id)
            ?? $this->templates->first();
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
        return view('livewire.twibbon.generator', [
            'templates' => $this->templates,
            'generated_path' => $this->generatedPath,
        ]);
    }
}
