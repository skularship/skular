<?php

namespace App\Contracts;

use App\Data\ModuleOutlineStepData;
use App\Models\Module;

interface ModuleOutlineServiceInterface
{
    /**
     * Get the learning loop steps of a module, in the order mentees see them.
     *
     * @return array<int, ModuleOutlineStepData>
     */
    public function steps(Module $module): array;

    /**
     * Get the step labels keyed by step key.
     *
     * @return array<string, string>
     */
    public function stepLabels(Module $module): array;
}
