<?php

namespace App\Contracts;

use App\Data\ModuleFeedbackSummaryData;

/**
 * Read-only aggregates of the mandatory end-of-module feedback for the admin
 * dashboard: how helpful modules are, which parts mentees find hardest, which
 * modules need attention, and the latest low-score reasons.
 */
interface ModuleFeedbackInsightsServiceInterface
{
    /**
     * Summarise the feedback sent with the mandatory form (rows with a helpfulness score).
     *
     * @param  int  $days  Only feedback sent in the last N days; 0 means all time.
     * @param  int|null  $module_id  Limit to one module; null means every module.
     * @return ModuleFeedbackSummaryData The totals, distributions, module ranking and recent low scores.
     */
    public function summary(int $days, ?int $module_id = null): ModuleFeedbackSummaryData;

    /**
     * The modules that already received feedback, for the widget's module filter.
     *
     * @return array<int, string> Module titles keyed by module id.
     */
    public function modulesWithFeedback(): array;
}
