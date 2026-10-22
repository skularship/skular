<?php

namespace App\Contracts;

use App\Data\BrainstormOutlineData;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserBrainstormingSession;

/**
 * Stores and restores a mentee's brainstorming answers and AI outline.
 */
interface BrainstormingSessionServiceInterface
{
    /**
     * Get the stored session for one mentee, module and practice form.
     */
    public function find(User $user, Module $module, PracticeForm $practice_form): ?UserBrainstormingSession;

    /**
     * Persist the mentee's current answers without touching the outline.
     *
     * @param  array<string, mixed>  $answers
     */
    public function saveAnswers(User $user, Module $module, PracticeForm $practice_form, array $answers): UserBrainstormingSession;

    /**
     * Persist the answers together with the generated outline.
     *
     * @param  array<string, mixed>  $answers
     */
    public function saveOutline(
        User $user,
        Module $module,
        PracticeForm $practice_form,
        array $answers,
        BrainstormOutlineData $outline,
        string $summary,
    ): UserBrainstormingSession;

    /**
     * Remove the stored session when the mentee starts over.
     */
    public function reset(User $user, Module $module, PracticeForm $practice_form): void;
}
