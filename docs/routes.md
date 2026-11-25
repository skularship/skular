# Routes Skular

Sumber kebenaran: `routes/web.php`, `routes/settings.php`, route Fortify/passkeys (paket), dan panel Filament. Tabel di bagian 2–6 dihasilkan dari `php artisan route:list --json` (middleware diringkas: `web` tidak ditulis). Untuk melihat ulang: `php artisan route:list --except-vendor`.

Skular adalah aplikasi **Livewire server-rendered**, bukan API JSON. Aksi di halaman (klik tombol, ubah input) dikirim ke endpoint Livewire `POST /livewire-{hash}/update`; `{hash}` diturunkan dari `APP_KEY` sehingga berbeda per environment. Setiap aksi divalidasi dan diotorisasi ulang di server.

## 1. Fungsi route aplikasi

| URL | Fungsi | Catatan akses |
|---|---|---|
| `/` | Landing page + Profiling Kilat (tanpa login, tidak disimpan) | Publik |
| `/faq` | FAQ publik | Publik (layout `layouts.public`) |
| `/auth/google/redirect`, `/auth/google/callback` | Login/daftar dengan Google | Publik, throttle 30/menit |
| `/profiling` | Wizard profiling 8 langkah (`?step=1..8`) | auth + verified |
| `/profiling/recommendation` | Rekomendasi beasiswa, pilih ≤ 5 + 1 utama | auth + verified |
| `/dashboard` | Ringkasan progres, beasiswa aktif, kartu ringkas roadmap (fase aktif) | auth + verified |
| `/roadmap` | Roadmap Belajar: fase selesai, fase aktif + modulnya, fase berikutnya (nama saja), fase lain tertutup awan | auth + verified |
| `/modules/{module}` | Module player (`?section=opening\|pahami\|quick\|praktik\|checkpoint`); draf → 404, modul terkunci → 403, checkpoint dialihkan ke praktik bila gate belum terpenuhi | auth + verified |
| `/akses-gratis` | Komitmen akses gratis ("Saya setuju" memberi masukan jujur di akhir setiap modul). `?modul={id}` = lanjut ke modul itu setelah setuju; tanpa parameter lanjut ke rekomendasi | auth + verified |
| `/event` | Daftar event (`?tab=rekaman` untuk rekaman) | auth + verified |
| `/event/{event:slug}` | Detail event, daftar (+ pertanyaan singkat), batal, link acara H-1 untuk peserta, masukan 1-5 setelah acara; draf/batal/di luar audiens → 404 | auth + verified |
| `/event/{event:slug}/kalender` | Unduh file .ics (tanpa link acara) | auth + verified |
| `/library` | Modul Perpustakaan (opsional) | auth + verified |
| `/scholarships` | Katalog beasiswa (`?q`, `?jenjang`, `?cakupan`, `?lokasi`, `?ditutup`, `?urut`, `?page`) | auth + verified |
| `/scholarships/saya` | Tracker "Beasiswa Saya" | auth + verified |
| `/scholarships/{scholarship}` | Detail beasiswa (draf/arsip → 404) | auth + verified |
| `/scholarships/{application}/twibbon` | Generator twibbon untuk satu aplikasi | auth + verified, pemilik |
| `/scholarships/{application}/proof` | Unduh bukti pendaftaran (disk privat) | auth + verified, pemilik atau admin |
| `/cv` | CV dari jawaban praktik | auth + verified |
| `/settings` → `/settings/profile` | Pengaturan profil | auth |
| `/settings/security` | Password, passkey, 2FA | auth + verified + konfirmasi password |
| `/sitemap.xml` | Sitemap halaman publik | Publik |
| `/up`, `/health` | Liveness / readiness (DB + cache, 503 bila gagal) | Publik |
| `/admin/**` | Panel admin Filament | Admin (Shield) |
| `/admin/modules/{record}/preview` | Pratinjau modul seperti dilihat mentee (read-only) | Admin |

