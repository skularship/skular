<?php

namespace App\Providers;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\AdaptiveQuestionSelectorInterface;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\AnswerCheckerInterface;
use App\Contracts\BrainstormingSessionServiceInterface;
use App\Contracts\CheckoutServiceInterface;
use App\Contracts\ConsentServiceInterface;
use App\Contracts\CvServiceInterface;
use App\Contracts\EntitlementServiceInterface;
use App\Contracts\EventServiceInterface;
use App\Contracts\GoogleAuthServiceInterface;
use App\Contracts\LearningPathServiceInterface;
use App\Contracts\MentorQuotaServiceInterface;
use App\Contracts\ModuleBrainstormingServiceInterface;
use App\Contracts\ModuleFeedbackInsightsServiceInterface;
use App\Contracts\ModuleFeedbackServiceInterface;
use App\Contracts\ModuleMentorServiceInterface;
use App\Contracts\ModuleOutlineServiceInterface;
use App\Contracts\ModuleReadinessServiceInterface;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentWebhookServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Contracts\PricingServiceInterface;
use App\Contracts\ProductTourServiceInterface;
use App\Contracts\ProfilingServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Contracts\ReadinessScoringServiceInterface;
use App\Contracts\ReferralServiceInterface;
use App\Contracts\RichContentRendererInterface;
use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Contracts\ScholarshipImageServiceInterface;
use App\Contracts\ScholarshipMatchingServiceInterface;
use App\Contracts\ScholarshipPathServiceInterface;
use App\Contracts\ScholarshipReportServiceInterface;
use App\Contracts\SiteAnalyticsServiceInterface;
use App\Contracts\SpeakingAssessmentServiceInterface;
use App\Contracts\SubscriptionServiceInterface;
use App\Contracts\SupportTicketServiceInterface;
use App\Contracts\TryoutAnswerServiceInterface;
use App\Contracts\TryoutAttemptServiceInterface;
use App\Contracts\TryoutBankImportServiceInterface;
use App\Contracts\TryoutCatalogServiceInterface;
use App\Contracts\TryoutMentorQuotaServiceInterface;
use App\Contracts\TryoutMentorServiceInterface;
use App\Contracts\TryoutReadinessServiceInterface;
use App\Contracts\TryoutResultServiceInterface;
use App\Contracts\TryoutScoringServiceInterface;
use App\Contracts\TwibbonServiceInterface;
use App\Contracts\VaultServiceInterface;
use App\Contracts\WritingAssessmentServiceInterface;
use App\Filament\RichEditor\FontFamilyPlugin;
use App\Filament\RichEditor\FontSizePlugin;
use App\Services\ActivityTracker;
use App\Services\AdaptiveQuestionSelector;
use App\Services\AiFeedbackService;
use App\Services\AnswerChecker;
use App\Services\BrainstormingSessionService;
use App\Services\CheckoutService;
use App\Services\ConsentService;
use App\Services\CvService;
use App\Services\EntitlementService;
use App\Services\EventService;
use App\Services\GoogleAuthService;
use App\Services\LearningPathService;
use App\Services\MentorQuotaService;
use App\Services\MidtransGateway;
use App\Services\ModuleBrainstormingService;
use App\Services\ModuleFeedbackInsightsService;
use App\Services\ModuleFeedbackService;
use App\Services\ModuleMentorService;
use App\Services\ModuleOutlineService;
use App\Services\ModuleReadinessService;
use App\Services\PaymentWebhookService;
use App\Services\PracticeSubmissionService;
use App\Services\PricingService;
use App\Services\ProductTourService;
use App\Services\ProfilingService;
use App\Services\ProgressService;
use App\Services\ReadinessScoringService;
use App\Services\ReferralService;
use App\Services\RichContentRenderer;
use App\Services\ScholarshipApplicationService;
use App\Services\ScholarshipImageService;
use App\Services\ScholarshipMatchingService;
use App\Services\ScholarshipPathService;
use App\Services\ScholarshipReportService;
use App\Services\SiteAnalyticsService;
use App\Services\SpeakingAssessmentService;
use App\Services\SubscriptionService;
use App\Services\SupportTicketService;
use App\Services\TryoutAnswerService;
use App\Services\TryoutAttemptService;
use App\Services\TryoutBankImportService;
use App\Services\TryoutCatalogService;
use App\Services\TryoutMentorQuotaService;
use App\Services\TryoutMentorService;
use App\Services\TryoutReadinessService;
use App\Services\TryoutResultService;
use App\Services\TryoutScoringService;
use App\Services\TwibbonService;
use App\Services\VaultService;
use App\Services\WritingAssessmentService;
use App\Support\MaterialStyles;
use Carbon\CarbonImmutable;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Blaze\Blaze;

