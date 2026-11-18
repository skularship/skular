<?php

namespace App\Filament\Widgets;

use App\Contracts\ModuleFeedbackInsightsServiceInterface;
use App\Filament\Resources\ModuleFeedback\ModuleFeedbackResource;
use Filament\Widgets\Widget;

/**
 * "Ringkasan Feedback Modul" on the admin dashboard: how helpful modules are,
 * how many mentees picked each hardest part, which modules need attention,
 * and the latest low-score reasons, filterable by time window and module.
 */
class ModuleFeedbackSummary extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.module-feedback-summary';

    /**
     * Time window in days (7, 30, 90) or 0 for all time, chosen in the widget header.
     */
    public int $days = 30;

    /**
     * Selected module id as a string ("" = every module), bound to the header select.
     */
    public string $module = '';

    /**
     * Only admins who may list module feedback see the summary.
     */
    public static function canView(): bool
    {
        return ModuleFeedbackResource::canViewAny();
    }

    /**
     * Keep the window to the offered ranges.
     */
    public function updatedDays(): void
    {
        $this->days = in_array($this->days, [0, 7, 30, 90], true) ? $this->days : 30;
    }

    /**
     * Pass the summary, the module options, and the feedback list URL to the view.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $insights = app(ModuleFeedbackInsightsServiceInterface::class);
        $modules = $insights->modulesWithFeedback();
        $module_id = ctype_digit($this->module) && isset($modules[(int) $this->module]) ? (int) $this->module : null;

        return [
            'summary' => $insights->summary($this->days, $module_id),
            'modules' => $modules,
            'list_url' => ModuleFeedbackResource::getUrl('index'),
        ];
    }
}
