<?php

namespace App\Contracts;

use App\Data\BrainstormResultData;
use App\Models\Module;
use App\Models\PracticeForm;

/**
 * Reads admin-configured brainstorming questions and asks the AI for outlines.
 */
interface ModuleBrainstormingServiceInterface
{
    /**
     * Get the questions configured for the module without calling the AI.
     */
    public function questions(Module $module, PracticeForm $practice_form): BrainstormResultData;

    /**
     * Build a draft outline from validated mentee answers.
     *
     * @param  array<string, mixed>  $answers  Answers keyed by question identifier.
     */
    public function summary(Module $module, PracticeForm $practice_form, array $answers): BrainstormResultData;
}
