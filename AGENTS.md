# AGENTS.md — Panduan untuk AI coding agent dan developer Skular

Baca file ini sebelum mengubah kode. Jika isi file ini berbeda dengan kode/config, **kode yang benar**; perbarui file ini di commit yang sama.

## 1. Ringkasan proyek

Skular (skularship.com) adalah platform persiapan beasiswa: profiling 8 langkah → rekomendasi beasiswa (maks 5) → roadmap modul (Pembukaan → Pahami → Quick Question → Praktik → Checkpoint) dengan penilaian AI, AI Mentor, dan AI Brainstorming. Basis: `laravel/livewire-starter-kit` + panel Filament 5 di `/admin`. Stack: Laravel 13, PHP 8.5, Livewire 4 (`Route::livewire()`), Tailwind 4, Vite Plus, Pest 5, Larastan level 7, Pint. Copy UI dalam Bahasa Indonesia; kode dan docblock dalam bahasa Inggris.

Dokumentasi lengkap: `README.md` (setup, env, fitur, deploy) dan `docs/` (`architecture.md`, `database.md`, `ai-features.md`, `routes.md`).

| Folder | Isi |
|---|---|
| `app/Services` + `app/Contracts` | Business logic di balik interface; binding di `AppServiceProvider::registerServiceBindings()` |
| `app/Livewire` | Halaman/komponen publik (tipis; memanggil interface service) |
| `app/Filament` | Panel admin: `Resources/{Nama}/{Pages,Schemas,Tables,RelationManagers}`, `Widgets`, `Actions` |
| `app/Ai` | `AiConfiguration` + `Agents/` (ModuleMentorAgent, ModuleBrainstormingAgent, PracticeFeedbackAgent) |
| `app/Data`, `app/Enums`, `app/Exceptions` | DTO (spatie/laravel-data), enum domain, exception dengan pesan ramah |
| `app/Policies` | Policy Shield (hasil generate) |
| `resources/views` | `layouts/`, `components/` (dikompilasi Blaze), `partials/`, `livewire/` |
| `routes/web.php`, `routes/settings.php` | Semua route aplikasi (Fortify & Filament mendaftarkan route sendiri) |
| `tests/Feature` | Pest; SQLite in-memory |

## 2. Aturan produk yang tidak boleh dilanggar

