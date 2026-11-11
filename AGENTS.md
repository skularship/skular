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
| `app/Jobs` | `EvaluatePracticeFeedback` (penilaian AI di queue) |
| `app/Policies` | Policy Shield (hasil generate) |
| `resources/views` | `layouts/`, `components/` (dikompilasi Blaze), `partials/`, `livewire/` |
| `routes/web.php`, `routes/settings.php` | Semua route aplikasi (Fortify & Filament mendaftarkan route sendiri) |
| `tests/Feature` | Pest; SQLite in-memory |

## 2. Aturan produk yang tidak boleh dilanggar

1. **Model AI hanya dari `config/ai.php`** (`providers.openrouter.models.text.default`). Dilarang hardcode nama model, memasang `#[Model]`/`#[Provider]`/`#[UseCheapestModel]`/`#[UseSmartestModel]` di agent, atau mengganti model tanpa keputusan tim. Dijaga oleh `tests/Feature/Learning/AiResilienceTest.php`. Panggil `AiConfiguration::ensureTextModelConfigured()` sebelum `prompt()`.
2. **Jawaban mentee harus personal.** AI hanya menjelaskan, menilai, memberi kerangka, dan berdiskusi. Agent tidak boleh menulis jawaban akhir, tidak boleh mengisi/mengubah jawaban praktik, dan tidak boleh menyelesaikan checkpoint. Prompt memperlakukan input mentee sebagai DATA, bukan instruksi.
3. **Bantuan modul bertahap.** Urutan langkah: materi (Pahami) → **Quick Question** (kuis Benar/Salah, dikoreksi sistem) → Praktik. Di Praktik, chatbot AI Mentor menampilkan dulu **saran pertanyaan dari admin** (`modules.mentor_suggestions`, maks 5). Saran yang punya jawaban tim dijawab sistem tanpa LLM dan **tidak** memakai kuota; pertanyaan lain dikirim ke LLM dengan **limit per mentee per modul** dari `config('ai.module_chat.limit')` (`AI_MODULE_CHAT_LIMIT`, default 5). Kuota disimpan di tabel `module_mentor_usages`, direservasi dengan `lockForUpdate` sebelum memanggil model, dan dikembalikan bila panggilan gagal (`MentorQuotaService`). Jangan memindahkan pengecekan kuota ke frontend.
4. **Jawaban AI Mentor singkat dengan struktur PREP** (Point, Reason, Example, Point; ±120 kata) — lihat instruksi di `ModuleMentorAgent`.
5. **Mentee melihat label, bukan skor angka.** Gunakan `App\Enums\AiScoreLabel` (≥85 Sangat baik, 70–84 Baik, 50–69 Cukup, <50 Perlu diperbaiki). Skor 0–100 hanya untuk admin (Filament) dan perhitungan internal.
6. **Maksimal 5 beasiswa per mentee** — `ScholarshipApplicationService::MAX_SELECTIONS`, ditegakkan di `select()` dengan `lockForUpdate`.
7. **Gate tahap Feedback/Checkpoint di backend.** Mentee tidak boleh masuk langkah `checkpoint` (tandai selesai + feedback modul) sebelum semua latihan aktif tersimpan **dan** selesai dinilai AI. Ditegakkan di `ModulePlayer::guardCheckpointSection()` (mount, `goToSection`, perubahan `?section=`), `confirmCheckpoint()`, dan `saveModuleFeedback()`, plus lapisan kedua di `ProgressService::completeModule()` (`assertPracticeSubmitted()` menolak jawaban yang belum ada atau belum berstatus `completed`). Penilaian gagal/macet (>3 menit, `UserPracticeResponse::isStalled()`) harus selalu punya tombol "Nilai ulang".
8. **Bahasa materi memakai "kamu"**, sederhana, tidak kaku, tidak terdengar seperti tulisan AI.
9. **Data mentee adalah aset.** Jangan menghapus atau menimpa jawaban/progres mentee. Jawaban praktik disimpan dulu baru dinilai (`PracticeSubmissionService::submit()`), satu baris per mentee per form (`attempts` bertambah). Scholarship/Module/Fase dengan histori menolak `delete()`; gunakan status atau *Jadikan draf*. Migration hanya boleh additive; jangan mengubah migration lama.
10. **Penyebutan nama beasiswa:** deskriptif, tanpa logo penyelenggara, tanpa kata "resmi"/"partner"/"mitra" yang menyiratkan afiliasi, sertakan disclaimer bahwa Skular tidak berafiliasi, dan tautan ke sumber resmi (`scholarships.link`). Label tautan "Situs resmi" diperbolehkan karena merujuk ke sumber. *Catatan: disclaimer belum ada di UI saat dokumen ini ditulis.*

## 3. Konvensi kode

