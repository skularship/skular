<?php

namespace App\Contracts;

use App\Enums\ConsentSource;
use App\Enums\LegalDocument;
use App\Models\User;

/**
 * Records which legal document versions a mentee accepted, and tells whether
 * they still need to accept the current Terms and Privacy Policy.
 */
interface ConsentServiceInterface
{
    /**
     * The documents every signed-in mentee must have accepted in their current version.
     *
     * @return array<int, LegalDocument>
     */
    public function requiredDocuments(): array;

    /**
     * The required documents whose current version the mentee has not accepted.
     *
     * @return array<int, LegalDocument>
     */
    public function missingDocuments(User $user): array;

    /**
     * Whether the mentee accepted every required document in its current version.
     */
    public function hasCurrentConsent(User $user): bool;

    /**
     * Record the acceptance of the current version of each document.
     *
     * A version already logged from the same source is skipped, so repeated calls (e.g. a second checkout) add no duplicate rows.
     *
     * @param  array<int, LegalDocument>  $documents
     * @param  bool|null  $age_confirmed  The 18+ or parent/guardian statement, null when it was not asked.
     */
    public function record(User $user, array $documents, ConsentSource $source, ?bool $age_confirmed = null): void;

    /**
     * The source to log on the consent screen: a first Google sign-in, or a re-consent.
     */
    public function pendingSource(User $user): ConsentSource;

    /**
     * A key that changes whenever a required document version changes (for the per-session cache).
     */
    public function versionKey(): string;
}