## 1a. Mode under construction

Saat `UNDER_CONSTRUCTION=true` (`config('app.under_construction')`, berlaku setelah `php artisan optimize`), `routes/web.php` menambahkan middleware `under.construction` (`App\Http\Middleware\ShowUnderConstruction`) ke grup route publik/mentee. Nama, path, dan middleware asli tidak berubah, jadi `route('home')`, `route('dashboard')`, dst. tetap bisa di-resolve. Middleware ini berjalan sebelum `auth` (daftar prioritas di `bootstrap/app.php`) dan mengembalikan view `under-construction` dengan status 503 + `Retry-After: 3600`, atau JSON 503 untuk request JSON.

| Terdampak (halaman "Skular sedang diperbarui") | Tetap normal |
|---|---|
| `/`, `/faq`, `/premium`, `/sitemap.xml` | `/up`, `/health` |
| `/persetujuan` | `POST /payments/midtrans/notification` (`routes/payments.php`) |
| Seluruh grup `auth` + `verified` + `legal.consent` (dashboard, profiling, roadmap, modul, beasiswa, cv, vault, event, pembayaran, dll.) | Panel Filament `/admin/**` termasuk `/admin/login` |
| `routes/settings.php` (`/settings/*`, `/.well-known/passkey-endpoints`) | Empat halaman legal |
| | Route Fortify (login, daftar, reset password, verifikasi email) dan `/auth/google/*` |
| | Endpoint Livewire `POST /livewire-*/update` (dipakai Filament; tab mentee yang sudah terbuka masih bisa mengirim aksi) |

## 2. Halaman mentee & publik

