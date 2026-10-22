<?php

namespace App\Contracts;

use App\Data\ModuleReadinessCheckData;
use App\Models\Module;

interface ModuleReadinessServiceInterface
{
    /**
     * Evaluate every completeness requirement of a module.
     *
     * @return array<int, ModuleReadinessCheckData>
     */
    public function checks(Module $module): array;

    /**
     * Get how complete the module is as a percentage of passed checks.
     */
    public function progress(Module $module): int;

    /**
     * Determine whether the module satisfies every required check.
     */
    public function isReady(Module $module): bool;

    /**
     * Get the required checks that still fail, in checklist order.
     *
     * @return array<int, ModuleReadinessCheckData>
     */
    public function blockers(Module $module): array;
}
