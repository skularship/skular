<?php

namespace App\Services;

use App\Contracts\ConsentServiceInterface;
use App\Enums\ConsentSource;
use App\Enums\LegalDocument;
use App\Models\User;

/**
 * Consent log for the legal documents (user_consents). Terms and Privacy are
 * required for every signed-in mentee; checkout also logs Terms and Refund.
 * Rows are only ever added, never changed, and hold no IP address.
 */
class ConsentService implements ConsentServiceInterface
{
    /**
     * The documents every signed-in mentee must have accepted in their current version.
     *
     * @return array<int, LegalDocument>
     */
    public function requiredDocuments(): array
    {
        return [LegalDocument::Terms, LegalDocument::Privacy];
    }

    /**
     * The required documents whose current version the mentee has not accepted.
     *
     * @return array<int, LegalDocument>
     */
    public function missingDocuments(User $user): array
    {
        $accepted = $user->consents()
            ->get(['document', 'version'])
            ->map(fn ($consent): string => $consent->document->value.'@'.$consent->version)
            ->all();

        return array_values(array_filter(
            $this->requiredDocuments(),
            fn (LegalDocument $document): bool => ! in_array($document->value.'@'.$document->version(), $accepted, true),
        ));
    }

    /**
     * Whether the mentee accepted every required document in its current version.
     */
    public function hasCurrentConsent(User $user): bool
    {
        return $this->missingDocuments($user) === [];
    }

    /**
     * Record the acceptance of the current version of each document, skipping a version already logged from the same source.
     *
     * @param  array<int, LegalDocument>  $documents
     */
    public function record(User $user, array $documents, ConsentSource $source, ?bool $age_confirmed = null): void
    {
        foreach ($documents as $document) {
            $user->consents()->firstOrCreate(
                ['document' => $document, 'version' => $document->version(), 'source' => $source],
                ['age_confirmed' => $age_confirmed, 'accepted_at' => now()],
            );
        }
    }

    /**
     * A first Google sign-in when the account has a Google ID and no consent at all; otherwise a re-consent.
     */
    public function pendingSource(User $user): ConsentSource
    {
        return $user->google_id !== null && ! $user->consents()->exists()
            ? ConsentSource::Google
            : ConsentSource::Reconsent;
    }

    /**
     * A key that changes whenever a required document version changes.
     */
    public function versionKey(): string
    {
        return implode('|', array_map(
            fn (LegalDocument $document): string => $document->value.'@'.$document->version(),
            $this->requiredDocuments(),
        ));
    }
}