| Method | URL | Nama route | Handler | Middleware |
|---|---|---|---|---|
| GET | `/` | home | `view welcome` (Route::view) | — |
| GET | `/auth/google/callback` | auth.google.callback | `Http\Controllers\Auth\GoogleAuthController@callback` | `throttle:30,1` |
| GET | `/auth/google/redirect` | auth.google.redirect | `Http\Controllers\Auth\GoogleAuthController@redirect` | `throttle:30,1` |
| GET | `/cv` | cv.show | `Livewire\Cv\Show (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/dashboard` | dashboard | `Livewire\Dashboard (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/premium` | premium | `Livewire\Premium\Index (Livewire page)` | — (404 bila `PREMIUM_ENABLED=false`) |
| GET | `/pembayaran` | payment.index | `Livewire\Premium\Billing (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/pembayaran/{order}` | payment.status | `Livewire\Premium\Status (Livewire page, hanya pemilik order)` | `auth`, `verified`, `legal.consent` |
| GET | `/vault` | vault.index | `Livewire\Vault\Index (Livewire page)` | `auth`, `verified`, `legal.consent` (404 bila Premium mati) |
| GET | `/vault/{document}/unduh` | vault.download | `Http\Controllers\VaultDownloadController` | `auth`, `verified`, `legal.consent` (hanya pemilik) |
| GET | `/scholarships/{application}/checklist` | checklist.show | `Livewire\Checklist\Show (Livewire page)` | `auth`, `verified`, `legal.consent` (hanya pemilik, Pro untuk mengubah) |
| POST | `/payments/midtrans/notification` | payments.midtrans.notification | `Http\Controllers\MidtransWebhookController` | `throttle:120,1` (tanpa CSRF/session, dimuat dari `routes/payments.php`) |
| GET | `/faq` | faq.index | `Livewire\Faq\Index (Livewire page)` | — |
| GET | `/kebijakan-privasi` | legal.privacy | `view legal.privacy` (Route::view, loop `LegalDocument`) | — (tetap tampil walau Premium mati) |
| GET | `/syarat-ketentuan` | legal.terms | `view legal.terms` | — |
| GET | `/kebijakan-cookie` | legal.cookies | `view legal.cookies` | — |
| GET | `/kebijakan-pengembalian-dana` | legal.refund | `view legal.refund` | — |
| GET | `/persetujuan` | legal.consent | `Livewire\Legal\Consent (Livewire page)` | `auth` (tanpa `legal.consent`) |
| GET | `/roadmap` | roadmap.show | `Livewire\Roadmap (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/panduan` | guide.index | `Livewire\Onboarding\Guide (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/bantuan` | support.index | `Livewire\Support\Help (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/library` | library.index | `Livewire\Learning\Library (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/modules/{module}` | modules.show | `Livewire\Learning\ModulePlayer (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/akses-gratis` | feedback.commitment | `Livewire\Learning\FeedbackCommitment (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/event` | events.index | `Livewire\Events\Index (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/event/{event}` | events.show | `Livewire\Events\Show (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/event/{event}/kalender` | events.calendar | `EventCalendarController` | `auth`, `verified`, `legal.consent` |
| GET | `/tryout` | tryout.index | `Livewire\Tryout\Index (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/tryout/{tryout}` | tryout.show | `Livewire\Tryout\Show (intro, pilih mode, mulai)` | `auth`, `verified`, `legal.consent` |
| GET | `/tryout/sesi/{attempt}` | tryout.runner | `Livewire\Tryout\Runner` (layout `layouts.tryout`, hanya pemilik) | `auth`, `verified`, `legal.consent` |
| GET | `/tryout/hasil/{attempt}` | tryout.result | `Livewire\Tryout\Result (hanya pemilik)` | `auth`, `verified`, `legal.consent` |
| GET | `/tryout/hasil/{attempt}/pembahasan` | tryout.review | `Livewire\Tryout\Review` (Pro; Gratis melihat halaman terkunci) | `auth`, `verified`, `legal.consent` |
| GET | `/tryout/{tryout}/teori` | tryout.theory | `Livewire\Tryout\Theory` (modul teori Pro, link materi untuk semua) | `auth`, `verified`, `legal.consent` |
| GET | `/tryout/{tryout}/teori/berkas` | tryout.theory.pdf | `TryoutTheoryPdfController`: byte PDF ter-masking (XOR) untuk viewer di halaman Teori saja; wajib header `X-Skular-Viewer` (dibuka langsung = 404), Pro (Gratis 403), `no-store`, tidak bisa diunduh | `auth`, `verified`, `legal.consent`, `throttle:30,1` |
| GET | `/profiling/recommendation` | profiling.recommendation | `Livewire\Scholarships\Recommendation (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/profiling` | profiling | `Livewire\Profiling\Wizard (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/scholarships/saya` | scholarships.tracker | `Livewire\Scholarships\Tracker (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/scholarships/{application}/proof` | applications.proof | `Http\Controllers\SubmissionProofController@show` | `auth`, `verified`, `legal.consent` |
| GET | `/scholarships/{application}/twibbon` | twibbon.show | `Livewire\Twibbon\Generator (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/scholarships/{scholarship}` | scholarships.show | `Livewire\Scholarships\Show (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/scholarships` | scholarships.index | `Livewire\Scholarships\Index (Livewire page)` | `auth`, `verified`, `legal.consent` |
| GET | `/settings/profile` | profile.edit | `Livewire\Settings\Profile (Livewire page)` | `auth` |
| GET | `/settings/security` | security.edit | `Livewire\Settings\Security (Livewire page)` | `auth`, `verified`, `password.confirm` |
| GET|POST|PUT|PATCH|DELETE|OPTIONS | `/settings` | — | `RedirectController` | `auth` |

Middleware `legal.consent` (`EnsureLegalConsent`) dipasang pada seluruh grup `auth`+`verified` di `routes/web.php`: mentee tanpa persetujuan versi terkini Syarat & Ketentuan dan Kebijakan Privasi diarahkan ke `/persetujuan`. Route pengaturan (`/settings/*`, termasuk hapus akun), halaman legal, logout, dan `/health` tidak memakainya.

## 3. Autentikasi (Fortify)

