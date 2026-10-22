<?php

namespace App\Services;

use App\Contracts\BrainstormingSessionServiceInterface;
use App\Data\BrainstormOutlineData;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserBrainstormingSession;

class BrainstormingSessionService implements BrainstormingSessionServiceInterface
{
    public function __construct(
        private readonly BrainstormingConfigService $config,
    ) {}

    /**
     * Get the stored session for one mentee, module and practice form.
     */
    public function find(User $user, Module $module, PracticeForm $practice_form): ?UserBrainstormingSession
    {
        return UserBrainstormingSession::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('practice_form_id', $practice_form->id)
            ->first();
    }

    /**
     * Persist the mentee's current answers without touching the outline.
     *
     * @param  array<string, mixed>  $answers
     */
    public function saveAnswers(User $user, Module $module, PracticeForm $practice_form, array $answers): UserBrainstormingSession
    {
        return $this->store($user, $module, $practice_form, [
            'answers' => $this->config->sanitizeAnswers($module, $answers),
        ]);
    }

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
    ): UserBrainstormingSession {
        return $this->store($user, $module, $practice_form, [
            'answers' => $this->config->sanitizeAnswers($module, $answers),
            'outline' => $outline->toArray(),
            'summary' => $summary,
        ]);
    }

    /**
     * Remove the stored session when the mentee starts over.
     */
    public function reset(User $user, Module $module, PracticeForm $practice_form): void
    {
        UserBrainstormingSession::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('practice_form_id', $practice_form->id)
            ->delete();
    }

    /**
     * Upsert the one stored session for this mentee and practice form.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function store(User $user, Module $module, PracticeForm $practice_form, array $attributes): UserBrainstormingSession
    {
        return UserBrainstormingSession::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'practice_form_id' => $practice_form->id,
            ],
            [
                'module_id' => $module->id,
                ...$attributes,
            ],
        );
    }
}