1. **Model AI hanya dari `config/ai.php`** (`providers.openrouter.models.text.default`). Dilarang hardcode nama model, memasang `#[Model]`/`#[Provider]`/`#[UseCheapestModel]`/`#[UseSmartestModel]` di agent, atau mengganti model tanpa keputusan tim. Dijaga oleh `tests/Feature/Learning/AiResilienceTest.php`. Panggil `AiConfiguration::ensureTextModelConfigured()` sebelum `prompt()`.
2. **Jawaban mentee harus personal.** AI hanya menjelaskan, menilai, memberi kerangka, dan berdiskusi. Agent tidak boleh menulis jawaban akhir, tidak boleh mengisi/mengubah jawaban praktik, dan tidak boleh menyelesaikan checkpoint. Prompt memperlakukan input mentee sebagai DATA, bukan instruksi.
3. **Bantuan modul bertahap.** Urutan langkah: materi (Pahami) → **Quick Question** (kuis Benar/Salah, dikoreksi sistem) → Praktik. Di Praktik, chatbot AI Mentor menampilkan dulu **saran pertanyaan dari admin** (`modules.mentor_suggestions`, maks 5). Saran yang punya jawaban tim dijawab sistem tanpa LLM dan **tidak** memakai kuota; pertanyaan lain dikirim ke LLM dengan **limit per mentee per modul** dari `config('ai.module_chat.limit')` (`AI_MODULE_CHAT_LIMIT`, default 5). Kuota disimpan di tabel `module_mentor_usages`, direservasi dengan `lockForUpdate` sebelum memanggil model, dan dikembalikan bila panggilan gagal (`MentorQuotaService`). Jangan memindahkan pengecekan kuota ke frontend.
4. **Jawaban AI Mentor singkat dengan struktur PREP** (Point, Reason, Example, Point; ±120 kata) — lihat instruksi di `ModuleMentorAgent`.
5. **Mentee melihat label, bukan skor angka, untuk penilaian praktik dan checkpoint modul.** Gunakan `App\Enums\AiScoreLabel` (≥85 Sangat baik, 70–84 Baik, 50–69 Cukup, <50 Perlu diperbaiki). Skor 0–100 hanya untuk admin (Filament) dan perhitungan internal. **Pengecualian: Try Out** menampilkan skor mentah dan band estimasi (0–9, kelipatan 0,5) karena itu tujuan fiturnya, selalu dengan label "Estimasi latihan" + disclaimer `config('tryout.disclaimer')`. Jangan memakai `AiScoreLabel` di Try Out, dan jangan menampilkan band Try Out di halaman modul (dijaga `TryoutPagesTest`). **Tes IQ** juga pengecualian: menampilkan jumlah benar dan persen per subtes + kategori dari `config('iq.categories')`, hanya untuk akses berbayar, selalu dengan disclaimer `config('iq.disclaimer')` (aturan 15).
6. **Maksimal 5 beasiswa per mentee** — `ScholarshipApplicationService::MAX_SELECTIONS`, ditegakkan di `select()` dengan `lockForUpdate`.
7. **Gate tahap Feedback/Checkpoint di backend.** Mentee tidak boleh masuk langkah `checkpoint` (masukan modul + tandai selesai) sebelum semua latihan aktif tersimpan **dan** selesai dinilai AI. Ditegakkan di `ModulePlayer::guardCheckpointSection()` (mount, `goToSection`, perubahan `?section=`), `confirmCheckpoint()`, `submitFeedbackAndContinue()`, dan `saveModuleFeedback()`, plus lapisan kedua di `ProgressService::completeModule()` (`assertPracticeSubmitted()` menolak jawaban yang belum ada atau belum berstatus `completed`). **Masukan modul wajib**: modul tidak bisa diselesaikan tanpa `module_feedbacks.helpful_score` (`ModuleFeedbackService::submit()`, `ProgressService::assertFeedbackSubmitted()`); modul yang sudah selesai tidak diminta mengisi mundur. Modul baru bisa dibuka setelah mentee menyetujui komitmen akses gratis (`users.feedback_commitment_at`, dicek di `ModulePlayer::mount()`). Test yang menyelesaikan modul memakai helper `giveModuleFeedback()` (`tests/Pest.php`). Penilaian gagal/macet (>75 detik tanpa heartbeat, `UserPracticeResponse::isStalled()`) harus selalu punya tombol "Nilai ulang". Penilaian pertama dan "Nilai ulang" memakai satu jalur: evaluasi **sinkron di dalam request** (`PracticeSubmissionService::evaluate()`, tanpa queue/worker) di bawah `Cache::lock` per jawaban; Throwable apa pun mengubah status versi jawaban itu menjadi `failed` (tidak pernah menimpa `completed` atau versi yang lebih baru) lalu melepas lock. Angka timeout agent × percobaan + jeda, batas request (`REQUEST_TIME_LIMIT_SECONDS` 60, margin 10), lock, dan jendela macet saling terkait dan dijaga `PracticeFeedbackReliabilityTest`; ubah serentak.
8. **Bahasa materi memakai "kamu"**, sederhana, tidak kaku, tidak terdengar seperti tulisan AI.
9. **Data mentee adalah aset.** Jangan menghapus atau menimpa jawaban/progres mentee. Jawaban praktik disimpan dulu baru dinilai (`PracticeSubmissionService::submit()`), satu baris per mentee per form (`attempts` bertambah). Scholarship/Module/Fase dengan histori menolak `delete()`; gunakan status atau *Jadikan draf*. Migration hanya boleh additive; jangan mengubah migration lama.
10. **Penyebutan nama beasiswa:** deskriptif, tanpa logo penyelenggara, tanpa kata "resmi"/"partner"/"mitra" yang menyiratkan afiliasi, sertakan disclaimer bahwa Skular tidak berafiliasi, dan tautan ke sumber resmi (`scholarships.link`). Label tautan "Situs resmi" diperbolehkan karena merujuk ke sumber. Disclaimer tampil di footer landing dan di Syarat & Ketentuan.
11. **Halaman legal dan persetujuan.** Empat halaman (`/kebijakan-privasi`, `/syarat-ketentuan`, `/kebijakan-cookie`, `/kebijakan-pengembalian-dana`) adalah Blade statis di `resources/views/legal`, isinya mengikuti kode dan mengambil fakta dari `config/legal.php` (identitas usaha lewat env `LEGAL_*`; yang belum diputuskan = penanda `[ISI: ...]`, dan `php artisan legal:check` gagal di production selama penanda masih ada). **Naikkan `legal.documents.{dokumen}.version` dan `effective_at` setiap isi dokumen berubah**: versi `terms`/`privacy` baru membuat semua mentee menyetujui ulang lewat `EnsureLegalConsent` (`/persetujuan`), versi `cookies` baru memunculkan banner lagi. Persetujuan dicatat hanya lewat `ConsentServiceInterface` di `user_consents` (tanpa IP): daftar (`CreateNewUser`, checkbox divalidasi di server), login Google pertama, checkout (`Premium\Index::checkout`), persetujuan ulang. Teks legal tidak boleh memuat "jaminan lolos", "tidak dapat dikembalikan", "partner"/"mitra", atau "resmi" selain "situs resmi" (dijaga `LegalPagesTest`), dan tidak menulis nominal harga.
12. **Analytics hanya setelah persetujuan.** GA4 (gtag.js) tidak boleh dimuat sebelum pengunjung memilih "Terima semua"/mengaktifkan Analitik di banner cookie (`partials/cookie-consent`, `resources/js/cookie-consent.js`, `partials/analytics`). Skrip pelacak baru wajib lewat mekanisme yang sama dan masuk tabel di Kebijakan Cookie. Cookie `skular_cookie_consent` dikecualikan dari enkripsi di `bootstrap/app.php`. Banner berada di alur halaman (bukan `fixed`) agar tidak menutupi form/tombol bayar.
13. **Try Out (simulasi IELTS, `app/**/Tryout*`, `/tryout`).** Domain terpisah dari alur modul (tidak memakai `PracticeForm`/checkpoint). Aturan: kunci, jawaban alternatif, kerangka, pembahasan, dan naskah audio di-`#[Hidden]` di model dan **tidak pernah** masuk state Livewire/HTML akun Gratis; halaman tes hanya menerima `TryoutQuestionViewData`, kunci hanya lewat `TryoutResultService::review()` yang menolak non-Pro (`EntitlementServiceInterface::can($user, 'tryout_review' | 'tryout_mentor' | 'tryout_theory')`, `tryoutAttemptLimit()` untuk jatah attempt Gratis). Timer dari server (`deadline_at` + `tryout.answer_grace_seconds`). Listening/Reading adaptif blok unit (`AdaptiveQuestionSelector`): soal disimpan sebagai baris `tryout_answers` **sebelum** ditampilkan (resume), jawaban terkunci setelah dikirim, hanya soal aktif yang diterima (`lockForUpdate`). Writing/Speaking dinilai AI sinkron **satu panggilan per request** (satu task Writing, satu penilaian Speaking) di bawah `Cache::lock`, gagal = `failed` + "Nilai ulang" (maks `tryout.ai.max_retries_per_answer`), tidak menimpa `completed`; batas waktu di `App\Support\TryoutAiBudget` (dijaga `TryoutAiReliabilityTest`). Speaking ditranskripsi **di browser mentee** (Web Speech API, `resources/js/speech-recorder.js`, gratis): server tidak menerima maupun menyimpan audio, hanya teks transkrip + lama bicara (`TryoutAnswerService::saveSpokenAnswer()`); transkrip adalah input mentee yang tidak tepercaya (DATA, bukan instruksi). Browser tanpa pengenalan suara (Firefox) mendapat peringatan di intro dan di Speaking. Rumus skor per jenis ujian lewat `TryoutScoringStrategyInterface` (v1: `IeltsScoringStrategy`, tabel band di `config/tryout.php` masih SEMENTARA sampai dokumen v0.2). Modul `is_tryout_theory` disembunyikan dari Perpustakaan dan 404 di `ModulePlayer`. **PDF teori Try Out tidak boleh bisa diunduh:** hanya dibaca Pro lewat viewer di halaman Teori (`resources/js/pdf-viewer.js`, pdf.js ke canvas tanpa lapisan teks, watermark nama + email, unduh/cetak/klik kanan diblokir); `TryoutTheoryPdfController` hanya melayani `fetch` ber-header `X-Skular-Viewer` dengan isi ter-masking XOR dan `no-store`. Jangan menambah route unduh atau `->downloadable()` untuk mentee.
14. **Data diri CV tampil di modul sebagai referensi saja.** Panel "Data dirimu" di langkah Praktik tidak pernah mengisi atau mengirim jawaban otomatis, tidak menulis `$answers`, tidak memanggil AI, tidak menampilkan skor/label, dan tidak memengaruhi gate checkpoint. Sumber ditentukan admin lewat `module_cv_sources` (editor modul, section "6. Data diri dari CV mentee"); hanya form `include_in_cv` dari modul lain, divalidasi di server (`ModuleForm::invalidCvSourceMessage()`). Data dibaca hanya untuk user yang login (`CvServiceInterface::sectionsForModule()`), `ModulePlayer::toggleCvSource()` memverifikasi id terhadap sumber modul itu, dan `open_cv_source` `#[Locked]`. CV dan panel hanya memakai form aktif di modul terbit, mengikuti skema `fields` saat ini, dan tidak pernah menampilkan field `file` atau kode jawaban internal (`PracticeForm::cvSources()`, `CvService`). Mentee memperbaiki datanya di modul asal.
15. **Tes IQ Latihan (`app/**/Iq*`, `/tes-iq`).** Latihan, bukan tes IQ resmi: **angka IQ tidak boleh dihitung, disimpan, atau ditampilkan di mana pun** (tidak ada tabel norma; mentor AI juga dilarang menyebutnya), dan disclaimer `config('iq.disclaimer')` tampil di halaman awal, runner, dan hasil. Soal gratis; skor, kategori, kunci, pembahasan, materi, dan mentor AI hanya bila `EntitlementServiceInterface::can($user, 'iq_premium')` (saat ini = Pro; satu akses per akun). Runner hanya menerima `IqQuestionViewData` (`answer_key`/`explanation` `#[Hidden]`), halaman hasil tanpa akses hanya memuat jumlah soal dikerjakan. Waktu dari server (`deadline_at` + `iq.answer_grace_seconds`); jawaban disimpan setiap dipilih dan bisa diganti sampai Kirim/waktu habis; satu percobaan `in_progress` per akun (`lockForUpdate` baris user); skor dihitung di server saat percobaan ditutup (`IqScoringService`, kunci saat itu). Pesan dukungan hanya dari hitungan jawaban mentee di 25/50/75% (tanpa benar/salah, pujian umum, atau perbandingan). Soal hanya aktif bila `IqQuestion::activationErrors()` kosong (Visuospasial wajib gambar + teks alternatif). Kuota mentor per akun per hari (`iq_mentor_usages`). PDF materi mengikuti aturan PDF teori Try Out (viewer saja, tanpa unduh). Dijaga `tests/Feature/Iq/*`.