| Method | URL | Nama route | Handler | Middleware |
|---|---|---|---|---|
| DELETE | `/user/two-factor-authentication` | two-factor.disable | `Fortify\TwoFactorAuthenticationController@destroy` | `auth`, `password.confirm` |
| GET | `/email/verify/{id}/{hash}` | verification.verify | `Fortify\VerifyEmailController@__invoke` | `auth`, `signed`, `throttle:6,1` |
| GET | `/email/verify` | verification.notice | `Fortify\EmailVerificationPromptController@__invoke` | `auth` |
| GET | `/forgot-password` | password.request | `Fortify\PasswordResetLinkController@create` | `guest` |
| GET | `/login` | login | `Fortify\AuthenticatedSessionController@create` | `guest` |
| GET | `/register` | register | `Fortify\RegisteredUserController@create` | `guest` |
| GET | `/reset-password/{token}` | password.reset | `Fortify\NewPasswordController@create` | `guest` |
| GET | `/two-factor-challenge` | two-factor.login | `Fortify\TwoFactorAuthenticatedSessionController@create` | `guest` |
| GET | `/user/confirm-password` | password.confirm | `Fortify\ConfirmablePasswordController@show` | `auth` |
| GET | `/user/confirmed-password-status` | password.confirmation | `Fortify\ConfirmedPasswordStatusController@show` | `auth` |
| GET | `/user/two-factor-qr-code` | two-factor.qr-code | `Fortify\TwoFactorQrCodeController@show` | `auth`, `password.confirm` |
| GET | `/user/two-factor-recovery-codes` | two-factor.recovery-codes | `Fortify\RecoveryCodeController@index` | `auth`, `password.confirm` |
| GET | `/user/two-factor-secret-key` | two-factor.secret-key | `Fortify\TwoFactorSecretKeyController@show` | `auth`, `password.confirm` |
| POST | `/email/verification-notification` | verification.send | `Fortify\EmailVerificationNotificationController@store` | `auth`, `throttle:6,1` |
| POST | `/forgot-password` | password.email | `Fortify\PasswordResetLinkController@store` | `guest` |
| POST | `/login` | login.store | `Fortify\AuthenticatedSessionController@store` | `guest`, `throttle:login` |
| POST | `/logout` | logout | `Fortify\AuthenticatedSessionController@destroy` | `auth` |
| POST | `/register` | register.store | `Fortify\RegisteredUserController@store` | `guest` |
| POST | `/reset-password` | password.update | `Fortify\NewPasswordController@store` | `guest` |
| POST | `/two-factor-challenge` | two-factor.login.store | `Fortify\TwoFactorAuthenticatedSessionController@store` | `guest`, `throttle:two-factor` |
| POST | `/user/confirm-password` | password.confirm.store | `Fortify\ConfirmablePasswordController@store` | `auth` |
| POST | `/user/confirmed-two-factor-authentication` | two-factor.confirm | `Fortify\ConfirmedTwoFactorAuthenticationController@store` | `auth`, `password.confirm` |
| POST | `/user/two-factor-authentication` | two-factor.enable | `Fortify\TwoFactorAuthenticationController@store` | `auth`, `password.confirm` |
| POST | `/user/two-factor-recovery-codes` | two-factor.regenerate-recovery-codes | `Fortify\RecoveryCodeController@store` | `auth`, `password.confirm` |

## 4. Passkey

