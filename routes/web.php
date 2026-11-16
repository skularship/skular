<?php

use App\Enums\LegalDocument;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SubmissionProofController;
use App\Http\Controllers\VaultDownloadController;
use App\Livewire\Checklist\Show as ChecklistShow;
use App\Livewire\Cv\Show as CvShow;
use App\Livewire\Dashboard;
use App\Livewire\Faq\Index as FaqIndex;
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
use App\Livewire\Twibbon\Generator;
use App\Livewire\Vault\Index as VaultIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('health', HealthCheckController::class)->name('health');

// Only pages reachable without authenticating are listed. Scholarship and
// module pages sit behind auth + verified, so crawlers would only see a login
// redirect; listing them would waste crawl budget and leak no value.
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::livewire('faq', FaqIndex::class)->name('faq.index');

// Legal pages: static Blade views (changes go through commits), always public,
// also while PREMIUM_ENABLED is false. Texts read config/legal.php.
foreach (LegalDocument::cases() as $legalDocument) {
    Route::view($legalDocument->path(), $legalDocument->view())->name($legalDocument->routeName());
}

// Public price page; returns 404 until PREMIUM_ENABLED is true.
Route::livewire('premium', PremiumIndex::class)->name('premium');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// Consent screen for mentees without consent to the current Terms/Privacy
// (first Google sign-in, accounts from before the consent log, new versions).
Route::livewire('persetujuan', LegalConsent::class)->middleware('auth')->name('legal.consent');

Route::middleware(['auth', 'verified', 'legal.consent'])->group(function () {
    Route::livewire('profiling', Wizard::class)->name('profiling');
    Route::livewire('profiling/recommendation', Recommendation::class)->name('profiling.recommendation');
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('roadmap', Roadmap::class)->name('roadmap.show');
    Route::livewire('modules/{module}', ModulePlayer::class)->name('modules.show');
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
    Route::get('scholarships/{application}/proof', [SubmissionProofController::class, 'show'])->name('applications.proof');
});

require __DIR__.'/settings.php';