- **Controller/komponen tipis, logika di Service.** Livewire dan Filament meng-inject interface dari `app/Contracts`, bukan class konkret. Service baru = interface + class + binding di `AppServiceProvider`.
- **Type hint native** untuk setiap parameter, return, dan property bila tipenya pasti. Jangan menambah tipe yang bentrok dengan parent/interface (mis. `Model::$table`, `toResponse($request)` milik Fortify). **Jangan menambahkan `declare(strict_types=1)`** ke file lama.
- **Docblock wajib**: per class (1–3 kalimat: tanggung jawab + fitur), per method publik (`@param`, `@return`, `@throws`), array shape (`array{question: string, answer: string|null}`), `@property`/`@property-read` di model untuk setiap kolom dan relasi. Komentar inline hanya untuk aturan bisnis atau workaround yang tidak jelas.
- Penamaan: variabel/properti `snake_case` (mengikuti kode yang ada), method `camelCase` deskriptif (`isRegisteredForDiscounts`), enum case `PascalCase`.
- DTO di `app/Data`; jangan mengirim model Eloquent ke cache (`ContentCache` hanya menyimpan scalar/array). Panggil `ContentCache::bump()` saat Fase/Module/Scholarship/ModuleCoreSection disimpan/dihapus (sudah di hook model).
- Copy UI Bahasa Indonesia; test meng-assert string Indonesia.
- UI **light-only** (dijaga `LightModeTest`); jangan menambah pengaturan tema.
- Ikon: `<x-icons.app-icon>` (render via `svg()`); jangan diganti `<x-dynamic-component>`.
- Teks materi kurikulum (kata pengantar, ringkasan, Inti Materi) ditulis **Markdown** dan dirender lewat `<x-learning.rich-content>` (`RichContentRendererInterface`, gaya `.sk-prose` di `resources/css/prose.css`). HTML mentah dibuang; jangan echo teks materi dari database dengan `{!! !!}` di tempat lain.
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
| Penilaian praktik | `PracticeSubmissionService`, `EvaluatePracticeFeedback`, `UserPracticeResponse` | Jawaban hilang/tertimpa, penilaian ganda, status macet di `processing` |
| Kuota chat | `MentorQuotaService`, `MentorChat`, `module_mentor_usages` | Kuota bisa dilewati atau terpotong saat AI gagal |
| Gate checkpoint | `ModulePlayer` (`checkpointBlock`, `guardCheckpointSection`, `confirmCheckpoint`, `saveModuleFeedback`) + `ProgressService::assertPracticeSubmitted()` | Mentee melompati penilaian AI |
| Progres & jalur belajar | `ProgressService`, `LearningPathService`, `ScholarshipPathService`, `checkpoints` | Modul terbuka/terkunci salah, progres hilang |
| Pilihan beasiswa | `ScholarshipApplicationService` | Melewati batas 5 |
| Migration & data produksi | `database/migrations` | Kehilangan data; jangan ubah migration lama |
| Auth | `FortifyServiceProvider`, `app/Http/Responses`, `GoogleAuthService` | Akun tertaut salah |
| Policy/role | `app/Policies`, `RoleSeeder`, `config/filament-shield.php` | Admin mendapat akses berlebih. `shield:generate` dapat menimpa docblock policy |
| Pembayaran/coin | — | Belum ada di codebase |

**Lingkungan:** `.env` lokal bisa saja menunjuk ke database bersama/production (pernah ditemukan `DB_HOST` remote). Periksa `DB_*` sebelum `migrate`, `db:seed`, `tinker` yang menulis, atau menjalankan server lokal (cache/session `database` ikut menulis). Uji migration pada salinan SQLite. Test selalu memakai SQLite `:memory:` (`phpunit.xml`).

## 6. Checklist sebelum commit

- [ ] `composer test` lulus (Pint test, PHPStan level 7, seluruh Pest). Bila PHPStan kehabisan memori: `vendor/bin/phpstan analyse --memory-limit=2G`.
- [ ] Tidak ada nama model AI di luar `config/ai.php` (`grep -rnE "deepseek|gpt-|claude-|gemini-" app resources routes` kosong) dan tidak ada `#[Model]`/`#[Provider]` di agent.
- [ ] Tidak ada secret/API key/password di kode, test, atau dokumentasi; variabel baru ditambahkan ke `.env.example` (opsional = baris komentar).
- [ ] Mentee tidak melihat skor angka; copy baru memakai "kamu".
- [ ] Migration baru additive dan diuji pada salinan data.
- [ ] Perubahan frontend: `npm run build` lalu commit semua file baru di `public/build`.
- [ ] Docblock/komentar header untuk class/method/view baru; README, `docs/`, dan AGENTS.md diperbarui bila perilaku berubah.
- [ ] Hanya file milik perubahan ini yang di-stage.

## 7. Perintah & catatan teknis

- `composer setup` — bootstrap penuh; `composer dev` — serve + `queue:listen` + vite. Queue `database`: penilaian AI praktik butuh worker.
- `composer test` = `config:clear` → `pint --parallel --test` → `phpstan analyse` → `artisan test`. CI menjalankan `composer ci:check` (rantai yang sama).
- Satu test: `vendor/bin/pest tests/Feature/... --filter=...`.
- `tests/Pest.php` sudah memasang `TestCase` + `RefreshDatabase` untuk `tests/Feature`; jangan menambahkannya lagi per file. `tests/Load/skular-smoke.js` adalah skrip k6, bukan Pest.
- Provider error diklasifikasikan `App\Enums\AiFailureReason` (configuration / rate_limited / timeout / unavailable) dan dicatat dengan key `reason`.
- Blaze mengompilasi `resources/views/components`; setelah mengubah `BLAZE_ENABLED`/config Blaze jalankan `php artisan view:clear` (view basi melempar `Undefined variable $__blaze`).
- `.npmrc` memakai `ignore-scripts=true`. `public/build` di-commit.

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