## 3. Konvensi kode

- **Controller/komponen tipis, logika di Service.** Livewire dan Filament meng-inject interface dari `app/Contracts`, bukan class konkret. Service baru = interface + class + binding di `AppServiceProvider`.
- **Type hint native** untuk setiap parameter, return, dan property bila tipenya pasti. Jangan menambah tipe yang bentrok dengan parent/interface (mis. `Model::$table`, `toResponse($request)` milik Fortify). **Jangan menambahkan `declare(strict_types=1)`** ke file lama.
- **Docblock wajib**: per class (1–3 kalimat: tanggung jawab + fitur), per method publik (`@param`, `@return`, `@throws`), array shape (`array{question: string, answer: string|null}`), `@property`/`@property-read` di model untuk setiap kolom dan relasi. Komentar inline hanya untuk aturan bisnis atau workaround yang tidak jelas.
- Penamaan: variabel/properti `snake_case` (mengikuti kode yang ada), method `camelCase` deskriptif (`isRegisteredForDiscounts`), enum case `PascalCase`.
- DTO di `app/Data`; jangan mengirim model Eloquent ke cache (`ContentCache` hanya menyimpan scalar/array). Panggil `ContentCache::bump()` saat Fase/Module/Scholarship/ModuleCoreSection disimpan/dihapus (sudah di hook model).
- Copy UI Bahasa Indonesia; test meng-assert string Indonesia.
- **Email selalu Bahasa Indonesia:** `User::preferredLocale()` = `id`, jadi semua email/notifikasi ke user dirender dengan locale `id` (nama bulan Indonesia) tanpa mengubah `APP_LOCALE`. String bawaan Laravel (verifikasi, reset password, salam, footer) diterjemahkan di `lang/id.json`; kirim ke model `User` (`Mail::to($user)`, `$user->notify()`), bukan string alamat. Dijaga `tests/Feature/Mail/EmailLanguageTest.php`.
- UI **light-only** (dijaga `LightModeTest`); jangan menambah pengaturan tema.
- Ikon: `<x-icons.app-icon>` (render via `svg()`); jangan diganti `<x-dynamic-component>`.
- **Layout baru** harus menyertakan `@include('partials.cookie-consent')` di awal `<body>` dan `<x-legal.links>` di footer/area bawah. Route aplikasi baru yang butuh login masuk grup `auth`+`verified`+`legal.consent`. Factory `User` otomatis membuat persetujuan versi terkini; pakai `->withoutConsent()` untuk menguji alur persetujuan.
- **Mode under construction:** route publik/mentee baru di `routes/web.php` wajib masuk grup `Route::middleware($under_construction_middleware)` (middleware `under.construction`, saklar `UNDER_CONSTRUCTION`). Route kritis (`/health`, webhook `routes/payments.php`, admin Filament, halaman legal, login Google) tidak boleh masuk grup itu.
- **Product Tour:** halaman baru di alur mentee → tambahkan langkahnya di `ProductTourService::steps()` (copy "kamu", label bukan skor) dan sematkan `<livewire:onboarding.product-tour tour="..." />`; target memakai hook `data-test`/`data-tour` yang stabil (target hilang = kartu di tengah).
- Teks materi kurikulum dirender lewat `<x-learning.rich-content>` (`RichContentRendererInterface`, gaya `.sk-prose` di `resources/css/prose.css`). Kata pengantar, ringkasan, dan `inti_material` lama ditulis **Markdown** (HTML mentah dibuang; dibaca AI sebagai teks polos). Kolom Inti Materi (`module_core_sections.body`) berformat sesuai `body_format`: `markdown` (data lama) atau `html` dari RichEditor, dirender dengan prop `format` lewat sanitizer allowlist (`toHtmlFromFormat()`): `style` tersimpan selalu dibuang dan disusun ulang hanya dari warna/font/ukuran huruf di `config/content.php` (`App\Support\MaterialStyles`). Jangan echo teks materi dari database dengan `{!! !!}` di luar komponen itu.
- Format: `vendor/bin/pint --format agent <path>`.
- **Commit:** satu commit per perubahan logis, pesan dalam Bahasa Indonesia dengan prefix tipe (`feat(...)`, `fix(...)`, `docs(...)`, `build:`), badan menjelaskan *apa* dan *mengapa*. Stage file secara eksplisit; jangan `git add -A` bila ada perubahan orang lain di working tree.

