# Skular

## 1. Tentang Skular

Skular ([skularship.com](https://skularship.com)) adalah platform persiapan beasiswa berbasis AI untuk mahasiswa dan Gen Z. Setelah daftar, mentee melihat Info Beasiswa (bisa difilter tanpa profiling), lalu lewat tombol "Cari beasiswa yang cocok untukku" mengisi profiling 8 langkah, mendapat rekomendasi beasiswa yang cocok (maksimal 5 dipilih), lalu belajar lewat roadmap modul: materi, Quick Question, praktik menulis yang dinilai AI, AI Mentor dengan kuota, dan checkpoint. AI hanya membantu (menjelaskan, menilai, memberi kerangka); jawaban akhir selalu ditulis mentee sendiri.

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
| Admin | Filament `^5.0` di `/admin` + filament-shield `^4.3` (role/permission), filament-breezy `^3.2` (2FA admin), filament-google-analytics `^3.3` (hanya sebagai penyedia klien GA4; halaman analitik = `App\Filament\Pages\SiteAnalytics`) |
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
| `SEO_HOME_LASTMOD`, `SEO_FAQ_LASTMOD` | Override `lastmod` sitemap (`YYYY-MM-DD`) untuk `/` dan `/faq`. Default di `config/seo.php` (`pages.*.lastmod`), diubah manual hanya saat isi halaman berubah. `/faq` memakai `updated_at` FAQ terbit terbaru bila ada | Opsional |
| `SEO_SAME_AS` | URL profil resmi Skular untuk `sameAs` pada JSON-LD Organization di landing, dipisah koma. Default `https://www.instagram.com/skulars` (`config/seo.php`, `same_as`) | Opsional |
| `TRUSTED_PROXIES` | IP/CIDR reverse proxy agar HTTPS dan cookie aman terdeteksi | Wajib bila di belakang proxy |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi database | Wajib |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Default `database`. Untuk trafik tinggi gunakan `redis` | Wajib |
| `SESSION_*` lainnya | Lifetime, domain, secure cookie, same-site | Opsional |
| `REDIS_*`, `MEMCACHED_HOST` | Bila memakai Redis/Memcached | Opsional |
| `OPENROUTER_API_KEY` | Kunci AI untuk feedback praktik, AI Mentor, Brainstorming. Test tidak membutuhkannya (AI di-fake) | Wajib untuk fitur AI |
| `AI_MODULE_CHAT_LIMIT` | Jumlah pertanyaan AI Mentor ke model per mentee per modul. Default `5` | Opsional |
| `TRYOUT_MENTOR_LIMIT` | Pertanyaan mentor AI Try Out per attempt (Pro). Default `10` | Opsional |
| `IQ_MENTOR_DAILY_LIMIT` | Pertanyaan mentor AI Tes IQ per akun per hari (akses berbayar). Default `10` (angka sementara) | Opsional |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Login Google. Bila kosong, tombol Google mengembalikan ke halaman login | Opsional |
| `PASSKEYS_USER_HANDLE_SECRET` | Secret user handle passkey. Default `APP_KEY`; biarkan dikomentari bila tidak dipakai | Opsional |
| `ANALYTICS_PROPERTY_ID` | ID numerik property GA4 (bukan `G-XXXX`) untuk halaman Google Analytics admin (`SiteAnalyticsService`) + file kredensial `storage/app/analytics/service-account-credentials.json`. Email service account wajib ditambahkan sebagai **Viewer** di GA4 → Admin → Property access management; tanpa itu halaman menampilkan pesan "belum punya akses". Cache: hari ini 5 menit, periode lain 1 jam | Opsional |
| `GA_MEASUREMENT_ID` | Tag tracking GA4 di halaman publik (format `G-XXXXXXXXXX`, hanya production, tidak di `/admin`); beda dengan `ANALYTICS_PROPERTY_ID` | Opsional |
| `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION` | Kunci Midtrans Snap (sandbox dulu, production setelah akun disetujui). Server key rahasia, jangan di repo | Wajib bila `PREMIUM_ENABLED=true` |
| `QUEUE_CONNECTION` (worker) | Pembayaran dan reminder (termasuk pengingat event) berjalan di antrean. Production wajib menjalankan `php artisan queue:work --queue=payments,reminders,default` (Supervisor) dan scheduler (`schedule:run` tiap menit) | Wajib bila `PREMIUM_ENABLED=true` atau ada event terbit |
| `SUPPORT_WHATSAPP_NUMBER` | Nomor WhatsApp untuk tombol bantuan di `/bantuan` dan `/premium` | Opsional |
| `PREMIUM_ENABLED` | Saklar fitur Premium (`/premium`, bagian Harga di landing, kartu paket di dashboard). Default `false` | Opsional |
| `UNDER_CONSTRUCTION` | Mode "Skular sedang diperbarui": halaman publik & mentee menjawab 503 + `Retry-After`; admin, `/health`, `/up`, halaman legal, login, dan webhook Midtrans tetap jalan. Default `false`. Wajib `php artisan optimize` setelah diubah | Opsional |
| `PRICING_LAUNCHING_UNTIL` | Akhir promo launching (ISO 8601). Kosong atau lewat = harga normal | Opsional |
| `BLAZE_ENABLED` | Default `true`. `false` hanya untuk debugging komponen Blade, lalu `php artisan view:clear` | Opsional |
| `PERFORMANCE_LOG_SLOW_REQUESTS`, `PERFORMANCE_SLOW_REQUEST_MS`, `PERFORMANCE_LOG_SLOW_QUERIES`, `PERFORMANCE_SLOW_QUERY_MS` | Log request/query lambat (`config/performance.php`) | Opsional |
| `MAIL_*` | Pengiriman email (verifikasi, reset password, pengingat). Semua email berbahasa Indonesia lewat `User::preferredLocale()` + `lang/id.json` | Wajib di production |
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
| Product Tour onboarding (coach marks landing → Info Beasiswa → profiling → rekomendasi → dashboard → roadmap → modul → Beasiswa Saya; muncul sekali per halaman) + halaman Panduan dengan "Ulangi tur" | `/panduan` | `ProductTourService.php`, `app/Livewire/Onboarding`, `components/onboarding/tour-overlay.blade.php`, `resources/js/product-tour.js` |
| Bantuan: WhatsApp (`SUPPORT_WHATSAPP_NUMBER`, default +62 896-0488-3024) + form lapor bug/error dengan tangkapan layar opsional (maks 5 tiket/24 jam); admin menangani di *Pemantauan Mentee → Tiket bantuan* | `/bantuan`, `/admin/support-tickets` | `app/Livewire/Support/Help.php`, `SupportTicketService.php`, `app/Filament/Resources/SupportTickets` |
| Laporan "Informasi Tidak Akurat?" (mentee lapor dari detail beasiswa; admin tinjau di *Beasiswa → Laporan info beasiswa*) | `/scholarships/{scholarship}`, `/admin/scholarship-info-reports` | `ScholarshipReportService.php`, `app/Filament/Resources/ScholarshipInfoReports` |
| Beasiswa Saya (tracker, bukti, hasil) | `/scholarships/saya`, `/scholarships/{application}/proof` | `app/Livewire/Scholarships/Tracker.php`, `app/Http/Controllers/SubmissionProofController.php`, `app/Services/ScholarshipPathService.php` |
| Dashboard | `/dashboard` | `app/Livewire/Dashboard.php`, `app/Services/ProgressService.php`, `LearningPathService.php` |
| Roadmap Belajar (fase aktif + modul; fase terkunci hanya nama, ditutup awan) | `/roadmap` | `app/Livewire/Roadmap.php`, `ProgressService::focusRoadmap()`, `app/Data/FocusedRoadmapData.php` |
| Module player (Pembukaan → Pahami → Quick Question → Praktik → Checkpoint) | `/modules/{module}?section=...` | `app/Livewire/Learning/ModulePlayer.php`, `resources/views/livewire/learning/partials/*`, `app/Services/ModuleOutlineService.php` |
| Pengantar (Markdown) + Inti Materi multi-kolom bergaya dokumen: tiap kolom = "Bagian X dari N", daftar isi sticky, editor WYSIWYG di admin dengan 4 ukuran huruf, 9 warna teks, dan 6 jenis huruf dari `config/content.php`; HTML dirender lewat sanitizer allowlist, kolom lama berformat Markdown tetap tampil | (di langkah Pahami) | `app/Models/ModuleCoreSection.php` (`body_format`), `partials/pahami.blade.php`, `components/learning/rich-content.blade.php`, `RichContentRenderer.php`, `app/Filament/RichEditor/`, `resources/css/prose.css` |
| Quick Question (benar/salah, dikoreksi sistem) | (di langkah Quick) | `app/Models/QuickQuestion.php`, `partials/quick.blade.php` |
| Praktik + penilaian AI sinkron di request (label, bukan skor) | (di langkah Praktik) | `app/Services/PracticeSubmissionService.php`, `app/Services/AiFeedbackService.php`, `app/Enums/AiScoreLabel.php` |
| AI Mentor (saran pertanyaan admin + kuota 5/modul) | (di langkah Praktik) | `app/Livewire/Learning/MentorChat.php`, `app/Services/ModuleMentorService.php`, `MentorQuotaService.php` |
| AI Brainstorming (kerangka draf dari jawaban mentee) | (di form praktik) | `app/Livewire/Learning/Brainstorming.php`, `app/Services/ModuleBrainstormingService.php`, `BrainstormingSessionService.php` |
| Gate tahap Feedback/Checkpoint + masukan wajib per modul (skor 1-5, bagian tersulit, pertanyaan terbuka, alasan bila skor 1-3; tombol "Kirim masukan & lanjut") | (di langkah Checkpoint) | `ModulePlayer::checkpointBlock()` / `guardCheckpointSection()` / `submitFeedbackAndContinue()`, `ModuleFeedbackService.php`, `ProgressService::completeModule()` |
| Komitmen akses gratis (sekali, setelah profiling atau saat pertama membuka modul) | `/akses-gratis` | `app/Livewire/Learning/FeedbackCommitment.php`, `users.feedback_commitment_at` |
| Event: webinar, kelas intensif, mentoring personal (daftar + pertanyaan singkat pengganti Google Form, kapasitas, audiens Semua/Pro/Undangan lewat tag mentee, link acara hanya untuk peserta mulai H-1, file .ics, pengingat email H-1 hari & H-1 jam, masukan 1-5 setelah acara, rekaman; mentee Gratis bisa menonton satu rekaman event Pro yang ditandai admin "Pratinjau gratis", rekaman Pro lain tampil terkunci dengan tautan ke `/premium`) | `/event`, `/event/{slug}`, `/event/{slug}/kalender` | `app/Livewire/Events/*`, `app/Services/EventService.php`, `app/Console/Commands/DispatchEventReminders.php`, `app/Mail/EventReminderMail.php`, `app/Filament/Resources/Events/**` |
| Tracking perilaku mentee (tanpa pihak ketiga) + widget admin "Funnel Mentee" 7/30 hari | (semua alur utama) | `app/Services/ActivityTracker.php`, `app/Enums/ActivityName.php`, `app/Filament/Widgets/MenteeFunnel.php` |
| Perpustakaan (modul opsional; modul `is_tryout_theory` disembunyikan) | `/library` | `app/Livewire/Learning/Library.php` |
| Try Out IELTS: 4 subtes berurutan (Listening, Reading adaptif per soal dengan blok unit; Writing dan Speaking dinilai AI; Speaking ditranskripsi gratis di browser lewat Web Speech API, hanya Chrome/Edge/Safari), timer dari server, mode Latihan/Ujian, hasil gratis (band estimasi, skor mentah, level), pembahasan + mentor AI + teori + attempt ulang untuk Pro. Admin: *Try Out → Paket Try Out, Unit Teks & Audio, Bank Soal (impor/ekspor JSON), Hasil Mentee* + widget kesehatan bank soal | `/tryout`, `/tryout/{slug}`, `/tryout/sesi/{attempt}`, `/tryout/hasil/{attempt}`, `/tryout/hasil/{attempt}/pembahasan`, `/tryout/{slug}/teori` | `app/Livewire/Tryout/**`, `app/Services/Tryout*.php`, `AdaptiveQuestionSelector.php`, `AnswerChecker.php`, `WritingAssessmentService.php`, `SpeakingAssessmentService.php`, `IeltsScoringStrategy.php`, `config/tryout.php`, `app/Filament/Resources/Tryout*/**` |
| Tes IQ Latihan: 35 soal pilihan ganda A-D (Numerik, Visuospasial bergambar, Verbal, Logika, Teks) dalam 20 menit, timer dari server, jawaban tersimpan setiap dipilih dan bisa diganti sampai Kirim, satu percobaan aktif per akun, pesan dukungan di 25/50/75% hanya dari hitungan jawaban mentee. Soal gratis; skor per subtes (benar/jumlah + persen), kategori, pembahasan, materi (PPT sebagai PDF di viewer tanpa unduh), dan mentor AI hanya dengan akses berbayar (`plans.*.iq_premium`, saat ini = Pro). Tidak pernah menampilkan angka IQ. Admin: *Tes IQ → Tes IQ, Bank Soal IQ (impor/ekspor JSON)* | `/tes-iq`, `/tes-iq/sesi/{attempt}`, `/tes-iq/hasil/{attempt}`, `/tes-iq/materi` | `app/Livewire/Iq/**`, `app/Services/Iq*.php`, `config/iq.php`, `app/Filament/Resources/Iq*/**`, `database/data/iq/bank-soal-v0.1.json` |
| CV otomatis dari jawaban praktik (hanya latihan aktif di modul terbit, mengikuti urutan pertanyaan, tanpa file unggahan) | `/cv` | `app/Livewire/Cv/Show.php`, `app/Services/CvService.php` |
| Panel "Data dirimu" di langkah Praktik: tombol "Tampilkan ..." membuka jawaban CV mentee dari modul lain sebagai referensi + tombol Salin + tautan "Perbaiki di modul asal"; tidak pernah mengisi jawaban otomatis; sumber dipilih admin di editor modul (section 6) | (di langkah Praktik) | `CvService::sectionsForModule()`, `ModulePlayer::toggleCvSource()`, `partials/cv-reference-panel.blade.php`, tabel `module_cv_sources`, `practice_forms.cv_button_label` |
| Twibbon | `/scholarships/{application}/twibbon` | `app/Livewire/Twibbon/Generator.php`, `app/Services/TwibbonService.php` |
| Pengaturan akun (profil, keamanan, passkey, 2FA) | `/settings/profile`, `/settings/security` | `app/Livewire/Settings/*` |
| Panel admin (kurikulum, beasiswa, pemantauan mentee, pengguna) | `/admin/*` | `app/Filament/**`, `app/Policies/*` |
| Pratinjau modul untuk admin (read-only) | `/admin/modules/{record}/preview` | `app/Filament/Resources/Modules/Pages/PreviewModule.php` |
| Halaman legal (Kebijakan Privasi, Syarat & Ketentuan, Kebijakan Cookie, Kebijakan Pengembalian Dana): Blade statis, isi dari `config/legal.php`, versi + tanggal berlaku per dokumen | `/kebijakan-privasi`, `/syarat-ketentuan`, `/kebijakan-cookie`, `/kebijakan-pengembalian-dana` | `resources/views/legal/*`, `components/legal/*`, `config/legal.php`, `app/Enums/LegalDocument.php`, `php artisan legal:check` |
| Banner persetujuan cookie (Esensial selalu aktif, Analitik/GA4 opsional); GA4 tidak dimuat sebelum izin, dibuka ulang dari "Pengaturan cookie" di footer | semua halaman publik, auth, dan aplikasi | `partials/cookie-consent.blade.php`, `resources/js/cookie-consent.js`, `partials/analytics.blade.php`, `app/Support/CookieConsent.php` |
| Pencatatan persetujuan (daftar, login Google pertama, checkout, persetujuan ulang saat versi naik); admin melihat riwayat di halaman edit pengguna | `/register`, `/persetujuan`, `/premium` | `ConsentService.php`, `EnsureLegalConsent.php`, `app/Livewire/Legal/Consent.php`, `CreateNewUser.php`, `Users/RelationManagers/ConsentsRelationManager.php` |
| SEO, sitemap, health check | `/sitemap.xml`, `/up`, `/health` | `app/Http/Controllers/SitemapController.php`, `HealthCheckController.php`, `config/seo.php` |

### Aturan bisnis ringkas

- **Maks 5 beasiswa per mentee** — `ScholarshipApplicationService::MAX_SELECTIONS`, dikunci dengan `lockForUpdate` di `select()`; duplikat dicegah unique index `(user_id, scholarship_id)`.
- **Antrean beasiswa** diurutkan deadline terdekat; setelah jalur belajar satu beasiswa selesai, `ScholarshipPathService` membuka beasiswa berikutnya (idempoten).
- **Tiga atribut modul berbeda:** `modules.type` (format penyajian, kolom lama), `modules.category` (`inti` / `template` / `perpustakaan`), `modules.content_status` (`draft` / `published`). Draf 404 di player dan tidak masuk jalur belajar; publikasi hanya lewat aksi Filament yang lolos `ModuleReadinessService`.
- **Jalur belajar personal** = modul Inti + modul Template beasiswa yang sedang difokuskan. Progres Inti dibagi lintas beasiswa.
- **Histori mentee tidak bisa terhapus:** Scholarship/Module/Fase yang sudah punya histori menolak `delete()`; gunakan status Closed/Archived atau *Jadikan draf*.
- **Persetujuan legal:** daftar wajib mencentang S&K + Kebijakan Privasi dan pernyataan usia (divalidasi di `CreateNewUser`). Mentee tanpa persetujuan versi terkini (akun Google baru, akun lama, versi naik) diarahkan ke `/persetujuan` oleh middleware `legal.consent`. Menaikkan `legal.documents.{terms,privacy}.version` = semua mentee menyetujui ulang; menaikkan `cookies` = banner muncul lagi.
- **AI hanya untuk konten** (feedback, mentor, kerangka). Urutan beasiswa, batas 5, unlock modul, dan gate checkpoint dihitung deterministik dari database.

Detail lengkap: [`AGENTS.md`](AGENTS.md) dan [`docs/architecture.md`](docs/architecture.md).

### Alur kerja penyusun kurikulum (admin)

1. Buat modul (selalu tersimpan sebagai **Draf**).
2. Isi informasi dasar, kata pengantar, materi Pahami (ringkasan, poin penting, **kolom Inti Materi**, link video YouTube opsional — link tonton/youtu.be/shorts otomatis diubah jadi pemutar, modul tanpa link menampilkan "Tidak ada materi video"; kata pengantar dan ringkasan memakai editor Markdown; isi kolom Inti Materi memakai editor WYSIWYG (subjudul H2/H3, tebal, daftar, ukuran huruf, jenis huruf, warna teks, sorot, kutipan, tabel, link) yang tampil sama dengan halaman mentee; modul lama dikonversi otomatis saat disimpan; cek hasilnya di *Pratinjau*), pertanyaan brainstorming, **saran pertanyaan AI Mentor** (jawaban tim opsional), dan rubrik AI.
3. Tambah Quick Question dan Latihan praktik dari tabel di halaman modul.
4. Buka **Pratinjau**, perbaiki item merah di checklist kelengkapan, lalu **Terbitkan**.

Menu admin dikelompokkan: Kurikulum, Materi & Latihan, Beasiswa, Pemantauan Mentee, Pendaftaran Beasiswa, Pembayaran, Administrasi Sistem. Role: `super_admin` (melewati semua cek izin), `co_admin`, `staff_divisi` (izin dari `RoleSeeder`), `staff_modul` (izin diatur di halaman Role Shield). Role baru hanya bisa masuk panel bila namanya terdaftar di `App\Enums\AdminRole`.

---

## 7. Perintah Penting

```bash
composer dev              # php artisan serve + vite (paralel)
npm run dev               # Vite dev server saja (vp dev)
npm run build             # build aset production (vp build) — hasilnya di-commit
php artisan migrate       # jalankan migration

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

Scheduler dibutuhkan (`php artisan schedule:run` tiap menit) untuk task di `routes/console.php`, termasuk `tryout:close-expired` (tiap menit) dan `iq:close-expired` (tiap 5 menit). Impor bank soal Try Out dari CLI: `php artisan tryout:import bank.json --dry-run`; bank soal Tes IQ: `php artisan iq:import database/data/iq/bank-soal-v0.1.json --dry-run` (soal Visuospasial tersimpan nonaktif sampai gambar + teks alternatif diunggah di admin). Bila PHPStan kehabisan memori di mesin lokal, jalankan `vendor/bin/phpstan analyse --memory-limit=2G`.

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

### Gerbang halaman legal (sebelum `PREMIUM_ENABLED=true` dan aktivasi Midtrans production)

1. Isi env `LEGAL_*` (lihat `.env.example`) dan keputusan terbuka di `PROGRES-LEGAL.md`.
2. `php artisan legal:check` di server production harus lulus (gagal bila halaman legal masih memuat `[ISI` atau `[KEPUTUSAN`).
3. Tinjauan konsultan hukum atas keempat halaman.
4. Refund belum punya alur di aplikasi: ikuti [`docs/refund-manual.md`](docs/refund-manual.md).

### Langkah setiap deploy

```bash
php artisan down                               # opsional untuk rilis besar
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force  # izin admin baru (Pembayaran)
php artisan queue:restart                      # worker memuat kode job terbaru
php artisan optimize                           # cache config, route, event, view
php artisan view:cache                         # wajib: hindari kompilasi view di request pertama
php artisan filament:optimize
php artisan legal:check                        # production: gagal bila halaman legal masih ada [ISI]
php artisan up
```

### Mode under construction (`UNDER_CONSTRUCTION`)

Untuk pembaruan yang membuat halaman mentee belum layak dibuka, tapi tim tetap harus bisa bekerja di `/admin`:

1. Ubah `UNDER_CONSTRUCTION=true` di `.env` server.
2. Jalankan `php artisan optimize`. **Wajib**: config dan route di-cache, dan kondisi dibaca di `routes/web.php` saat route dimuat. Tanpa langkah ini perubahan env tidak terbaca.
3. Reload PHP-FPM bila OPcache `validate_timestamps=0`.
4. Untuk mematikan: `UNDER_CONSTRUCTION=false`, lalu `php artisan optimize` (dan reload PHP-FPM bila perlu).

Saat aktif, `/`, `/faq`, `/premium`, `/sitemap.xml`, `/persetujuan`, `/settings/*`, dan seluruh halaman mentee menampilkan halaman "Skular sedang diperbarui" (503, `Retry-After: 3600`, `noindex`; request JSON mendapat JSON 503). Yang tetap jalan: `/up`, `/health`, webhook Midtrans, panel `/admin`, empat halaman legal, login/daftar/reset password, dan login Google (setelah login mentee mendarat di halaman pemeliharaan).

Bedanya dengan `php artisan down`: mode bawaan Laravel memblokir **semua** request, termasuk admin dan webhook Midtrans (kecuali `/up` dan `/health`), dan tidak memakai env. Pakai `down` untuk jeda singkat saat deploy; pakai `UNDER_CONSTRUCTION` untuk pemeliharaan yang lebih lama.

Risiko yang tersisa: tab yang sudah terbuka sebelum mode aktif masih bisa mengirim aksi Livewire (`POST /livewire-*/update`, mis. menyimpan jawaban praktik), karena endpoint itu juga dipakai panel admin dan tidak diblokir.

### Kebutuhan runtime

| Komponen | Keterangan |
|---|---|
| Queue worker | Wajib bila `PREMIUM_ENABLED=true` atau ada event terbit (email pengingat event masuk antrean `reminders`): `php artisan queue:work --queue=payments,reminders,default` di bawah Supervisor (restart otomatis). Tanpa worker, pembayaran masuk tapi Pro belum aktif dan email tidak terkirim; panel admin menampilkan "Notifikasi tertahan" |
| Scheduler | Cron `* * * * * php artisan schedule:run`: pengingat deadline dan Pro berakhir, pengingat event (`events:remind` tiap 15 menit), antrean ulang pembayaran tertahan (`payments:requeue-stuck`), dan pengecekan order pending lebih dari 25 jam ke API Midtrans (`payments:reconcile-pending` tiap jam; order yang tidak pernah dibayar menjadi kedaluwarsa) |
| Email | `MAIL_MAILER` nyata (bukan `log`) untuk pengingat |
| URL notifikasi Midtrans | Isi di dashboard Midtrans: `{APP_URL}/payments/midtrans/notification` |
| PHP | 8.3+ dengan OPcache aktif; reload PHP-FPM setiap deploy bila `validate_timestamps=0` |
| Database | MySQL 8+ atau PostgreSQL 15+ (bukan SQLite) |
| Queue worker | **Tidak wajib** untuk penilaian AI praktik: penilaian pertama dan *Nilai ulang* berjalan sinkron di request web. Saat ini tidak ada job lain di codebase. Job `EvaluatePracticeFeedback` lama yang masih tersisa di tabel `jobs` tidak bisa dijalankan lagi (kelasnya dihapus) dan akan masuk `failed_jobs` bila ada worker; jawaban terkait tampil macet lalu mendapat tombol *Nilai ulang* setelah 75 detik |
| Timeout request | Penilaian praktik menahan request Livewire paling lama ±49 detik (22 dtk × 2 percobaan + jeda 5 dtk). Batas request di depan PHP harus **≥ 60 detik** (`PracticeSubmissionService::REQUEST_TIME_LIMIT_SECONDS`): nginx `fastcgi_read_timeout`/`proxy_read_timeout` (default 60), PHP-FPM `request_terminate_timeout` (0 atau ≥ 60), Apache/LiteSpeed (default 300), Cloudflare (100). Evaluasi menaikkan `max_execution_time` ke 60 bila lebih kecil. Bila batas server lebih pendek, turunkan dulu anggaran di kode (lihat `docs/ai-features.md`) |
| Storage | Persisten & privat: bukti pendaftaran di disk `local`; twibbon dan gambar kartu beasiswa (`scholarships/`, WebP ≤512 px hasil resize GD) di disk `public` (`php artisan storage:link`). GD dengan dukungan WebP disarankan; tanpa WebP gambar disimpan sebagai JPEG |
| HTTPS | Wajib; isi `TRUSTED_PROXIES` bila di belakang load balancer |
| Health check | `GET /up` (liveness), `GET /health` (database + cache, 503 bila gagal). Probe `/health/queue` sudah dihapus karena penilaian praktik tidak lagi lewat queue |
| Uji beban | `tests/Load/skular-smoke.js` (k6), jalankan ke staging |

> CI didefinisikan di `.github/workflows/tests.yml`, tetapi folder `/.github` saat ini tercantum di `.gitignore`, sehingga workflow tidak ikut ter-push. Lihat laporan dokumentasi.
