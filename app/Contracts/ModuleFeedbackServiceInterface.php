<?php

namespace App\Contracts;

use App\Models\Module;
use App\Models\ModuleFeedback;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The mandatory end-of-module feedback (helpfulness 1-5, hardest part, one
 * open question, reason for a low score) and the free-access commitment a
 * mentee accepts before opening modules.
 */
interface ModuleFeedbackServiceInterface
{
    /**
     * Validate and store the mentee's feedback for a module (one row per mentee per module).
     *
     * @param  array{helpful_score?: mixed, difficulty?: mixed, open_answer?: mixed, low_score_reason?: mixed}  $input
     *
     * @throws ValidationException When a field is missing, too short, or only repeated characters.
     */
    public function submit(User $user, Module $module, array $input): ModuleFeedback;

    /**
     * Whether the mentee already sent the mandatory feedback (with a helpfulness score) for the module.
     */
    public function hasSubmitted(User $user, Module $module): bool;

    /**
     * The open question shown for this module: the admin's question, or a rotating default.
     */
    public function openQuestionFor(Module $module): string;

    /**
     * Record that the mentee agreed to the free-access feedback commitment (idempotent).
     */
    public function acceptCommitment(User $user): void;
}