## 4. Cara menambah fitur baru

Urutan file yang biasanya disentuh:

1. **Migration** baru (`php artisan make:migration ... --no-interaction`), additive dan nullable/berdefault agar data lama aman.
2. **Model** (+ factory) dengan `#[Fillable]`, `casts()`, `@property`/`@property-read`, dan relasi bertipe generik (`@return HasMany<X, $this>`).
3. **Interface** di `app/Contracts` + **Service** di `app/Services`, lalu binding di `AppServiceProvider`.
4. **Livewire** component (halaman: `Route::livewire()` di `routes/web.php` dalam grup `auth`+`verified`) atau aksi pada component yang ada. Validasi dan otorisasi di action server.
5. **View** Blade di `resources/views/livewire/...` dengan komentar header.
6. **Admin:** field di `Schemas/*Form.php` / kolom di `Tables/*Table.php`. Resource baru → `php artisan shield:generate --all --panel=admin` lalu `php artisan db:seed --class=RoleSeeder`.
7. **Test** Pest di `tests/Feature/...` (`php artisan make:test --pest Nama`). AI selalu di-fake: `ModuleMentorAgent::fake([...])->preventStrayPrompts()`.
8. Dokumentasi: README (fitur/env), `docs/*`, dan file ini bila menyentuh aturan.

