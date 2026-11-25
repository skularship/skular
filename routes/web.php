<?php

use App\Enums\LegalDocument;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\EventCalendarController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SubmissionProofController;
use App\Http\Controllers\TryoutTheoryPdfController;
use App\Http\Controllers\VaultDownloadController;
use App\Livewire\Checklist\Show as ChecklistShow;
use App\Livewire\Cv\Show as CvShow;
use App\Livewire\Dashboard;
use App\Livewire\Events\Index as EventsIndex;
use App\Livewire\Events\Show as EventShow;
use App\Livewire\Faq\Index as FaqIndex;
use App\Livewire\Learning\FeedbackCommitment;
use App\Livewire\Learning\Library;
use App\Livewire\Learning\ModulePlayer;
use App\Livewire\Legal\Consent as LegalConsent;
use App\Livewire\Onboarding\Guide;
use App\Livewire\Premium\Billing as PremiumBilling;
use App\Livewire\Premium\Index as PremiumIndex;
use App\Livewire\Premium\Status as PremiumStatus;
use App\Livewire\Profiling\Wizard;
use App\Livewire\Roadmap;
use App\Livewire\Scholarships\Index as ScholarshipsIndex;
use App\Livewire\Scholarships\Recommendation;
use App\Livewire\Scholarships\Show as ScholarshipShow;
use App\Livewire\Scholarships\Tracker;
use App\Livewire\Support\Help;
use App\Livewire\Tryout\Index as TryoutIndex;
use App\Livewire\Tryout\Result as TryoutResult;
use App\Livewire\Tryout\Review as TryoutReview;
use App\Livewire\Tryout\Runner as TryoutRunner;
use App\Livewire\Tryout\Show as TryoutShow;
use App\Livewire\Tryout\Theory as TryoutTheory;
use App\Livewire\Twibbon\Generator;
use App\Livewire\Vault\Index as VaultIndex;
use Illuminate\Support\Facades\Route;

// Under construction switch (UNDER_CONSTRUCTION, config/app.php). Read once
// when the routes load, so `php artisan optimize` is required after changing it.
// The affected routes stay registered under their own names (route('home'),
// route('dashboard') keep resolving); they only gain the under.construction
// middleware. Health, legal pages, Google sign-in, Fortify, Filament (/admin),
// /up and the Midtrans webhook (routes/payments.php) are never wrapped.
$under_construction = (bool) config('app.under_construction');
$under_construction_middleware = $under_construction ? ['under.construction'] : [];

Route::get('health', HealthCheckController::class)->name('health');

// Legal pages: static Blade views (changes go through commits), always public,
// also while PREMIUM_ENABLED is false. Texts read config/legal.php.
foreach (LegalDocument::cases() as $legalDocument) {
    Route::view($legalDocument->path(), $legalDocument->view())->name($legalDocument->routeName());
}

Route::middleware('throttle:30,1')->group(function () {
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// Public and mentee pages: show the under construction page while the switch is on.
Route::middleware($under_construction_middleware)->group(function () {
    Route::view('/', 'welcome')->name('home');

    // Only pages reachable without authenticating are listed. Scholarship and
    // module pages sit behind auth + verified, so crawlers would only see a login
    // redirect; listing them would waste crawl budget and leak no value.
    Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

    Route::livewire('faq', FaqIndex::class)->name('faq.index');

    // Public price page; returns 404 until PREMIUM_ENABLED is true.
    Route::livewire('premium', PremiumIndex::class)->name('premium');

    // Consent screen for mentees without consent to the current Terms/Privacy
    // (first Google sign-in, accounts from before the consent log, new versions).
    Route::livewire('persetujuan', LegalConsent::class)->middleware('auth')->name('legal.consent');

    Route::middleware(['auth', 'verified', 'legal.consent'])->group(function () {
        Route::livewire('profiling', Wizard::class)->name('profiling');
        Route::livewire('profiling/recommendation', Recommendation::class)->name('profiling.recommendation');
        Route::livewire('dashboard', Dashboard::class)->name('dashboard');
        Route::livewire('roadmap', Roadmap::class)->name('roadmap.show');
        Route::livewire('modules/{module}', ModulePlayer::class)->name('modules.show');
        Route::livewire('akses-gratis', FeedbackCommitment::class)->name('feedback.commitment');
        Route::livewire('library', Library::class)->name('library.index');
        Route::livewire('scholarships', ScholarshipsIndex::class)->name('scholarships.index');
        Route::livewire('scholarships/saya', Tracker::class)->name('scholarships.tracker');
        Route::livewire('scholarships/{application}/twibbon', Generator::class)->name('twibbon.show');
        Route::livewire('scholarships/{scholarship}', ScholarshipShow::class)->name('scholarships.show');
        Route::livewire('cv', CvShow::class)->name('cv.show');
        Route::livewire('bantuan', Help::class)->name('support.index');
        Route::livewire('pembayaran', PremiumBilling::class)->name('payment.index');
        Route::livewire('pembayaran/{order}', PremiumStatus::class)->name('payment.status');
        Route::livewire('vault', VaultIndex::class)->name('vault.index');
        Route::get('vault/{document}/unduh', VaultDownloadController::class)->name('vault.download');
        Route::livewire('scholarships/{application}/checklist', ChecklistShow::class)->name('checklist.show');
        Route::livewire('panduan', Guide::class)->name('guide.index');
        Route::livewire('event', EventsIndex::class)->name('events.index');
        Route::livewire('event/{event}', EventShow::class)->name('events.show');
        Route::get('event/{event}/kalender', EventCalendarController::class)->name('events.calendar');
        Route::get('scholarships/{application}/proof', [SubmissionProofController::class, 'show'])->name('applications.proof');

        // Try Out (simulasi IELTS). Ownership and Pro access are checked in each component/controller.
        Route::livewire('tryout', TryoutIndex::class)->name('tryout.index');
        Route::livewire('tryout/sesi/{attempt}', TryoutRunner::class)->name('tryout.runner');
        Route::livewire('tryout/hasil/{attempt}', TryoutResult::class)->name('tryout.result');
        Route::livewire('tryout/hasil/{attempt}/pembahasan', TryoutReview::class)->name('tryout.review');
        Route::livewire('tryout/{tryout}/teori', TryoutTheory::class)->name('tryout.theory');
        // Masked bytes for the in-page PDF viewer only (Pro); the PDF is never downloadable.
        Route::get('tryout/{tryout}/teori/berkas', TryoutTheoryPdfController::class)->middleware('throttle:30,1')->name('tryout.theory.pdf');
        Route::livewire('tryout/{tryout}', TryoutShow::class)->name('tryout.show');
    });

    require __DIR__.'/settings.php';
});
