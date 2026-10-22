<?php

namespace App\Contracts;

use App\Exceptions\TwibbonException;
use App\Models\ScholarshipApplication;
use App\Models\TwibbonTemplate;
use App\Models\User;

interface TwibbonServiceInterface
{
    /**
     * Render a twibbon for the mentee and return its public disk path.
     *
     * @throws TwibbonException
     */
    public function generate(
        User $user,
        ScholarshipApplication $application,
        TwibbonTemplate $template,
    ): string;
}