Contoh nyata: kuota AI Mentor = migration `create_module_mentor_usages_table` → model `ModuleMentorUsage` → `MentorQuotaServiceInterface` + `MentorQuotaService` → binding → dipakai di `MentorChat::callMentor()` → view `mentor-chat.blade.php` → `tests/Feature/Learning/MentorChatQuotaTest.php` → config `ai.module_chat.limit`.

## 5. Area sensitif (ubah dengan sangat hati-hati)

| Area | File | Risiko |
|---|---|---|
| Konfigurasi & agent AI | `config/ai.php`, `app/Ai/**`, `app/Services/{AiFeedback,ModuleMentor,ModuleBrainstorming}Service.php` | Biaya provider, ganti model tanpa sengaja, jawaban AI menulis jawaban mentee |
| Penilaian praktik | `PracticeSubmissionService` (evaluasi sinkron + lock), `UserPracticeResponse` | Jawaban hilang/tertimpa, penilaian ganda, status macet di `processing`, request melewati timeout web server |
| Kuota chat | `MentorQuotaService`, `MentorChat`, `module_mentor_usages` | Kuota bisa dilewati atau terpotong saat AI gagal |
| Halaman legal & persetujuan | `config/legal.php`, `resources/views/legal/*`, `EnsureLegalConsent`, `ConsentService`, `partials/analytics`, `resources/js/cookie-consent.js` | Teks tidak sesuai kode, GA4 termuat tanpa izin, mentee terkunci di layar persetujuan, persetujuan tidak tercatat |
| Gate checkpoint | `ModulePlayer` (`checkpointBlock`, `guardCheckpointSection`, `confirmCheckpoint`, `submitFeedbackAndContinue`, `saveModuleFeedback`) + `ProgressService::assertPracticeSubmitted()` / `assertFeedbackSubmitted()` | Mentee melompati penilaian AI atau masukan modul |
| Progres & jalur belajar | `ProgressService`, `LearningPathService`, `ScholarshipPathService`, `checkpoints` | Modul terbuka/terkunci salah, progres hilang |
| Pilihan beasiswa | `ScholarshipApplicationService` | Melewati batas 5 |
| Data diri CV / panel referensi | `CvService`, `PracticeForm::cvSources()`, `ModulePlayer::toggleCvSource()`, `partials/cv-reference-panel.blade.php`, `module_cv_sources` | Jawaban mentee lain bocor, path file/kode internal tampil, panel menulis ke jawaban praktik |
| Try Out | `TryoutAnswerService`, `TryoutAttemptService`, `AdaptiveQuestionSelector`, `WritingAssessmentService`, `SpeakingAssessmentService`, `TryoutResultService`, `config/tryout.php` | Kunci bocor ke akun Gratis, jawaban diterima setelah waktu habis, soal teracak ulang saat refresh, penilaian AI melewati batas request, rekaman tidak terhapus sesuai retensi |
| Tes IQ | `IqAttemptService`, `IqScoringService`, `IqResultService`, `config/iq.php`, `plans.*.iq_premium` | Skor/kunci bocor ke akun tanpa akses, jawaban diterima setelah waktu habis, angka IQ muncul, dua percobaan aktif |
| Migration & data produksi | `database/migrations` | Kehilangan data; jangan ubah migration lama |
| Auth | `FortifyServiceProvider`, `app/Http/Responses`, `GoogleAuthService` | Akun tertaut salah |
| Policy/role | `app/Policies`, `RoleSeeder`, `config/filament-shield.php` | Admin mendapat akses berlebih. `shield:generate` dapat menimpa docblock policy |
| Pembayaran (Midtrans Snap) | `CheckoutService`, `PaymentWebhookService`, `SubscriptionService`, `PricingService`, `MidtransGateway` | Prepaid tanpa auto-renew. Webhook satu-satunya sumber kebenaran (tanda tangan SHA512 + cek `gross_amount`, idempoten). Harga selalu dari server. Fitur dimatikan lewat `PREMIUM_ENABLED=false`; promo launching mati bila `PRICING_LAUNCHING_UNTIL` kosong. Webhook hanya menyimpan lalu mengantrekan `ProcessMidtransNotification` (antrean `payments`). Order `pending` >25 jam dicek ke API status Midtrans oleh `payments:reconcile-pending` (tiap jam): tanpa transaksi = `expired`, status final lewat job yang sama. Aturan paket di `config/plans.php` lewat `EntitlementService`. Vault, checklist, reminder email (antrean `reminders`) hanya Pro. Event `audience = pro` hanya untuk Pro; Gratis hanya boleh menonton satu rekaman bertanda `events.is_free_preview` (`EventService::canWatchRecording()`). Halaman harga (`premium/index`, harga di `welcome`) hanya mencantumkan fitur Pro yang sudah ada di kode. Kode referral 10% (`ReferralService`) hanya paket 12 bulan saat launching. Pengingat Pro berakhir H-7/H-1. Admin: grup menu Pembayaran (Produk, Pesanan read-only, Langganan, Notifikasi Midtrans). Coin belum ada. |