| Method | URL | Nama route | Handler | Middleware |
|---|---|---|---|---|
| DELETE | `/user/passkeys/{passkey}` | passkey.destroy | `Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@destroy` | `auth`, `password.confirm` |
| GET | `/.well-known/passkey-endpoints` | well-known.passkeys | `Http\Controllers\PasskeyEndpointsController` | — |
| GET | `/passkeys/confirm/options` | passkey.confirm-options | `Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController@index` | `auth`, `throttle:passkeys` |
| GET | `/passkeys/login/options` | passkey.login-options | `Laravel\Passkeys\Http\Controllers\PasskeyLoginController@index` | `guest`, `throttle:passkeys` |
| GET | `/user/passkeys/options` | passkey.registration-options | `Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@index` | `auth`, `password.confirm`, `throttle:passkeys` |
| POST | `/passkeys/confirm` | passkey.confirm | `Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController@store` | `auth`, `throttle:passkeys` |
| POST | `/passkeys/login` | passkey.login | `Laravel\Passkeys\Http\Controllers\PasskeyLoginController@store` | `guest`, `throttle:passkeys` |
| POST | `/user/passkeys` | passkey.store | `Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@store` | `auth`, `password.confirm`, `throttle:passkeys` |

## 5. Sistem

| Method | URL | Nama route | Handler | Middleware |
|---|---|---|---|---|
| GET | `/health` | health | `Http\Controllers\HealthCheckController` | — |
| GET | `/sitemap.xml` | sitemap | `Http\Controllers\SitemapController` | — |
| GET | `/up` | — | `Closure` | — |

## 6. Panel admin (Filament)

Semua route di bawah `/admin` memakai middleware panel Filament (`SetUpPanel:admin`, `Authenticate`, `AuthenticateSession`, CSRF) dan otorisasi Shield policy di `app/Policies`.

| Prefix | URL |
|---|---|
| `(dashboard)` | `/admin` |
| `checkpoints` | `/admin/checkpoints`<br>`/admin/checkpoints/create`<br>`/admin/checkpoints/{record}/edit` |
| `clusters` | `/admin/clusters`<br>`/admin/clusters/create`<br>`/admin/clusters/{record}/edit` |
| `faq-categories` | `/admin/faq-categories`<br>`/admin/faq-categories/create`<br>`/admin/faq-categories/{record}/edit` |
| `faqs` | `/admin/faqs`<br>`/admin/faqs/create`<br>`/admin/faqs/{record}/edit` |
| `fases` | `/admin/fases`<br>`/admin/fases/create`<br>`/admin/fases/{record}/edit` |
| `google-analytics-dashboard` | `/admin/google-analytics-dashboard` |
| `login` | `/admin/login` |
| `logout` | `/admin/logout` |
| `module-feedback` | `/admin/module-feedback`<br>`/admin/module-feedback/create`<br>`/admin/module-feedback/{record}/edit` |
| `modules` | `/admin/modules`<br>`/admin/modules/create`<br>`/admin/modules/{record}/edit`<br>`/admin/modules/{record}/preview` |
| `passkeys` | `/admin/passkeys/authentication-options` |
| `practice-forms` | `/admin/practice-forms`<br>`/admin/practice-forms/create`<br>`/admin/practice-forms/{record}/edit` |
| `quick-questions` | `/admin/quick-questions`<br>`/admin/quick-questions/create`<br>`/admin/quick-questions/{record}/edit` |
| `scholarship-applications` | `/admin/scholarship-applications`<br>`/admin/scholarship-applications/create`<br>`/admin/scholarship-applications/{record}/edit` |
| `scholarships` | `/admin/scholarships`<br>`/admin/scholarships/create`<br>`/admin/scholarships/{record}/edit` |
| `shield` | `/admin/shield/roles`<br>`/admin/shield/roles/create`<br>`/admin/shield/roles/{record}`<br>`/admin/shield/roles/{record}/edit` |
| `twibbon-templates` | `/admin/twibbon-templates`<br>`/admin/twibbon-templates/create`<br>`/admin/twibbon-templates/{record}/edit` |
| `two-factor-authentication` | `/admin/two-factor-authentication` |
| `scholarship-info-reports` | `/admin/scholarship-info-reports` |
| `support-tickets` | `/admin/support-tickets` |
| `user-practice-responses` | `/admin/user-practice-responses` |
| `users` | `/admin/users`<br>`/admin/users/create`<br>`/admin/users/{record}/edit` |
