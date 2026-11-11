# Skular

## 1. Tentang Skular

Skular ([skularship.com](https://skularship.com)) adalah platform persiapan beasiswa berbasis AI untuk mahasiswa dan Gen Z. Mentee mengisi profiling 8 langkah, mendapat rekomendasi beasiswa yang cocok (maksimal 5 dipilih), lalu belajar lewat roadmap modul: materi, Quick Question, praktik menulis yang dinilai AI, AI Mentor dengan kuota, dan checkpoint. AI hanya membantu (menjelaskan, menilai, memberi kerangka); jawaban akhir selalu ditulis mentee sendiri.

Dokumen lain:

| Dokumen | Isi |
|---|---|
| [`AGENTS.md`](AGENTS.md) | Aturan produk, konvensi kode, area sensitif, checklist sebelum commit (untuk developer dan AI agent) |
| [`docs/architecture.md`](docs/architecture.md) | Alur request dan diagram Mermaid (profiling, modul, penilaian AI) |
| [`docs/database.md`](docs/database.md) | Tabel, kolom penting, relasi, ERD |
| [`docs/ai-features.md`](docs/ai-features.md) | Setiap fitur AI: file, prompt, limit, penanganan error |
| [`docs/routes.md`](docs/routes.md) | Daftar route penting |

---

## 2. Tech Stack

Versi diambil dari `composer.json` dan `package.json`.

| Lapisan | Teknologi |
|---|---|
| Bahasa & framework | PHP `^8.3` (diuji 8.5), Laravel `^13.17` |
| UI publik | Livewire `^4.1` (halaman didaftarkan dengan `Route::livewire()`), Alpine.js (bawaan Livewire), Flux `^2.13` |
| Admin | Filament `^5.0` di `/admin` + filament-shield `^4.3` (role/permission), filament-breezy `^3.2` (2FA admin), filament-google-analytics `^3.3` |
| CSS & build | Tailwind CSS `^4` (`@tailwindcss/vite`), Vite Plus `0.3.3` (`vp build` / `vp dev`), laravel-vite-plugin `^3.1` |
| Render Blade | livewire/blaze `^1.0` (mengompilasi `resources/views/components`) |
| Autentikasi | Laravel Fortify `^1.37` (email/password, verifikasi email, 2FA TOTP, passkey via `@laravel/passkeys`), Socialite `^5.31` (Google) |
| AI | laravel/ai `^1.1`, driver OpenRouter. Model teks ditetapkan di `config/ai.php` |
| Data | Eloquent, spatie/laravel-data `^4.23` (DTO). Production: MySQL 8+ / PostgreSQL 15+. Lokal/test: SQLite |
| Queue / cache / session | Default driver `database` (bisa diganti Redis tanpa ubah kode) |
| Kualitas | Pest `^5.3`, Larastan `^3.9` (PHPStan level 7), Pint `^1.27`, Laravel Boost `^2.2` (dev) |

---

## 3. Cara Setup Lokal

**Prasyarat:** PHP 8.3+ dengan ekstensi `gd` dan `pdo`, Composer, Node.js 22.18+ atau 24.11+ (syarat Vite Plus), SQLite.

### Otomatis

```bash
git clone <url-repo> skular && cd skular
composer setup
```

`composer setup` menjalankan: `composer install` → salin `.env.example` ke `.env` (bila belum ada) → `php artisan key:generate` → membuat `database/database.sqlite` → `php artisan migrate --force` → `npm ci` → `npm run build`.

### Manual

```bash
composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite      # DB_CONNECTION=sqlite di .env
php artisan migrate
php artisan db:seed                 # role, cluster, beasiswa, modul contoh, akun admin & mentee demo

npm ci
npm run build
composer dev                        # http://localhost:8000
```

> **Penting:** sebelum menjalankan `migrate`, `db:seed`, atau `tinker` yang menulis data, pastikan `DB_*` di `.env` Anda mengarah ke database lokal, bukan ke server bersama/production.

**Akun hasil seed (khusus lokal).** `AdminUserSeeder` membuat `admin@skular.test` (super_admin), `coadmin@skular.test` (co_admin), dan `staff@skular.test` (staff_divisi). `DemoMenteeSeeder` membuat `mentee.baru@`, `mentee.progress@`, dan `mentee.lulus@skular.test` untuk menguji tiap tahap perjalanan mentee. Password ada di file seeder dan **hanya untuk development**; jangan menjalankan seeder akun di production. Login admin: `/admin/login`.

---

## 4. Environment Variables

Nilai rahasia tidak pernah ditulis di dokumentasi. Daftar lengkap ada di [`.env.example`](.env.example).

| Variabel | Fungsi | Wajib? |
|---|---|---|
| `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL` | Identitas dan mode aplikasi. Production: `APP_ENV=production`, `APP_DEBUG=false` | Wajib |
| `APP_KEY` | Kunci enkripsi. Buat sekali per environment, jangan dirotasi saat deploy rutin | Wajib |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | Locale framework (copy UI ditulis langsung dalam Bahasa Indonesia) | Opsional |
| `SEO_CANONICAL_URL` | Host kanonik untuk canonical URL, sitemap, Open Graph. Kosong = pakai `APP_URL` | Opsional |
| `TRUSTED_PROXIES` | IP/CIDR reverse proxy agar HTTPS dan cookie aman terdeteksi | Wajib bila di belakang proxy |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi database | Wajib |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Default `database`. Untuk trafik tinggi gunakan `redis` | Wajib |
| `SESSION_*` lainnya | Lifetime, domain, secure cookie, same-site | Opsional |
| `REDIS_*`, `MEMCACHED_HOST` | Bila memakai Redis/Memcached | Opsional |
| `OPENROUTER_API_KEY` | Kunci AI untuk feedback praktik, AI Mentor, Brainstorming. Test tidak membutuhkannya (AI di-fake) | Wajib untuk fitur AI |
| `AI_MODULE_CHAT_LIMIT` | Jumlah pertanyaan AI Mentor ke model per mentee per modul. Default `5` | Opsional |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Login Google. Bila kosong, tombol Google mengembalikan ke halaman login | Opsional |
| `PASSKEYS_USER_HANDLE_SECRET` | Secret user handle passkey. Default `APP_KEY`; biarkan dikomentari bila tidak dipakai | Opsional |
| `ANALYTICS_PROPERTY_ID` | Property GA4 untuk dashboard analitik admin (+ file kredensial `storage/app/analytics/service-account-credentials.json`) | Opsional |
| `BLAZE_ENABLED` | Default `true`. `false` hanya untuk debugging komponen Blade, lalu `php artisan view:clear` | Opsional |
| `PERFORMANCE_LOG_SLOW_REQUESTS`, `PERFORMANCE_SLOW_REQUEST_MS`, `PERFORMANCE_LOG_SLOW_QUERIES`, `PERFORMANCE_SLOW_QUERY_MS` | Log request/query lambat (`config/performance.php`) | Opsional |
| `MAIL_*` | Pengiriman email (verifikasi, reset password) | Wajib di production |
| `FILESYSTEM_DISK`, `AWS_*` | Penyimpanan file bila memakai S3 | Opsional |
| `LOG_*`, `BCRYPT_ROUNDS`, `BROADCAST_CONNECTION`, `VITE_APP_NAME` | Default framework | Opsional |

> **Tidak ada variabel untuk model AI.** Model teks ditetapkan di `config/ai.php` (`providers.openrouter.models.text.default`) dan dipakai oleh semua agent.

---

## 5. Struktur Folder

```
app/
├── Ai/                    # AiConfiguration (validasi config AI) + Agents/ (Mentor, Brainstorming, PracticeFeedback)
├── Actions/Fortify/       # Registrasi & reset password (Fortify)
├── Concerns/              # Trait bersama: aturan validasi, pembaca respons AI terstruktur
├── Contracts/             # Interface service; di-bind di AppServiceProvider
├── Data/                  # DTO (spatie/laravel-data)
├── Enums/                 # Enum domain (status, kategori, label skor AI, alasan gagal AI, ...)
├── Exceptions/            # Exception domain dengan pesan ramah (AiMentorException, AiFeedbackException, ...)
├── Filament/              # Panel admin: Resources/{Nama}/{Pages,Schemas,Tables,RelationManagers}, Widgets, Actions
├── Http/                  # Controller tipis (Google OAuth, sitemap, health, bukti pendaftaran), middleware, respons Fortify
├── Livewire/              # Halaman & komponen publik (Profiling, Learning, Scholarships, Cv, Twibbon, Settings, Faq)
├── Models/                # Model Eloquent
├── Policies/              # Policy Shield (hasil generate)
├── Providers/             # AppServiceProvider (binding), FortifyServiceProvider, Filament/AdminPanelProvider
├── Services/              # Business logic (lihat daftar fitur)
└── Support/               # Helper (Seo, dst.)
config/                    # ai.php (provider, model, kuota chat), performance.php, seo.php, loading.php, ...
database/{migrations,factories,seeders}/
resources/
├── css/app.css            # Tailwind 4 + token warna brand
├── js/app.js, passkeys.js # Progressive enhancement (lihat JSDoc di file)
└── views/                 # layouts/, components/, partials/, livewire/ (setiap file punya komentar header)
routes/web.php, settings.php, console.php
tests/Feature/             # Pest (SQLite in-memory); tests/Load/ berisi skrip k6
docs/                      # Dokumentasi arsitektur, database, AI, routes
public/build/              # Aset Vite hasil build (DI-COMMIT)
```

---

## 6. Daftar Fitur

| Fitur | Route | File utama |
|---|---|---|
| Landing + Profiling Kilat (tanpa login, tidak disimpan) | `GET /` (`home`) | `resources/views/welcome.blade.php`, `app/Livewire/Profiling/QuickAssessment.php`, `app/Services/ReadinessScoringService.php` |
| FAQ publik | `/faq` | `app/Livewire/Faq/Index.php`, `LandingSection.php` |
| Registrasi, login, verifikasi email, reset password, 2FA | `/register`, `/login`, ... (Fortify) | `app/Providers/FortifyServiceProvider.php`, `app/Actions/Fortify/*`, `app/Http/Responses/*`, `resources/views/livewire/auth/*` |
| Login Google | `/auth/google/redirect`, `/auth/google/callback` | `app/Http/Controllers/Auth/GoogleAuthController.php`, `app/Services/GoogleAuthService.php` |
| Passkey | `/passkeys/*`, `/user/passkeys*` (Fortify) | `resources/js/passkeys.js`, `components/auth/passkey-*.blade.php` |
| Profiling 8 langkah | `/profiling` | `app/Livewire/Profiling/Wizard.php`, `app/Services/ProfilingService.php`, `app/Concerns/ProfilingRules.php` |
| Rekomendasi & pemilihan beasiswa (maks 5; kartu 1:1 berisi gambar, nama, label kecocokan; alasan kecocokan di halaman detail) | `/profiling/recommendation` | `app/Livewire/Scholarships/Recommendation.php`, `app/Services/ScholarshipMatchingService.php`, `ScholarshipApplicationService.php`, `ScholarshipImageService.php`, `components/scholarship/card-image.blade.php` |
| Katalog & detail beasiswa | `/scholarships`, `/scholarships/{scholarship}` | `app/Livewire/Scholarships/Index.php`, `Show.php` |
| Product Tour onboarding (coach marks landing → profiling → rekomendasi → dashboard → roadmap → modul → Beasiswa Saya; muncul sekali per halaman) + halaman Panduan dengan "Ulangi tur" | `/panduan` | `ProductTourService.php`, `app/Livewire/Onboarding`, `components/onboarding/tour-overlay.blade.php`, `resources/js/product-tour.js` |
| Bantuan: WhatsApp (`SUPPORT_WHATSAPP_NUMBER`, default +62 896-0488-3024) + form lapor bug/error dengan tangkapan layar opsional (maks 5 tiket/24 jam); admin menangani di *Pemantauan Mentee → Tiket bantuan* | `/bantuan`, `/admin/support-tickets` | `app/Livewire/Support/Help.php`, `SupportTicketService.php`, `app/Filament/Resources/SupportTickets` |
| Laporan "Informasi Tidak Akurat?" (mentee lapor dari detail beasiswa; admin tinjau di *Beasiswa → Laporan info beasiswa*) | `/scholarships/{scholarship}`, `/admin/scholarship-info-reports` | `ScholarshipReportService.php`, `app/Filament/Resources/ScholarshipInfoReports` |
| Beasiswa Saya (tracker, bukti, hasil) | `/scholarships/saya`, `/scholarships/{application}/proof` | `app/Livewire/Scholarships/Tracker.php`, `app/Http/Controllers/SubmissionProofController.php`, `app/Services/ScholarshipPathService.php` |
| Dashboard | `/dashboard` | `app/Livewire/Dashboard.php`, `app/Services/ProgressService.php`, `LearningPathService.php` |
| Roadmap Belajar (fase aktif + modul; fase terkunci hanya nama, ditutup awan) | `/roadmap` | `app/Livewire/Roadmap.php`, `ProgressService::focusRoadmap()`, `app/Data/FocusedRoadmapData.php` |
| Module player (Pembukaan → Pahami → Quick Question → Praktik → Checkpoint) | `/modules/{module}?section=...` | `app/Livewire/Learning/ModulePlayer.php`, `resources/views/livewire/learning/partials/*`, `app/Services/ModuleOutlineService.php` |
| Pengantar + Inti Materi multi-kolom (Markdown: `#` hijau, `##`, `###`, **tebal**, daftar, kutipan; teks lama tetap tampil) | (di langkah Pahami) | `app/Models/ModuleCoreSection.php`, `partials/pahami.blade.php`, `components/learning/rich-content.blade.php`, `RichContentRenderer.php`, `resources/css/prose.css` |
| Quick Question (benar/salah, dikoreksi sistem) | (di langkah Quick) | `app/Models/QuickQuestion.php`, `partials/quick.blade.php` |
| Praktik + penilaian AI sinkron di request (label, bukan skor) | (di langkah Praktik) | `app/Services/PracticeSubmissionService.php`, `app/Services/AiFeedbackService.php`, `app/Enums/AiScoreLabel.php` |
| AI Mentor (saran pertanyaan admin + kuota 5/modul) | (di langkah Praktik) | `app/Livewire/Learning/MentorChat.php`, `app/Services/ModuleMentorService.php`, `MentorQuotaService.php` |
| AI Brainstorming (kerangka draf dari jawaban mentee) | (di form praktik) | `app/Livewire/Learning/Brainstorming.php`, `app/Services/ModuleBrainstormingService.php`, `BrainstormingSessionService.php` |
| Gate tahap Feedback/Checkpoint | (di langkah Checkpoint) | `ModulePlayer::checkpointBlock()` / `guardCheckpointSection()`, `ProgressService::completeModule()` |
| Perpustakaan (modul opsional) | `/library` | `app/Livewire/Learning/Library.php` |
| CV otomatis dari jawaban praktik | `/cv` | `app/Livewire/Cv/Show.php`, `app/Services/CvService.php` |
| Twibbon | `/scholarships/{application}/twibbon` | `app/Livewire/Twibbon/Generator.php`, `app/Services/TwibbonService.php` |
| Pengaturan akun (profil, keamanan, passkey, 2FA) | `/settings/profile`, `/settings/security` | `app/Livewire/Settings/*` |
| Panel admin (kurikulum, beasiswa, pemantauan mentee, pengguna) | `/admin/*` | `app/Filament/**`, `app/Policies/*` |
| Pratinjau modul untuk admin (read-only) | `/admin/modules/{record}/preview` | `app/Filament/Resources/Modules/Pages/PreviewModule.php` |
| SEO, sitemap, health check | `/sitemap.xml`, `/up`, `/health`, `/health/queue` | `app/Http/Controllers/SitemapController.php`, `HealthCheckController.php`, `QueueHealthController.php`, `config/seo.php` |

### Aturan bisnis ringkas

- **Maks 5 beasiswa per mentee** — `ScholarshipApplicationService::MAX_SELECTIONS`, dikunci dengan `lockForUpdate` di `select()`; duplikat dicegah unique index `(user_id, scholarship_id)`.
- **Antrean beasiswa** diurutkan deadline terdekat; setelah jalur belajar satu beasiswa selesai, `ScholarshipPathService` membuka beasiswa berikutnya (idempoten).
- **Tiga atribut modul berbeda:** `modules.type` (format penyajian, kolom lama), `modules.category` (`inti` / `template` / `perpustakaan`), `modules.content_status` (`draft` / `published`). Draf 404 di player dan tidak masuk jalur belajar; publikasi hanya lewat aksi Filament yang lolos `ModuleReadinessService`.
- **Jalur belajar personal** = modul Inti + modul Template beasiswa yang sedang difokuskan. Progres Inti dibagi lintas beasiswa.
- **Histori mentee tidak bisa terhapus:** Scholarship/Module/Fase yang sudah punya histori menolak `delete()`; gunakan status Closed/Archived atau *Jadikan draf*.
- **AI hanya untuk konten** (feedback, mentor, kerangka). Urutan beasiswa, batas 5, unlock modul, dan gate checkpoint dihitung deterministik dari database.

Detail lengkap: [`AGENTS.md`](AGENTS.md) dan [`docs/architecture.md`](docs/architecture.md).

### Alur kerja penyusun kurikulum (admin)

1. Buat modul (selalu tersimpan sebagai **Draf**).
2. Isi informasi dasar, kata pengantar, materi Pahami (ringkasan, poin penting, **kolom Inti Materi**, link video YouTube opsional — link tonton/youtu.be/shorts otomatis diubah jadi pemutar, modul tanpa link menampilkan "Tidak ada materi video"; kata pengantar, ringkasan, dan isi kolom memakai editor Markdown dengan panduan format singkat, cek hasilnya di *Pratinjau*), pertanyaan brainstorming, **saran pertanyaan AI Mentor** (jawaban tim opsional), dan rubrik AI.
3. Tambah Quick Question dan Latihan praktik dari tabel di halaman modul.
4. Buka **Pratinjau**, perbaiki item merah di checklist kelengkapan, lalu **Terbitkan**.

Menu admin dikelompokkan: Kurikulum, Materi & Latihan, Beasiswa, Pemantauan Mentee, Pendaftaran Beasiswa, Administrasi Sistem. Role: `super_admin` (melewati semua cek izin), `co_admin`, `staff_divisi` (izin dari `RoleSeeder`).

---

## 7. Perintah Penting

```bash
composer dev              # php artisan serve + queue:listen + vite (paralel)
npm run dev               # Vite dev server saja (vp dev)
npm run build             # build aset production (vp build) — hasilnya di-commit
php artisan migrate       # jalankan migration
php artisan queue:work    # worker queue (WAJIB untuk penilaian AI praktik dan Nilai ulang)

composer test             # config:clear + Pint (test) + PHPStan + seluruh test — gerbang sebelum merge
composer ci:check         # rantai yang sama dengan CI
composer lint             # Pint (perbaiki format)
composer types:check      # PHPStan / Larastan level 7

vendor/bin/pest tests/Feature/Learning/MentorChatQuotaTest.php     # satu file test
vendor/bin/pest --filter "the default limit is five questions"      # satu test

# Setelah menambah/mengganti nama resource Filament:
php artisan shield:generate --all --panel=admin
php artisan db:seed --class=RoleSeeder
```

Scheduler tidak diperlukan (tidak ada task terjadwal). Bila PHPStan kehabisan memori di mesin lokal, jalankan `vendor/bin/phpstan analyse --memory-limit=2G`.

---

## 8. Deployment

Repo tidak berisi skrip deploy otomatis; langkah yang dipakai saat ini:

### Gerbang sebelum rilis

```bash
composer ci:check         # Pint + PHPStan + seluruh test
npm ci && npm run build   # lalu commit SEMUA file baru di public/build/assets + manifest.json
composer audit --locked
npm audit --omit=dev
```

### Langkah setiap deploy

```bash
php artisan down                               # opsional untuk rilis besar
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize                           # cache config, route, event, view
php artisan view:cache                         # wajib: hindari kompilasi view di request pertama
php artisan filament:optimize
php artisan queue:restart                      # worker memuat kode baru
php artisan up
```

### Kebutuhan runtime

| Komponen | Keterangan |
|---|---|
| PHP | 8.3+ dengan OPcache aktif; reload PHP-FPM setiap deploy bila `validate_timestamps=0` |
| Database | MySQL 8+ atau PostgreSQL 15+ (bukan SQLite) |
| Queue worker | **Tidak wajib** untuk penilaian AI praktik: penilaian pertama dan *Nilai ulang* berjalan sinkron di request web. Saat ini tidak ada job lain di codebase |
| Timeout request | Penilaian praktik menahan request Livewire paling lama ±49 detik (22 dtk × 2 percobaan + jeda 5 dtk). Batas request di depan PHP harus **≥ 60 detik** (`PracticeSubmissionService::REQUEST_TIME_LIMIT_SECONDS`): nginx `fastcgi_read_timeout`/`proxy_read_timeout` (default 60), PHP-FPM `request_terminate_timeout` (0 atau ≥ 60), Apache/LiteSpeed (default 300), Cloudflare (100). Evaluasi menaikkan `max_execution_time` ke 60 bila lebih kecil. Bila batas server lebih pendek, turunkan dulu anggaran di kode (lihat `docs/ai-features.md`) |
| Storage | Persisten & privat: bukti pendaftaran di disk `local`; twibbon dan gambar kartu beasiswa (`scholarships/`, WebP ≤512 px hasil resize GD) di disk `public` (`php artisan storage:link`). GD dengan dukungan WebP disarankan; tanpa WebP gambar disimpan sebagai JPEG |
| HTTPS | Wajib; isi `TRUSTED_PROXIES` bila di belakang load balancer |
| Health check | `GET /up` (liveness), `GET /health` (database + cache, 503 bila gagal), `GET /health/queue` (503 bila ada job menunggu > 120 detik = worker tidak jalan; pasang di uptime monitor, jangan di load balancer) |
| Uji beban | `tests/Load/skular-smoke.js` (k6), jalankan ke staging |

> CI didefinisikan di `.github/workflows/tests.yml`, tetapi folder `/.github` saat ini tercantum di `.gitignore`, sehingga workflow tidak ikut ter-push. Lihat laporan dokumentasi.
