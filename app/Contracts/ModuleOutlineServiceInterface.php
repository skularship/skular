<?php

namespace App\Contracts;

use App\Data\ModuleOutlineStepData;
use App\Models\Module;

/**
 * Single source of truth for the learning loop steps of a module
 * (opening, pahami, quick, praktik, checkpoint), shared by the mentee player and the admin preview.
 */
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
