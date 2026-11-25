<?php

namespace App\Livewire\Tryout;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\EntitlementServiceInterface;
use App\Enums\ActivityName;
use App\Models\Tryout;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * IELTS theory of a Try Out (route tryout.theory, D11): the theory module
 * read on the site (Pro), the theory PDF read in an in-page viewer without
 * download or print (Pro) and the link to the paid material (everyone).
 * Gratis sees the first two locked. Read only: no module progress, Quick
 * Question or checkpoint.
 */
#[Title('Teori Try Out')]
class Theory extends Component
{
    #[Locked]
    public Tryout $tryout;

    /**
     * Load a published Try Out.
     */
    public function mount(Tryout $tryout, EntitlementServiceInterface $entitlements, ActivityTrackerInterface $tracker): void
    {
        abort_unless($tryout->status->isPublished(), 404);

        $this->tryout = $tryout;

        if ($entitlements->can($this->user(), 'tryout_theory')) {
            $tracker->track(ActivityName::TryoutTheoryOpened, ['tryout_id' => $tryout->id], $this->user());
        }
    }

    /**
     * Log the click on the paid material link, then open it.
     */
    public function openMaterials(ActivityTrackerInterface $tracker): void
    {
        if (! $this->tryout->hasMaterialsLink()) {
            return;
        }

        $tracker->track(ActivityName::TryoutMaterialsClicked, ['tryout_id' => $this->tryout->id, 'from' => 'theory'], $this->user());

        $this->redirect((string) $this->tryout->materials_url);
    }

    /**
     * Render the theory page; the module text is only loaded for Pro.
     *
     * @return View The livewire.tryout.theory view.
     */
    public function render(EntitlementServiceInterface $entitlements): View
    {
        $can = $entitlements->can($this->user(), 'tryout_theory');
        $module = $can ? $this->tryout->theoryModule()->where('is_tryout_theory', true)->with('coreSections')->first() : null;

        return view('livewire.tryout.theory', [
            'can_theory' => $can,
            'module' => $module,
            'has_module' => $this->tryout->hasTheoryModule(),
            'has_pdf' => filled($this->tryout->theory_pdf_path),
            'premium_enabled' => $entitlements->premiumEnabled(),
            // Drawn on every PDF page so a leaked screenshot can be traced.
            'watermark' => $can ? $this->user()->name.' · '.$this->user()->email.' · '.now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i') : '',
        ]);
    }

    /**
     * The signed-in mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