**Lingkungan:** `.env` lokal bisa saja menunjuk ke database bersama/production (pernah ditemukan `DB_HOST` remote). Periksa `DB_*` sebelum `migrate`, `db:seed`, `tinker` yang menulis, atau menjalankan server lokal (cache/session `database` ikut menulis). Jangan menjalankan `queue:listen`/`queue:work` lokal dengan `.env` yang menunjuk DB produksi: worker lokal akan mengambil job apa pun dari tabel `jobs` bersama (saat ini tidak ada job; penilaian praktik sinkron). Uji migration pada salinan SQLite. Test selalu memakai SQLite `:memory:` (`phpunit.xml`).

## 6. Checklist sebelum commit

- [ ] `composer test` lulus (Pint test, PHPStan level 7, seluruh Pest). Bila PHPStan kehabisan memori: `vendor/bin/phpstan analyse --memory-limit=2G`.
- [ ] Tidak ada nama model AI di luar `config/ai.php` (`grep -rnE "deepseek|gpt-|claude-|gemini-" app resources routes` kosong) dan tidak ada `#[Model]`/`#[Provider]` di agent.
- [ ] Tidak ada secret/API key/password di kode, test, atau dokumentasi; variabel baru ditambahkan ke `.env.example` (opsional = baris komentar).
- [ ] Mentee tidak melihat skor angka; copy baru memakai "kamu".
- [ ] Perubahan isi halaman legal: versi + tanggal berlaku di `config/legal.php` dinaikkan; data/cookie/penerima baru tercermin di Kebijakan Privasi/Cookie.
- [ ] Migration baru additive dan diuji pada salinan data.
- [ ] Perubahan frontend: `npm run build` lalu commit semua file baru di `public/build`.
- [ ] Docblock/komentar header untuk class/method/view baru; README, `docs/`, dan AGENTS.md diperbarui bila perilaku berubah.
- [ ] Hanya file milik perubahan ini yang di-stage.

