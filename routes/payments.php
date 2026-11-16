<?php

use App\Http\Controllers\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

// Midtrans payment notification (set this URL in the Midtrans dashboard).
// Loaded outside the web middleware group, see bootstrap/app.php.
Route::post('payments/midtrans/notification', MidtransWebhookController::class)->name('payments.midtrans.notification');
