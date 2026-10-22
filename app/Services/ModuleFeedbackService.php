<?php

namespace App\Services;

use App\Contracts\ModuleFeedbackServiceInterface;
use App\Models\Module;
use App\Models\ModuleFeedback;
use App\Models\User;

class ModuleFeedbackService implements ModuleFeedbackServiceInterface
{
    /**
     * The rating options offered to mentees after finishing a module.
     *
     * @var array<int, string>
     */
    public const RATING_OPTIONS = [
        'Materi mudah dipahami',
        'Materi relevan',
        'AI feedback membantu',
        'Durasi sesuai',
        'Praktik membantu',
    ];

    /**
     * Store or update the mentee's feedback for a module.
     *
     * @param  array<int, string>  $ratings
     */
    public function save(User $user, Module $module, array $ratings, ?string $suggestion): ModuleFeedback
    {
        $allowed = array_values(array_intersect(self::RATING_OPTIONS, $ratings));

        return ModuleFeedback::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'module_id' => $module->id,
            ],
            [
                'ratings' => $allowed,
                'suggestion' => $suggestion === null || trim($suggestion) === '' ? null : trim($suggestion),
            ],
        );
    }
}
