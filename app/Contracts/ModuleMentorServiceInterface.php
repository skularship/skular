<?php

namespace App\Contracts;

use App\Data\MentorReplyData;
use App\Models\Module;
use App\Models\PracticeForm;

/**
 * Answers mentee questions about a module while they work on a practice form.
 */
interface ModuleMentorServiceInterface
{
    /**
     * Answer a mentee question using the module context and bounded chat history.
     *
     * @param  array<int, array<string, mixed>>  $history
     */
    public function reply(
        Module $module,
        ?PracticeForm $practice_form,
        string $question,
        array $history = [],
        ?string $active_field = null,
    ): MentorReplyData;
}
