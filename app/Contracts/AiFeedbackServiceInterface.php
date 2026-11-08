<?php

namespace App\Contracts;

use App\Data\AiFeedbackData;
use App\Exceptions\AiFeedbackException;
use App\Models\Module;
use App\Models\PracticeForm;

/**
 * Asks the AI evaluator to grade one practice submission against the module rubric.
 *
 * Implemented by AiFeedbackService (PracticeFeedbackAgent). The model always
 * comes from config/ai.php.
 */
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