/**
 * Application bootstrapping: binds every App\Contracts interface to its
 * App\Services implementation, configures framework defaults, Blaze for Blade
 * components, and the optional slow request/query logging.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerServiceBindings();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPerformanceInstrumentation();
        $this->optimizeBladeComponents();
        $this->registerFilamentAssets();
    }

    /**
     * Register the Inti Materi font family and font size marks for the Filament rich editor.
     *
     * The scripts are loaded on request by FontFamilyPlugin and FontSizePlugin;
     * the font stacks and sizes are shared as window.filamentData.skularMaterialFonts
     * and skularMaterialFontSizes so the JS marks render the same whitelist as
     * the server (config/content.php).
     */
    protected function registerFilamentAssets(): void
    {
        FilamentAsset::register([
            Js::make(FontFamilyPlugin::SCRIPT_ID, resource_path('js/filament/rich-content-plugins/font-family.js'))->loadedOnRequest(),
            Js::make(FontSizePlugin::SCRIPT_ID, resource_path('js/filament/rich-content-plugins/font-size.js'))->loadedOnRequest(),
        ]);

        FilamentAsset::registerScriptData([
            'skularMaterialFonts' => array_map(fn (array $font): string => $font['stack'], MaterialStyles::fonts()),
            'skularMaterialFontSizes' => array_map(fn (array $size): string => $size['size'], MaterialStyles::fontSizes()),
        ], package: 'app');
    }

    /**
     * Compile the app's anonymous Blade components into plain PHP functions.
     *
     * Pages render 200-460 component instances (icons, pills, buttons) and
     * Blade's component factory dominated render time: measured on the
     * dashboard, p50 went from ~118ms to ~36ms. Only Blaze's "compile"
     * strategy is enabled; memoization and folding stay off because several
     * components read request state ($errors, Livewire). Vendor components
     * (Flux, Filament) are untouched. Set BLAZE_ENABLED=false to fall back
     * to plain Blade, then run `php artisan view:clear`.
     */
    protected function optimizeBladeComponents(): void
    {
        Blaze::optimize()->in(resource_path('views/components'));
    }

    /**
     * Log slow requests and (optionally) slow queries without private data.
     *
     * Only the path, status, duration and SQL text are recorded. Bindings,
     * request bodies, mentee answers and AI prompts are never logged here.
     */
    protected function registerPerformanceInstrumentation(): void
    {
        if (config('performance.log_slow_requests') === true) {
            $threshold = (int) config('performance.slow_request_ms', 1000);

            Event::listen(RequestHandled::class, function (RequestHandled $event) use ($threshold): void {
                // Prefer the per-request timestamp so long-running workers
                // (Octane, queue) never report the worker boot time.
                $started_at = $event->request->server('REQUEST_TIME_FLOAT')
                    ?? (defined('LARAVEL_START') ? LARAVEL_START : null);

                if (! is_numeric($started_at)) {
                    return;
                }

                $started_at = (float) $started_at;

                $duration_ms = (int) round((microtime(true) - $started_at) * 1000);

                if ($duration_ms < $threshold) {
                    return;
                }

                Log::warning('slow.request', [
                    'method' => $event->request->getMethod(),
                    'path' => $event->request->path(),
                    'status' => $event->response->getStatusCode(),
                    'duration_ms' => $duration_ms,
                ]);
            });
        }

        if (config('performance.log_slow_queries') === true) {
            $threshold = (int) config('performance.slow_query_ms', 200);

            DB::listen(function (QueryExecuted $query) use ($threshold): void {
                if ($query->time < $threshold) {
                    return;
                }

                Log::warning('slow.query', [
                    'connection' => $query->connectionName,
                    'sql' => $query->sql,
                    'duration_ms' => $query->time,
                ]);
            });
        }
    }

    /**
     * Bind every business service to its interface so consumers depend on abstractions.
     */
    protected function registerServiceBindings(): void
    {
        $this->app->bind(ActivityTrackerInterface::class, ActivityTracker::class);
        $this->app->bind(AiFeedbackServiceInterface::class, AiFeedbackService::class);
        $this->app->bind(CheckoutServiceInterface::class, CheckoutService::class);
        $this->app->bind(ConsentServiceInterface::class, ConsentService::class);
        $this->app->bind(PaymentGatewayInterface::class, MidtransGateway::class);
        $this->app->bind(PaymentWebhookServiceInterface::class, PaymentWebhookService::class);
        $this->app->bind(PricingServiceInterface::class, PricingService::class);
        $this->app->bind(EntitlementServiceInterface::class, EntitlementService::class);
        $this->app->bind(EventServiceInterface::class, EventService::class);
        $this->app->bind(ReferralServiceInterface::class, ReferralService::class);
        $this->app->bind(VaultServiceInterface::class, VaultService::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(SiteAnalyticsServiceInterface::class, SiteAnalyticsService::class);
        $this->app->bind(BrainstormingSessionServiceInterface::class, BrainstormingSessionService::class);
        $this->app->bind(CvServiceInterface::class, CvService::class);
        $this->app->bind(GoogleAuthServiceInterface::class, GoogleAuthService::class);
        $this->app->bind(LearningPathServiceInterface::class, LearningPathService::class);
        $this->app->bind(ModuleBrainstormingServiceInterface::class, ModuleBrainstormingService::class);
        $this->app->bind(ModuleFeedbackServiceInterface::class, ModuleFeedbackService::class);
        $this->app->bind(ModuleFeedbackInsightsServiceInterface::class, ModuleFeedbackInsightsService::class);
        $this->app->bind(MentorQuotaServiceInterface::class, MentorQuotaService::class);
        $this->app->bind(ModuleMentorServiceInterface::class, ModuleMentorService::class);
        $this->app->bind(ModuleOutlineServiceInterface::class, ModuleOutlineService::class);
        $this->app->bind(ModuleReadinessServiceInterface::class, ModuleReadinessService::class);
        $this->app->bind(PracticeSubmissionServiceInterface::class, PracticeSubmissionService::class);
        $this->app->bind(ProductTourServiceInterface::class, ProductTourService::class);
        $this->app->bind(ProfilingServiceInterface::class, ProfilingService::class);
        $this->app->bind(ProgressServiceInterface::class, ProgressService::class);
        $this->app->bind(ReadinessScoringServiceInterface::class, ReadinessScoringService::class);
        // Scoped: one CommonMark environment and memo per request/job, reset afterwards.
        $this->app->scoped(RichContentRendererInterface::class, RichContentRenderer::class);
        $this->app->bind(ScholarshipApplicationServiceInterface::class, ScholarshipApplicationService::class);
        $this->app->bind(ScholarshipImageServiceInterface::class, ScholarshipImageService::class);
        $this->app->bind(ScholarshipMatchingServiceInterface::class, ScholarshipMatchingService::class);
        $this->app->bind(ScholarshipPathServiceInterface::class, ScholarshipPathService::class);
        $this->app->bind(ScholarshipReportServiceInterface::class, ScholarshipReportService::class);
        $this->app->bind(SupportTicketServiceInterface::class, SupportTicketService::class);
        $this->app->bind(TwibbonServiceInterface::class, TwibbonService::class);

        // Try Out (simulasi IELTS): domain separate from the module flow.
        $this->app->bind(AdaptiveQuestionSelectorInterface::class, AdaptiveQuestionSelector::class);
        $this->app->bind(AnswerCheckerInterface::class, AnswerChecker::class);
        $this->app->bind(SpeakingAssessmentServiceInterface::class, SpeakingAssessmentService::class);
        $this->app->bind(TryoutAnswerServiceInterface::class, TryoutAnswerService::class);
        $this->app->bind(TryoutAttemptServiceInterface::class, TryoutAttemptService::class);
        $this->app->bind(TryoutBankImportServiceInterface::class, TryoutBankImportService::class);
        $this->app->bind(TryoutCatalogServiceInterface::class, TryoutCatalogService::class);
        $this->app->bind(TryoutMentorQuotaServiceInterface::class, TryoutMentorQuotaService::class);
        $this->app->bind(TryoutMentorServiceInterface::class, TryoutMentorService::class);
        $this->app->bind(TryoutReadinessServiceInterface::class, TryoutReadinessService::class);
        $this->app->bind(TryoutResultServiceInterface::class, TryoutResultService::class);
        $this->app->bind(TryoutScoringServiceInterface::class, TryoutScoringService::class);
        $this->app->bind(WritingAssessmentServiceInterface::class, WritingAssessmentService::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
