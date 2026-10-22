<?php

namespace App\Providers;

use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\BrainstormingSessionServiceInterface;
use App\Contracts\CvServiceInterface;
use App\Contracts\GoogleAuthServiceInterface;
use App\Contracts\LearningPathServiceInterface;
use App\Contracts\ModuleBrainstormingServiceInterface;
use App\Contracts\ModuleFeedbackServiceInterface;
use App\Contracts\ModuleMentorServiceInterface;
use App\Contracts\ModuleOutlineServiceInterface;
use App\Contracts\ModuleReadinessServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Contracts\ProfilingServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Contracts\ReadinessScoringServiceInterface;
use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Contracts\ScholarshipMatchingServiceInterface;
use App\Contracts\ScholarshipPathServiceInterface;
use App\Contracts\TwibbonServiceInterface;
use App\Services\AiFeedbackService;
use App\Services\BrainstormingSessionService;
use App\Services\CvService;
use App\Services\GoogleAuthService;
use App\Services\LearningPathService;
use App\Services\ModuleBrainstormingService;
use App\Services\ModuleFeedbackService;
use App\Services\ModuleMentorService;
use App\Services\ModuleOutlineService;
use App\Services\ModuleReadinessService;
use App\Services\PracticeSubmissionService;
use App\Services\ProfilingService;
use App\Services\ProgressService;
use App\Services\ReadinessScoringService;
use App\Services\ScholarshipApplicationService;
use App\Services\ScholarshipMatchingService;
use App\Services\ScholarshipPathService;
use App\Services\TwibbonService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->app->bind(AiFeedbackServiceInterface::class, AiFeedbackService::class);
        $this->app->bind(BrainstormingSessionServiceInterface::class, BrainstormingSessionService::class);
        $this->app->bind(CvServiceInterface::class, CvService::class);
        $this->app->bind(GoogleAuthServiceInterface::class, GoogleAuthService::class);
        $this->app->bind(LearningPathServiceInterface::class, LearningPathService::class);
        $this->app->bind(ModuleBrainstormingServiceInterface::class, ModuleBrainstormingService::class);
        $this->app->bind(ModuleFeedbackServiceInterface::class, ModuleFeedbackService::class);
        $this->app->bind(ModuleMentorServiceInterface::class, ModuleMentorService::class);
        $this->app->bind(ModuleOutlineServiceInterface::class, ModuleOutlineService::class);
        $this->app->bind(ModuleReadinessServiceInterface::class, ModuleReadinessService::class);
        $this->app->bind(PracticeSubmissionServiceInterface::class, PracticeSubmissionService::class);
        $this->app->bind(ProfilingServiceInterface::class, ProfilingService::class);
        $this->app->bind(ProgressServiceInterface::class, ProgressService::class);
        $this->app->bind(ReadinessScoringServiceInterface::class, ReadinessScoringService::class);
        $this->app->bind(ScholarshipApplicationServiceInterface::class, ScholarshipApplicationService::class);
        $this->app->bind(ScholarshipMatchingServiceInterface::class, ScholarshipMatchingService::class);
        $this->app->bind(ScholarshipPathServiceInterface::class, ScholarshipPathService::class);
        $this->app->bind(TwibbonServiceInterface::class, TwibbonService::class);
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
