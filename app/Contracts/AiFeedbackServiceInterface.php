<?php

namespace App\Contracts;

use App\Data\AiFeedbackData;
use App\Exceptions\AiFeedbackException;
use App\Models\Module;
use App\Models\PracticeForm;

interface AiFeedbackServiceInterface
{
    /**
     * Generate structured learning feedback for a practice submission.
     *
     * @param  array<string, mixed>  $answers
     *
     * @throws AiFeedbackException
     */
    public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData;
}
