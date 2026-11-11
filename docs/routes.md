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

## 2. Halaman mentee & publik

| Method | URL | Nama route | Handler | Middleware |
|---|---|---|---|---|
| GET | `/` | home | `view welcome` (Route::view) | — |
| GET | `/auth/google/callback` | auth.google.callback | `Http\Controllers\Auth\GoogleAuthController@callback` | `throttle:30,1` |
| GET | `/auth/google/redirect` | auth.google.redirect | `Http\Controllers\Auth\GoogleAuthController@redirect` | `throttle:30,1` |
| GET | `/cv` | cv.show | `Livewire\Cv\Show (Livewire page)` | `auth`, `verified` |
| GET | `/dashboard` | dashboard | `Livewire\Dashboard (Livewire page)` | `auth`, `verified` |
| GET | `/faq` | faq.index | `Livewire\Faq\Index (Livewire page)` | — |
| GET | `/roadmap` | roadmap.show | `Livewire\Roadmap (Livewire page)` | `auth`, `verified` |
| GET | `/bantuan` | support.index | `Livewire\Support\Help (Livewire page)` | `auth`, `verified` |
| GET | `/library` | library.index | `Livewire\Learning\Library (Livewire page)` | `auth`, `verified` |
| GET | `/modules/{module}` | modules.show | `Livewire\Learning\ModulePlayer (Livewire page)` | `auth`, `verified` |
| GET | `/profiling/recommendation` | profiling.recommendation | `Livewire\Scholarships\Recommendation (Livewire page)` | `auth`, `verified` |
| GET | `/profiling` | profiling | `Livewire\Profiling\Wizard (Livewire page)` | `auth`, `verified` |
| GET | `/scholarships/saya` | scholarships.tracker | `Livewire\Scholarships\Tracker (Livewire page)` | `auth`, `verified` |
| GET | `/scholarships/{application}/proof` | applications.proof | `Http\Controllers\SubmissionProofController@show` | `auth`, `verified` |
| GET | `/scholarships/{application}/twibbon` | twibbon.show | `Livewire\Twibbon\Generator (Livewire page)` | `auth`, `verified` |
| GET | `/scholarships/{scholarship}` | scholarships.show | `Livewire\Scholarships\Show (Livewire page)` | `auth`, `verified` |
| GET | `/scholarships` | scholarships.index | `Livewire\Scholarships\Index (Livewire page)` | `auth`, `verified` |
| GET | `/settings/profile` | profile.edit | `Livewire\Settings\Profile (Livewire page)` | `auth` |
| GET | `/settings/security` | security.edit | `Livewire\Settings\Security (Livewire page)` | `auth`, `verified`, `password.confirm` |
| GET|POST|PUT|PATCH|DELETE|OPTIONS | `/settings` | — | `RedirectController` | `auth` |

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