## 7. Perintah & catatan teknis

- `composer setup` — bootstrap penuh; `composer dev` — serve + vite. Tidak ada job queue: penilaian AI praktik berjalan sinkron di request Livewire (worker tidak dibutuhkan; batas request web server harus ≥ 60 detik, lihat README bagian deploy). Infrastruktur queue `database` (tabel `jobs`, `failed_jobs`) dibiarkan untuk job di masa depan.
- `composer test` = `config:clear` → `pint --parallel --test` → `phpstan analyse` → `artisan test`. CI menjalankan `composer ci:check` (rantai yang sama).
- Satu test: `vendor/bin/pest tests/Feature/... --filter=...`.
- `tests/Pest.php` sudah memasang `TestCase` + `RefreshDatabase` untuk `tests/Feature`; jangan menambahkannya lagi per file. `tests/Load/skular-smoke.js` adalah skrip k6, bukan Pest.
- Provider error diklasifikasikan `App\Enums\AiFailureReason` (configuration / rate_limited / timeout / unavailable) dan dicatat dengan key `reason`.
- Blaze mengompilasi `resources/views/components`; setelah mengubah `BLAZE_ENABLED`/config Blaze jalankan `php artisan view:clear` (view basi melempar `Undefined variable $__blaze`).
- `.npmrc` memakai `ignore-scripts=true`. `public/build` di-commit.
- Editor Inti Materi (RichEditor) memakai plugin `App\Filament\RichEditor\FontFamilyPlugin` dan `FontSizePlugin`; mark JS-nya di `resources/js/filament/rich-content-plugins/{font-family,font-size}.js` (memakai TipTap milik Filament, tanpa bundler). Font baru di `config/content.php` juga harus ditambahkan ke `bunny()` di `vite.config.js` dan `@fonts([...])` di `AdminPanelProvider`. Setelah mengubah file JS itu jalankan `php artisan filament:assets` dan commit `public/js/app/rich-content-plugins/`. `config/livewire.php` menaikkan `payload.max_nesting_depth` ke 32 karena dokumen TipTap di dalam Repeater melebihi batas bawaan 10 (gejala: error 500 `MaxNestingDepthExceededException` saat menyimpan modul).

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
