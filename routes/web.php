<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\SubmissionProofController;
use App\Livewire\Cv\Show as CvShow;
use App\Livewire\Dashboard;
use App\Livewire\Faq\Index as FaqIndex;
use App\Livewire\Learning\Library;
use App\Livewire\Learning\ModulePlayer;
use App\Livewire\Profiling\Wizard;
use App\Livewire\Scholarships\Index as ScholarshipsIndex;
use App\Livewire\Scholarships\Recommendation;
use App\Livewire\Scholarships\Show as ScholarshipShow;
use App\Livewire\Scholarships\Tracker;
use App\Livewire\Twibbon\Generator;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('health', HealthCheckController::class)->name('health');

Route::livewire('faq', FaqIndex::class)->name('faq.index');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('profiling', Wizard::class)->name('profiling');
    Route::livewire('profiling/recommendation', Recommendation::class)->name('profiling.recommendation');
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('modules/{module}', ModulePlayer::class)->name('modules.show');
    Route::livewire('library', Library::class)->name('library.index');
    Route::livewire('scholarships', ScholarshipsIndex::class)->name('scholarships.index');
    Route::livewire('scholarships/saya', Tracker::class)->name('scholarships.tracker');
    Route::livewire('scholarships/{application}/twibbon', Generator::class)->name('twibbon.show');
    Route::livewire('scholarships/{scholarship}', ScholarshipShow::class)->name('scholarships.show');
    Route::livewire('cv', CvShow::class)->name('cv.show');
    Route::get('scholarships/{application}/proof', [SubmissionProofController::class, 'show'])->name('applications.proof');
});

require __DIR__.'/settings.php';
