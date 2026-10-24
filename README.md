# Skular

Skular adalah platform pembelajaran beasiswa berbasis AI untuk mahasiswa dan Gen Z. Skular memetakan profil pengguna lewat wizard 8 langkah, mencocokkannya dengan beasiswa yang relevan (scholarship matching), lalu menuntun pengguna belajar melalui roadmap modular dengan praktik dan feedback dari AI.

---

## Daftar Isi

- [Tech Stack](#tech-stack)
- [Arsitektur](#arsitektur)
- [Fitur](#fitur)
- [Aturan Bisnis (Beasiswa & Modul)](#aturan-bisnis-beasiswa--modul)
- [Alur Kerja Penyusun Kurikulum (Admin)](#alur-kerja-penyusun-kurikulum-admin)
- [Struktur Halaman Frontend](#struktur-halaman-frontend)
- [Endpoint](#endpoint)
  - [Halaman Publik (Livewire)](#1-halaman-publik-livewire)
  - [Autentikasi (Fortify)](#2-autentikasi-fortify)
  - [Passkeys (WebAuthn)](#3-passkeys-webauthn)
  - [OAuth Google](#4-oauth-google)
  - [Livewire RPC](#5-livewire-rpc)
  - [Admin Filament (CRUD)](#6-admin-filament-crud)
- [Cara Menjalankan Project](#cara-menjalankan-project)
- [Perintah Lainnya](#perintah-lainnya)
- [Testing & Quality Gates](#testing--quality-gates)
- [Akun Admin](#akun-admin)

---

## Tech Stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13 (PHP 8.5) |
| Frontend interaktif | Livewire 4 + Alpine.js |
| Admin panel | Filament 5 (+ filament-shield, filament-breezy, google-analytics) |
| CSS | Tailwind CSS 4 (tokens brand di `@theme`) |
| Build | Vite Plus (`vp build` / `vp dev`) |
| Autentikasi | Laravel Fortify, Passkeys (WebAuthn), Socialite (Google), TOTP 2FA |
| Authorization (admin) | spatie/laravel-permission + filament-shield |
| AI | `laravel/ai` dengan driver OpenRouter (`OPENROUTER_API_KEY`) |
| Data | SQLite (local), Eloquent + spatie/laravel-data (DTO) |
| Testing | Pest 5, Laravel Pint, Larastan (PHPStan level 7) |

---

## Arsitektur

```
skular/
├── app/
│   ├── Ai/Agents/         # Agent AI (laravel/ai): PracticeFeedback, ModuleMentor, ModuleBrainstorming
│   ├── Concerns/          # Trait bersama (mis. ProfilingRules)
│   ├── Contracts/         # Interface service (binding di AppServiceProvider)
│   ├── Data/              # DTO (spatie/laravel-data)
│   ├── Enums/             # Enum domain (EducationLevel, ModuleCategory, AdminRole, dst.)
│   ├── Filament/          # Resource/schemas + Widgets admin (panel /admin)
│   ├── Livewire/          # Komponen halaman publik
│   │   ├── Profiling/     # Wizard 8 langkah
│   │   ├── Learning/      # ModulePlayer, Library, MentorChat, Brainstorming
│   │   ├── Scholarships/  # Index (katalog), Show (detail), Recommendation, Tracker
│   │   ├── Cv/, Twibbon/, Settings/
│   ├── Policies/          # Policy (di-generate filament-shield)
│   ├── Services/          # Business logic: Progress, LearningPath, ScholarshipApplication,
│   │                       #   ModuleMentor, ModuleBrainstorming, dst.
│   └── Http/Controllers/  # Controller tipis (auth Google, proof download)
├── resources/
│   ├── css/app.css        # Tailwind @theme: token warna Skular
│   ├── views/
│   │   ├── layouts/       # app/sidebar, auth/simple, auth/card
│   │   ├── components/    # ui/, forms/, actions/, navigation/ (Blade components)
│   │   ├── partials/      # head, sidebar-nav, toast
│   │   └── livewire/      # view tiap komponen Livewire + partials
│   └── js/                # passkeys.js
├── routes/
│   ├── web.php            # Halaman publik Livewire
│   ├── settings.php       # Halaman settings
│   └── console.php
├── database/
│   ├── migrations/        # Skema SQLite
│   ├── factories/
│   └── seeders/           # Role, Cluster, Scholarship, Learning, Admin, Demo
├── tests/Feature/         # Pest feature tests
└── config/                # ai.php, services.php, filament, livewire, dst.
```

**Prinsip:**

1. **Dua surface** — aplikasi publik Livewire (`/dashboard`, `/profiling`, dst.) dan panel admin Filament (`/admin`).
2. **Business logic di Service** — Livewire component tetap tipis; akses lewat interface di `app/Contracts` (di-bind di `AppServiceProvider`), data DTO di `app/Data`.
3. **Authorization admin** — Filament Shield terhadap policy di `app/Policies`. Role: `super_admin` (bypass), `co_admin`, `staff_divisi`.
4. **State Livewire server-side** — `wire:model` (deferred/live sesuai kebutuhan); validasi backend selalu dijalankan di action.

---

## Fitur

### Mentee (aplikasi publik)
- **Profiling 8 langkah** — selection card interaktif (single-select otomatis melepas pilihan lama), input angka untuk usia/IPK, progress bar + stepper, draft tersimpan di session, validasi per langkah.
- **Scholarship matching** — pencocokan berbasis jenjang, usia, IPK, SKTM, dan preferensi lokasi, dengan alasan transparan per kandidat; mentee memilih **maksimal 5 beasiswa** lalu menandai satu sebagai target utama.
- **Semua Beasiswa (katalog)** `/scholarships` — jelajah seluruh beasiswa aktif: pencarian nama/institusi, filter jenjang/cakupan/lokasi, urut deadline terdekat, pagination; beasiswa yang sudah ditutup bisa dimunculkan. Draft & arsip tidak pernah tampil.
- **Detail beasiswa** `/scholarships/{scholarship}` — benefit utama, nilai yang dicari, persyaratan (IPK/usia/SKTM), jadwal (dibuka/ditutup/verifikasi), kuota, catatan, link resmi; CTA **Tambah ke pilihan** yang mengikuti batas 5 dan status pendaftaran, atau **Lanjutkan Persiapan** bila sudah dipilih.
- **Learning roadmap (personal)** — modul **Inti** + modul **Template** beasiswa yang dipilih (checkpoint bertahap), plus **Perpustakaan** `/library` sebagai pelatihan tambahan lintas beasiswa di luar urutan roadmap.
- **Module player** — Pembukaan → Pahami (materi, insight, inti materi, **kata pengantar**) → Quick Question (benar/salah) → **Praktik** (dynamic form, kata pengantar sebelum form) → **Feedback AI** (skor, strengths, improvements, next steps) → Checkpoint + feedback modul.
- **AI Mentor (chatbot modul)** — panel diskusi di halaman Praktik. Menjawab berdasarkan materi + AI knowledge modul, dengan quick prompt, riwayat percakapan yang dibatasi, indikator loading, dan tombol retry saat provider gagal. Tidak pernah mengisi/mengubah jawaban praktik atau menyelesaikan checkpoint.
- **AI Brainstorming** — pertanyaan pilihan ganda adaptif untuk memancing ide saat mengisi praktik, dengan progress pertanyaan dan ringkasan ide yang bisa disalin. Ringkasan tidak pernah menimpa jawaban yang sudah ditulis.
- **Beasiswa Saya (tracker/antrean)** — konfirmasi pendaftaran + unggah bukti screenshot, lapor hasil (diterima/belum), unduh bukti. Beasiswa diurutkan deadline terdekat; setelah hasil dilaporkan, target aktif otomatis pindah ke beasiswa berikutnya.
- **Dashboard** — welcome header, beasiswa yang sedang disiapkan + antrean deadline, **Next Best Action** (dihitung dari status database), roadmap belajar, ringkasan progress, dan akses cepat.
- **CV Saya** — data diri otomatis dari jawaban praktik, siap cetak/PDF.
- **Twibbon** — generate twibbon dari template admin dengan nama & beasiswa.
- **Settings** — profil, appearance (dark/light/system), keamanan (password, 2FA TOTP, passkeys).
- **Autentikasi** — email/password, Google OAuth, passkey, verifikasi email, reset password, rate limiting.

### Admin (panel `/admin`)
- CRUD: Users, Scholarships (+ **Benefit Utama** & **Nilai yang Dicari**), Scholarship Applications, Fases, Modules (+ **Tipe** Inti/Template/Perpustakaan, **Beasiswa** untuk Template, kata pengantar, dan status draf/terbit), Practice Forms, Quick Questions, Checkpoints, Module Feedback, Cluster, Twibbon Templates, User Practice Responses.
- **Curriculum builder**: satu halaman editor modul berisi form bertahap (informasi dasar → kata pengantar → materi Pahami → konteks AI), **checklist kelengkapan otomatis**, serta tabel *Quick Question* dan *Latihan praktik* yang dikelola langsung dari halaman modul.
- **Pratinjau mentee** `/admin/modules/{record}/preview` — urutan langkah dan isi konten seperti yang dilihat mentee, read-only (tidak memulai checkpoint atau mengirim jawaban).
- **Publikasi terkendali**: tombol *Terbitkan* / *Jadikan draf* per modul, bulk *Terbitkan yang siap*, dan backend menolak publikasi selama syarat wajib belum lengkap.
- Navigasi dikelompokkan mengikuti alur kerja kurikulum: Kurikulum, Materi & Latihan, Beasiswa, Pemantauan Mentee, Pendaftaran Beasiswa, Administrasi Sistem.
- Dashboard admin: panduan kerja kurikulum (modul terbit, modul draf, modul belum siap terbit, modul Template/Perpustakaan), tabel *Modul draf yang perlu diselesaikan* + aksi cepat (buat modul, lihat modul draf), tabel deadline beasiswa, dan statistik operasional mentee/beasiswa — semuanya dihitung langsung dari database, tanpa angka karangan. Widget operasional otomatis disembunyikan dari pengguna yang tidak punya izin membaca data beasiswa.
- Palet warna brand Skular (amber→orange + teal) diterapkan di panel; font Rubik; sidebar collapsible.
- Role & permission (Shield), Google Analytics dashboard, Breezy (2FA di admin).

---

## Aturan Bisnis (Beasiswa & Modul)

**Maksimal 5 beasiswa per mentee.** Ditampilkan sebagai "x dari 5 beasiswa dipilih" dan dicek di UI (halaman rekomendasi & detail), lalu ditegakkan di backend: `ScholarshipApplicationService::select()` membatasi jumlah di dalam transaksi dengan `lockForUpdate`, sehingga request paralel tidak dapat melewati batas. Duplikat dicegah oleh unique index `(user_id, scholarship_id)`.

**Urutan berdasarkan deadline.** Antrean beasiswa diurutkan `closing_date` terdekat lebih dulu; `closing_date` NULL ditaruh paling akhir dengan tie-break `id` agar deterministik. Beasiswa berstatus Draft/Closed/Archived atau yang jendelanya sudah lewat tidak bisa dipilih sebagai pilihan baru.

**Lifecycle beasiswa.** `selected` → `submitted` → `result_reported` (hasil `accepted`/`rejected`). Setiap beasiswa punya status aplikasi sendiri, dan histori (jawaban praktik, feedback AI, checkpoint, bukti submit, hasil, twibbon) tetap tersimpan. Setelah hasil dilaporkan, target aktif otomatis berpindah ke beasiswa berikutnya dengan deadline terdekat; pelaporan hasil yang sama dua kali bersifat **idempoten** dan tidak melompati antrean. Bila tidak ada beasiswa berikutnya, beasiswa terakhir tetap menjadi target agar hasil & twibbonnya tetap dapat diakses.

**Tipe modul (`category`).**
- `inti` — berlaku untuk semua beasiswa, tidak terikat scholarship.
- `template` — terikat satu `scholarship_id` (foreign key) dan hanya tampil pada jalur beasiswa yang dipilih mentee.
- `perpustakaan` — pelatihan tambahan lintas beasiswa, diakses lewat `/library`, di luar urutan roadmap.

Model `Module` otomatis mengosongkan `scholarship_id` bila kategori diubah menjadi `inti`/`perpustakaan`, dan form admin mewajibkan `scholarship_id` saat kategori `template`.

> **Catatan penting:** kolom `modules.type` yang lama **tetap** menyimpan format penyajian (`reading`, `video`, `practice`, `mixed`) dan tidak diubah. Klasifikasi Inti/Template/Perpustakaan disimpan di kolom baru `modules.category` supaya data modul yang sudah ada tidak rusak.

**Learning path personal.** `LearningPathService` menyusun jalur = modul Inti + modul Template milik beasiswa yang **dipilih** mentee; modul Perpustakaan ditampilkan terpisah. Mengganti beasiswa aktif tidak menghapus progress atau jawaban sebelumnya. Modul Template milik beasiswa yang tidak dipilih tidak bisa dibuka lewat URL — ditolak di layer service (`ProgressService`), bukan hanya disembunyikan di UI.

**Status konten modul (`content_status`).** Setiap modul berstatus `draft` atau `published`. Modul baru selalu `draft`, dan seluruh kolom modul yang sudah ada di-backfill menjadi `published` saat migrasi sehingga tidak ada konten yang hilang. Modul draf tidak pernah masuk jalur belajar mentee: `LearningPathService` menyaring status di query (roadmap, modul inti, template, dan perpustakaan), dan `ModulePlayer` menolak membuka modul draf lewat URL dengan 404 — bukan hanya disembunyikan di navigasi. Publikasi hanya boleh dilakukan lewat aksi Filament yang memverifikasi `ModuleReadinessService`; bila syarat wajib belum lengkap, aksi menolak dan menyebutkan bagian yang kurang.

**Peran AI.** AI hanya dipakai untuk konten: feedback praktik, AI Mentor, dan AI Brainstorming. Urutan beasiswa, batas lima, penentuan modul, dan transisi beasiswa dihitung secara deterministik dari database — bukan oleh AI.

---

## Alur Kerja Penyusun Kurikulum (Admin)

Panel `/admin` dipakai bersama oleh Super Admin, Co-Admin, dan Staff Divisi (penyusun kurikulum). Menu dikelompokkan mengikuti pekerjaan penyusun konten, bukan struktur tabel database:

| Grup | Isi |
|---|---|
| Kurikulum | Fase pembelajaran, Modul pembelajaran |
| Materi & Latihan | Quick Question, Latihan praktik |
| Beasiswa | Beasiswa (termasuk benefit utama, nilai yang dicari, jadwal), Cluster bidang |
| Pemantauan Mentee | Checkpoint mentee, Jawaban latihan, Feedback modul |
| Pendaftaran Beasiswa | Pendaftaran mentee |
| Administrasi Sistem | Pengguna, Twibbon, Google Analytics, Role & permission |

Setiap pengguna hanya melihat menu yang izinnya dimiliki (Shield). Widget dashboard pun mengikuti izin: statistik mentee/beasiswa dan tabel deadline hanya tampil bila pengguna boleh membaca data beasiswa, sehingga Staff Divisi melihat dashboard kurikulum saja.

### Langkah menyusun satu modul

1. **Buat modul** dari aksi cepat dashboard (*Buat modul baru*) atau *Modul pembelajaran → Buat*. Modul baru selalu disimpan sebagai **Draf**, jadi mentee belum bisa membukanya, dan admin langsung diarahkan ke halaman editor untuk melanjutkan.
2. **Informasi dasar** — judul, fase, urutan, tipe modul (Inti / Template / Perpustakaan), format penyajian, durasi, dan tujuan pembelajaran. Field **Beasiswa tujuan** hanya muncul saat tipe Template dipilih.
3. **Kata pengantar** — dipakai sebagai orientasi sebelum Quick Question dan sebelum latihan praktik.
4. **Materi Pahami** — ringkasan materi, poin penting (repeater: satu poin per baris, bisa digeser urutannya), inti materi, dan video (toggle + link embed).
5. **Quick Question** — tabel di bawah form editor: pernyataan Benar/Salah, kunci jawaban, penjelasan, urutan, dan status tampil.
6. **Latihan praktik** — tabel di bawah form editor: judul, arahan, urutan, dan builder pertanyaan (jenis jawaban, wajib/opsional, petunjuk pengisian, pilihan jawaban untuk tipe pilihan, serta opsi tampil di CV). Tidak ada JSON yang perlu ditulis manual.
7. **Pratinjau** — tombol *Pratinjau* membuka `/admin/modules/{record}/preview`: urutan langkah dan isi konten seperti yang dilihat mentee, plus peringatan bila modul belum siap terbit. Halaman ini read-only — tidak memulai checkpoint, tidak mengirim jawaban praktik, dan tidak mengubah progres siapa pun.
8. **Terbitkan** — tombol *Terbitkan* (per modul atau bulk *Terbitkan yang siap*) hanya memproses modul draf. Bila syarat wajib belum lengkap, backend menolak dan notifikasi menyebutkan bagian yang kurang. Modul terbit dapat dikembalikan ke draf (*Jadikan draf*) tanpa menghapus progres, jawaban, atau checkpoint mentee.

### Checklist kelengkapan modul

Dihitung dari data modul dan konten yang terhubung (bukan centang manual), ditampilkan pada halaman editor dan pada kolom tabel modul, serta dipakai backend saat publikasi:

| Syarat | Sifat |
|---|---|
| Informasi dasar terisi | Wajib |
| Materi Pahami tersedia (ringkasan, inti materi, atau poin penting) | Wajib |
| Quick Question lengkap | Wajib bila modul memakai quick question |
| Beasiswa tujuan terpilih | Wajib untuk modul Template |
| Link video valid | Wajib untuk modul berformat video |
| Tujuan pembelajaran dijelaskan | Disarankan |
| Kata pengantar tersedia | Disarankan |
| Latihan praktik punya minimal satu pertanyaan | Disarankan (latihan kosong dilaporkan sebagai masalah) |

---


## Struktur Halaman Frontend

Setiap halaman Livewire dirender di dalam layout `x-layouts::app.sidebar` (kecuali auth & landing):

```
<x-layouts::app.sidebar>          # resources/views/layouts/app/sidebar.blade.php
├── <aside> Desktop sidebar       # partials.sidebar-nav (nav + user-menu dropdown ke atas)
├── <aside> Mobile sidebar        # Alpine $store.sidebar.open + backdrop
├── <header> Mobile header        # tombol toggle + user-menu
└── <main>                        # slot halaman
    └── <div> root komponen Livewire (max-w, flex-col)
        ├── header (pill, heading, progress/stepper)
        └── <main>/sections (x-ui.card, forms, states)
```

| Route | Komponen Livewire | View | Struktur |
|---|---|---|---|
| `/` | (blade view) | `welcome.blade.php` | Header sticky → hero (brand-grid + blob) → Cara Kerja → AI Learning → Matching → Roadmap → CTA → footer |
| `/login`, `/register`, dst. | auth Livewire | `livewire/auth/*` | `layouts/auth/simple` — logo, card form, link bawah |
| `/profiling` | `Profiling\Wizard` | `livewire/profiling/wizard.blade.php` | header progress (nomor "03 dari 08", progress bar, stepper dot 1-8) → `x-ui.card` form per langkah → tombol Kembali/Lanjut/Selesai |
| `/profiling/recommendation` | `Scholarships\Recommendation` | `livewire/scholarships/recommendation.blade.php` | header → daftar kartu match (checkbox, tier pill, alasan) → sticky bar konfirmasi |
| `/dashboard` | `Dashboard` | `livewire/dashboard.blade.php` | welcome header (card-brand) → stat grid (4 stat) → beasiswa aktif + antrean deadline → Next Best Action → roadmap (timeline per fase) → akses cepat |
| `/modules/{module}` | `Learning\ModulePlayer` | `livewire/learning/module-player.blade.php` + `partials/` | header + nav pill section → partial aktif: `opening`, `pahami`, `quick`, `praktik`, `checkpoint`; bagian praktik menyematkan `learning.mentor-chat` dan `learning.brainstorming` |
| `/library` | `Learning\Library` | `livewire/learning/library.blade.php` | header → grid kartu modul Perpustakaan (di luar urutan roadmap) |
| `/scholarships` | `Scholarships\Index` | `livewire/scholarships/index.blade.php` | header + penanda "x dari 5 dipilih" → panel filter (cari/jenjang/cakupan/lokasi/urut/tampilkan ditutup) → grid kartu → pagination |
| `/scholarships/saya` | `Scholarships\Tracker` | `livewire/scholarships/tracker.blade.php` | header → daftar kartu aplikasi (timeline status, form konfirmasi/report) |
| `/scholarships/{scholarship}` | `Scholarships\Show` | `livewire/scholarships/show.blade.php` | header beasiswa → jadwal & sisa hari → CTA (sesuai batas 5/status/ownership) → Benefit Utama → Nilai yang Dicari → Persyaratan → Catatan |
| `/cv` | `Cv\Show` | `livewire/cv/show.blade.php` | header + kartu profil → section CV per latihan |
| `/scholarships/{id}/twibbon` | `Twibbon\Generator` | `livewire/twibbon/generator.blade.php` | grid 2 kolom: pilih template + preview/unduh |
| `/settings/*` | `Settings\Profile/Appearance/Security` | `livewire/settings/*` | form profil, pemilih tema, keamanan (2FA/passkey) |
| `/admin` | Filament panel | vendor views | tabel + form resource |

**Komponen Blade bersama** (`resources/views/components/`):

- `ui/` — `card`, `pill`, `progress`, `stepper`, `stat`, `empty-state`
- `forms/` — `input`, `select`, `selection-card` (kartu pilihan profiling), `dynamic-field` (schema-driven praktik), `otp`
- `actions/` — `button`, `link-button`, `link`
- `navigation/` — `user-menu` (dropdown sidebar), `user-menu-items`
- `display/` — `heading`, `text`, `subheading`, `avatar`
- `feedback/` — `callout`, `auth-session-status`
- `brand/` — `app-logo`, `app-logo-icon`

---

## Endpoint

> Catatan: Skular adalah aplikasi **server-rendered Livewire**, bukan API JSON terpisah. Endpoint di bawah adalah rute HTTP yang tersedia. Body request ditampilkan dalam format JSON untuk referensi — untuk form browser dikirim sebagai `application/x-www-form-urlencoded` / `multipart/form-data`, dan untuk endpoint Livewire sebagai payload RPC-nya.

### 1. Halaman Publik (Livewire)

Semua butuh `auth` + `verified` kecuali disebutkan lain.

| Method | Endpoint | Route name | Parameter | Body contoh |
|---|---|---|---|---|
| GET | `/` | `home` | — | — |
| GET | `/profiling` | `profiling` | `?step=1..8` (query, opsional) | — |
| GET | `/profiling/recommendation` | `profiling.recommendation` | — | — |
| GET | `/dashboard` | `dashboard` | — | — |
| GET | `/modules/{module}` | `modules.show` | `module` (path, id), `?section=opening\|pahami\|quick\|praktik\|checkpoint` | — |
| GET | `/library` | `library.index` | — | — |
| GET | `/scholarships` | `scholarships.index` | `?q=`, `?jenjang=`, `?cakupan=`, `?lokasi=`, `?ditutup=1`, `?urut=deadline\|name`, `?page=` | — |
| GET | `/scholarships/saya` | `scholarships.tracker` | — | — |
| GET | `/scholarships/{scholarship}` | `scholarships.show` | `scholarship` (path, id) | — |
| GET | `/cv` | `cv.show` | — | — |
| GET | `/scholarships/{application}/twibbon` | `twibbon.show` | `application` (path, id) | — |
| GET | `/scholarships/{application}/proof` | `applications.proof` | `application` (path, id) | — |
| GET | `/settings/profile` | `profile.edit` | — | — |
| GET | `/settings/security` | `security.edit` | — (butuh konfirmasi password) | — |
| GET | `/settings` | — (redirect) | — | — |

### 2. Autentikasi (Fortify)

| Method | Endpoint | Route name | Parameter | Body contoh |
|---|---|---|---|---|
| GET | `/login` | `login` | — | — |
| POST | `/login` | `login.store` | — | `{"email":"user@mail.com","password":"rahasia123","remember":"1"}` |
| GET | `/register` | `register` | — | — |
| POST | `/register` | `register.store` | — | `{"name":"Budi","email":"budi@mail.com","password":"rahasia123","password_confirmation":"rahasia123"}` |
| POST | `/logout` | `logout` | — | `_token=<csrf>` |
| GET | `/forgot-password` | `password.request` | — | — |
| POST | `/forgot-password` | `password.email` | — | `{"email":"budi@mail.com"}` |
| GET | `/reset-password/{token}` | `password.reset` | `token` (path), `email`, `id` (query) | — |
| POST | `/reset-password` | `password.update` | — | `{"token":"<token>","email":"budi@mail.com","password":"baruanih123","password_confirmation":"baruanih123"}` |
| GET | `/email/verify` | `verification.notice` | — | — |
| GET | `/email/verify/{id}/{hash}` | `verification.verify` | `id`, `hash` (path), `signature`, `expires` (query) | — |
| POST | `/email/verification-notification` | `verification.send` | — | `_token=<csrf>` |
| GET | `/two-factor-challenge` | `two-factor.login` | — | — |
| POST | `/two-factor-challenge` | `two-factor.login.store` | — | `{"code":"123456"}` atau `{"recovery_code":"XXXX-XXXX"}` |
| GET | `/user/confirm-password` | `password.confirm` | — | — |
| POST | `/user/confirm-password` | `password.confirm.store` | — | `{"password":"rahasia123"}` |
| GET | `/user/confirmed-password-status` | `password.confirmation` | — | — |
| POST | `/user/two-factor-authentication` | `two-factor.enable` | butuh middleware `password.confirm` | — |
| DELETE | `/user/two-factor-authentication` | `two-factor.disable` | butuh middleware `password.confirm` | — |
| GET | `/user/two-factor-qr-code` | `two-factor.qr-code` | butuh `password.confirm` | — |
| GET | `/user/two-factor-secret-key` | `two-factor.secret-key` | butuh `password.confirm` | — |
| GET | `/user/two-factor-recovery-codes` | `two-factor.recovery-codes` | butuh `password.confirm` | — |
| POST | `/user/two-factor-recovery-codes` | `two-factor.regenerate-recovery-codes` | butuh `password.confirm` | — |
| POST | `/user/confirmed-two-factor-authentication` | `two-factor.confirm` | — | `{"code":"123456"}` |

### 3. Passkeys (WebAuthn)

| Method | Endpoint | Route name | Parameter | Body contoh |
|---|---|---|---|---|
| GET | `/passkeys/login/options` | `passkey.login-options` | — | — |
| POST | `/passkeys/login` | `passkey.login` | — | `{"id":"<cred-id>","rawId":"<b64>","response":{"clientDataJSON":"...","authenticatorData":"...","signature":"...","userHandle":null},"type":"public-key"}` |
| GET | `/user/passkeys/options` | `passkey.registration-options` | butuh auth | — |
| POST | `/user/passkeys` | `passkey.store` | butuh auth + konfirmasi password | `{"name":"Laptop","response":{"clientDataJSON":"...","attestationObject":"..."},"type":"public-key"}` |
| DELETE | `/user/passkeys/{passkey}` | `passkey.destroy` | `passkey` (path, id) | — |
| GET | `/passkeys/confirm/options` | `passkey.confirm-options` | — | — |
| POST | `/passkeys/confirm` | `passkey.confirm` | — | `{"id":"...","response":{"clientDataJSON":"...","authenticatorData":"...","signature":"..."}}` |
| GET | `/.well-known/passkey-endpoints` | `well-known.passkeys` | — | — |

### 4. OAuth Google

| Method | Endpoint | Route name | Parameter | Body contoh |
|---|---|---|---|---|
| GET | `/auth/google/redirect` | `auth.google.redirect` | — (throttle 30/menit) | — |
| GET | `/auth/google/callback` | `auth.google.callback` | `code`, `state` (query) | — |

### 5. Livewire RPC

Prefix endpoint: `/livewire-{hash}` — `hash` = 8 karakter pertama `sha256(APP_KEY + "livewire-endpoint")`. Di instance lokal ini prefix-nya `livewire-41e11991`; nilai berbeda bila `APP_KEY` berbeda.

| Method | Endpoint | Route name | Parameter | Body contoh |
|---|---|---|---|---|
| POST | `/livewire-41e11991/update` | `default-livewire.update` | — | `{"_token":"...","components":[{"snapshot":"<base64>","data":{...},"updates":{},"calls":[{"path":"","method":"next","params":[]}]}]}` |
| POST | `/livewire-41e11991/upload-file` | `livewire.upload-file` | — | `multipart/form-data` dengan `files[]` (Livewire TemporaryUploadedFile) |
| GET | `/livewire-41e11991/preview-file/{filename}` | `livewire.preview-file` | `filename` | — |
| PUT | `/storage/{path}` | `storage.local.upload` | `path` | `multipart/form-data` |

Contoh pemanggilan aksi Livewire (via component test / RPC): memanggil `Wizard::next()` di `POST /profiling` dengan `calls: [{"method":"next","params":[]}]` — validasi berjalan server-side; bila gagal, `errors` dikembalikan dan state dipertahankan.

### 6. Admin Filament (CRUD)

Prefix `/admin`, login via `/admin/login`, authorization via Shield.

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/admin` | Dashboard admin |
| GET/POST | `/admin/login`, `/admin/logout` | Auth panel |
| GET | `/admin/users`, `/admin/users/create`, `/admin/users/{record}/edit` | CRUD User |
| GET | `/admin/scholarships` (+ `/create`, `/{record}/edit`) | CRUD Beasiswa (termasuk **Benefit Utama** & **Nilai yang Dicari** sebagai repeater) |
| GET | `/admin/scholarship-applications` (+ `/create`, `/{record}/edit`) | Pendaftaran beasiswa |
| GET | `/admin/fases` (+ `/create`, `/{record}/edit`) | Fase roadmap |
| GET | `/admin/modules` (+ `/create`, `/{record}/edit`) | Modul (termasuk **Tipe** Inti/Template/Perpustakaan, **Beasiswa** untuk tipe Template, field **Kata Pengantar**, checklist kelengkapan, serta tabel Quick Question & Latihan praktik) |
| GET | `/admin/modules/{record}/preview` | Pratinjau mentee untuk modul (read-only) |
| GET | `/admin/practice-forms` (+ `/create`, `/{record}/edit`) | Form praktik dinamis |
| GET | `/admin/quick-questions` (+ `/create`, `/{record}/edit`) | Quick Question |
| GET | `/admin/checkpoints` (+ `/create`, `/{record}/edit`) | Checkpoint |
| GET | `/admin/module-feedback` (+ `/create`, `/{record}/edit`) | Feedback modul |
| GET | `/admin/clusters` (+ `/create`, `/{record}/edit`) | Bidang studi (cluster) |
| GET | `/admin/twibbon-templates` (+ `/create`, `/{record}/edit`) | Template twibbon |
| GET | `/admin/user-practice-responses` | Jawaban praktik mentee |
| GET | `/admin/shield/roles` (+ `/create`, `/{record}`, `/{record}/edit`) | Role & permission |
| GET | `/admin/two-factor-authentication` | 2FA admin |
| GET | `/admin/google-analytics-dashboard` | Analitik |

---

## Cara Menjalankan Project

**Prasyarat:** PHP >= 8.3 (diuji dengan 8.5) dengan ekstensi `gd` dan `pdo`, Composer, Node.js 22.18+ atau 24.11+ (dibatasi oleh Vite Plus), SQLite untuk lokal.

### Setup otomatis (yang dijalankan CI)

```bash
composer setup
```

Ini menjalankan: `composer install` → salin `.env` → `php artisan key:generate` → membuat file SQLite bila belum ada → `php artisan migrate --force` → `npm ci` → `npm run build`.

### Setup manual langkah demi langkah

```bash
# 1. Dependency PHP
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database (SQLite)
touch database/database.sqlite     # aman dibuat dulu; migrate --no-interaction tidak membuat file sendiri
php artisan migrate
php artisan db:seed          # role, cluster, beasiswa, modul, akun admin, demo mentee

# 4. Dependency JS + build aset
npm ci
npm run build                # production build (vp build)
```

### Menjalankan server development

```bash
composer dev
```

Menjalankan 3 proses paralel: `php artisan serve` (http://localhost:8000), `php artisan queue:listen`, dan `npm run dev` (Vite HMR).

Atur alternatif masing-masing:

```bash
php artisan serve
npm run dev
php artisan queue:listen
```

### Variabel environment penting

| Variabel | Keterangan |
|---|---|
| `OPENROUTER_API_KEY` | Kunci API AI — dipakai untuk feedback praktik, **AI Mentor**, dan **AI Brainstorming** (driver `openrouter` di `config/ai.php`). Ada di `.env` lokal dan `OPENROUTER_API_KEY` kosong di `.env.example` |
| `OPENROUTER_TEXT_MODEL` | Model teks OpenRouter untuk semua fitur AI. Diisi di `.env`; bila kosong, `config/ai.php` memakai `dots-studio/dots-3-note-preview:free` |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Login Google (Socialite) |
| `SESSION_DRIVER=database` | Sesi di tabel DB (sudah ada di `.env.example`) |
| `DB_CONNECTION=sqlite` | Database lokal |

---

## Deployment Production

Panduan lengkap hosting, environment production, Nginx, migrasi aman, backup,
restore, rollback, health check, dan load testing ada di
[`docs/deployment.md`](docs/deployment.md).

Ringkasan gerbang sebelum rilis:

```bash
composer ci:check        # Pint + PHPStan + seluruh test
npm ci && npm run build  # build aset production
composer audit           # kerentanan dependency PHP
npm audit --omit=dev     # kerentanan dependency JS
```

## Perintah Lainnya

```bash
composer dev              # serve + queue + vite (paralel)
composer lint             # Laravel Pint --dirty (perbaiki format)
composer lint:check       # Pint mode test (tanpa perubahan)
composer types:check      # PHPStan / Larastan level 7
composer test             # config:clear + lint:check + types:check + artisan test
composer ci:check         # gerbang penuh sama dengan CI
npm run build             # build produksi (vp build)
npm run dev               # dev server Vite (vp dev)
```

Satu test:

```bash
vendor/bin/pest tests/Feature/Profiling/ProfilingWizardTest.php
vendor/bin/pest --filter "the wizard starts on the first question"
```

Setelah menambah/mengubah resource Filament:

```bash
php artisan shield:generate --all --panel=admin
php artisan db:seed --class=RoleSeeder
```

---

## Testing & Quality Gates

- **Pest 5** — `tests/Feature` (RefreshDatabase, SQLite in-memory via `phpunit.xml`).
- **Pint** — format kode (`pint.json`).
- **PHPStan/Larastan level 7** — `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/`.
- **Build** — `npm run build` harus sukses.

Status saat dokumentasi ditulis:

| Gate | Hasil |
|---|---|
| Pest | **373 passed** (2823 assertions), 0 failed |
| Pint | PASS (299 file) |
| PHPStan | No errors |
| `npm run build` | Sukses |

---

## Akun Admin

Akun admin dibuat oleh `database/seeders/AdminUserSeeder.php` saat `php artisan db:seed`. Password di bawah adalah **untuk development lokal saja** — ganti sebelum deploy.

| Role | Email | Password | Nama |
|---|---|---|---|
| **Super Admin** | `admin@skular.test` | `password` | Super Admin Skular |
| Co-Admin | `coadmin@skular.test` | `password` | Co Admin Skular |
| Staff Divisi | `staff@skular.test` | `password` | Staff Divisi Skular |

- **Login admin:** `http://localhost:8000/admin/login` (panel Filament).
- **Super Admin** melewati seluruh cek izin via Shield gate; `co_admin` dan `staff_divisi` mengikuti matriks permission di `RoleSeeder`.
- Mentee biasa (tanpa role admin) ditolak akses panel oleh `User::canAccessPanel()`.

Untuk membuat ulang akun admin:

```bash
php artisan db:seed --class=AdminUserSeeder
```

---

## Akun Demo Mentee

`php artisan db:seed` juga menjalankan `DemoMenteeSeeder` yang membuat 3 akun mentee demo (password semua: `password`) untuk menguji tiap tahap perjalanan tanpa mendaftar manual:

| Email | Kondisi |
|---|---|
| `mentee.baru@skular.test` | Belum pernah menyelesaikan profiling |
| `mentee.progress@skular.test` | Profil lengkap, 3 modul selesai (roadmap berjalan) |
| `mentee.lulus@skular.test` | 6 modul selesai + beasiswa diterima (demo twibbon) |
