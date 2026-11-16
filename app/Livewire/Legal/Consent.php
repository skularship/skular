<?php

namespace App\Livewire\Legal;

use App\Contracts\ConsentServiceInterface;
use App\Http\Middleware\EnsureLegalConsent;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Consent screen (route legal.consent) that EnsureLegalConsent sends a mentee
 * to before the app: after the first Google sign-in, for accounts from before
 * the consent log, and when the Terms or Privacy Policy version changed.
 * Asks for the same two statements as the sign-up form and logs them.
 */
#[Title('Persetujuan Syarat & Kebijakan Privasi')]
#[Layout('layouts.auth')]
class Consent extends Component
{
    /**
     * "Aku setuju dengan Syarat & Ketentuan dan Kebijakan Privasi".
     */
    public bool $terms = false;

    /**
     * "Aku 18 tahun ke atas, atau orang tua/waliku sudah mengizinkan".
     */
    public bool $age_confirmation = false;

    /**
     * Skip the screen when nothing is missing.
     */
    public function mount(ConsentServiceInterface $consents): void
    {
        if ($consents->hasCurrentConsent($this->user())) {
            $this->redirectIntended(route('dashboard'), navigate: false);
        }
    }

    /**
     * Validate both statements, log the consent, and continue to the page the mentee asked for.
     */
    public function accept(ConsentServiceInterface $consents): void
    {
        $this->validate([
            'terms' => ['accepted'],
            'age_confirmation' => ['accepted'],
        ], [
            'terms.accepted' => __('Centang persetujuan Syarat & Ketentuan dan Kebijakan Privasi untuk melanjutkan.'),
            'age_confirmation.accepted' => __('Centang pernyataan usia untuk melanjutkan.'),
        ]);

        $user = $this->user();

        $consents->record($user, $consents->requiredDocuments(), $consents->pendingSource($user), true);

        session()->put(EnsureLegalConsent::SESSION_KEY, $consents->versionKey());

        $this->redirectIntended(route('dashboard'), navigate: false);
    }

    /**
     * Render the consent screen.
     *
     * @return View The livewire.legal.consent view.
     */
    public function render(ConsentServiceInterface $consents): View
    {
        $user = $this->user();

        return view('livewire.legal.consent', [
            'missing' => $consents->missingDocuments($user),
            'is_first_time' => ! $user->consents()->exists(),
        ]);
    }

    /**
     * The signed-in mentee (the route sits behind auth).
     */
    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
