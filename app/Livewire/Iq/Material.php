<?php

namespace App\Livewire\Iq;

use App\Contracts\EntitlementServiceInterface;
use App\Contracts\IqAttemptServiceInterface;
use App\Contracts\IqResultServiceInterface;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Material slides of the current Tes IQ (route iq.material): the PPT
 * exported to PDF, read in the in-page viewer (resources/js/pdf-viewer.js:
 * canvas pages, watermark, no download, no print). Paid access only;
 * everyone else sees a locked frame with the call to open access.
 */
#[Title('Materi Tes IQ')]
class Material extends Component
{
    /**
     * Render the material page.
     *
     * @return View The livewire.iq.material view.
     */
    public function render(IqAttemptServiceInterface $attempts, IqResultServiceInterface $results, EntitlementServiceInterface $entitlements): View
    {
        $user = $this->user();
        $test = $attempts->currentTest();
        $unlocked = $results->canSeeResults($user);

        return view('livewire.iq.material', [
            'test' => $test,
            'has_pdf' => $test?->hasMaterialPdf() ?? false,
            'unlocked' => $unlocked,
            'premium_enabled' => $entitlements->premiumEnabled(),
            // Drawn on every page so a leaked screenshot can be traced.
            'watermark' => $unlocked ? $user->name.' · '.$user->email.' · '.now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i') : '',
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
