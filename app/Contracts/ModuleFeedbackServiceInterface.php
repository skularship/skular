<?php

namespace App\Contracts;

use App\Models\Module;
use App\Models\ModuleFeedback;
use App\Models\User;

interface ModuleFeedbackServiceInterface
{
    /**
     * Store or update the mentee's feedback for a module.
     *
     * @param  array<int, string>  $ratings
     */
    public function save(User $user, Module $module, array $ratings, ?string $suggestion): ModuleFeedback;
}
