# Progres: Data diri CV sebagai referensi di modul

Catatan kerja untuk fitur "Data dirimu" di langkah Praktik: mentee membuka jawaban CV yang sudah ia isi di modul lain sebagai bahan referensi (Opsi A: tampilkan + salin). Diperbarui setiap tahap selesai.

## Status tahap

| Tahap | Isi | Status |
|---|---|---|
| 1 | Rapikan `CvService` + scope `PracticeForm::cvSources()` (file, form nonaktif, modul draf, field terhapus) | Selesai |
| 2 | Migration `module_cv_sources` + `practice_forms.cv_button_label`, relasi model | Selesai |
| 3 | Filament: pilih sumber di resource Modul, label tombol, bump cache, readiness | Selesai |
| 4 | `CvService::sectionsForModule()`, `ModulePlayer`, panel di `practice.blade.php`, Product Tour, seeder | Selesai |
| 5 | Tes, dokumentasi, Pint/PHPStan/Pest | Selesai |

## Keputusan teknis

### Tahap 1

- `PracticeForm::cvSources()` sekarang: `include_in_cv = true` **dan** `is_active = true` **dan** modul `published` (memakai scope `Module::published()` lewat `whereHas`). Kolom ditulis berkualifikasi (`practice_forms.*`) karena scope juga dipakai pada query yang di-join pivot.
- `CvService::entriesFor()` berjalan mengikuti skema `fields` (bukan mengikuti isi `answers`): urutan entri = urutan pertanyaan admin, jawaban field yang sudah dihapus/diganti kodenya tidak tampil, field bertipe `file` dilewati (path penyimpanan tidak pernah tampil).
- Label diambil dari `label` mentah di skema. `PracticeFieldData::fromSchema()` jatuh ke `name` bila label kosong; supaya kode internal tidak pernah tampil, `CvService` memakai label cadangan "Jawaban".
- Logika penyusun dipusatkan di `buildSections()` (satu query jawaban, selalu difilter `user_id`), dipakai `sections()` (hanya bagian terisi) dan `sectionsForModule()` (semua sumber, termasuk yang belum diisi).
- Factory `Module` default sudah `published` dan `PracticeForm` default sudah `is_active = true`, jadi tes lama tidak perlu diubah.

### Tahap 2

- Migration additive: `2026_11_25_060224_create_module_cv_sources_table` (FK cascade ke `modules` dan `practice_forms`, unique bernama eksplisit `module_cv_sources_module_form_unique` karena batas 64 karakter MySQL) dan `2026_11_25_060226_add_cv_button_label_to_practice_forms_table` (string 60, nullable).
- `Module::cvSources()` (BelongsToMany, urut `practice_forms.cv_ordering` lalu `practice_forms.id`) dan `PracticeForm::cvSourceFor()` (inverse). `cv_button_label` masuk `#[Fillable]` dan `@property`.
- `CvSectionData` mendapat `?string $button_label = null` dan `bool $filled = true` di akhir constructor (argumen lama tetap berlaku) + helper `buttonText()`.
- **Interaksi dengan aturan delete:** `Module::deleting` menolak hapus bila ada histori mentee; bila hapus diizinkan, baris pivot ikut terhapus lewat FK cascade. `PracticeForm` tidak punya guard delete (jawaban mentee ikut cascade, perilaku lama, tidak diubah di sini); pivotnya juga cascade. Menghapus pivot tidak menghapus jawaban mentee apa pun.

### Tahap 3

- `ModuleForm`: section baru **"6. Data diri dari CV mentee"** setelah "5. Saran pertanyaan AI Mentor". `Select::make('cvSources')->relationship(...)->multiple()->searchable()->preload()`; opsi hanya `include_in_cv = true` dan `module_id` berbeda dari record (pada Create semua modul boleh, karena record belum ada). Label opsi `Judul form (Judul modul asal)`. Helper text menyorot kategori Template (berubah sesuai kategori yang dipilih). Section disembunyikan bila `is_tryout_theory` aktif.
- Validasi server: `ModuleForm::invalidCvSourceMessage()` dipasang sebagai rule closure pada Select; menolak id yang tidak ada, form tanpa `include_in_cv`, dan form dari modul yang sedang diedit.
- Catatan Filament 5: `modifyQueryUsing` relasi juga dipakai saat sync (detach hanya menyentuh baris yang lolos filter), jadi kolom ditulis `practice_forms.*`. Akibatnya bila form sumber kemudian dimatikan `include_in_cv`-nya, baris pivot lama tidak terhapus otomatis saat modul disimpan; itu aman karena `sectionsForModule()` tetap menyaring lewat `cvSources()` (tidak tampil ke mentee) dan readiness memberi peringatan.
- `PracticeFormForm::cvSettings()`: `cv_button_label` (maks 60, hanya tampil bila `include_in_cv` aktif). Ikut di relation manager modul dan resource Latihan Praktik karena memakai schema yang sama. `PracticeFormsTable`: kolom "Dipakai di modul lain" (jumlah, tersembunyi secara default).
- `CreateModule::afterCreate()` dan `EditModule::afterSave()` memanggil `ContentCache::bump()` (Filament menyimpan relasi sebelum hook ini).
- `ModuleReadinessService` sudah punya pola peringatan (`required: false`), jadi ditambah cek non-blokir `cv_sources` (hanya muncul bila modul punya sumber): gagal bila ada sumber yang nonaktif, tidak lagi `include_in_cv`, atau modul asalnya draf. Tidak masuk `blockers()`. Eager load `cvSources.module:id,content_status` ditambahkan di `ModulesTable`, `ContentReadiness`, `CurriculumOverview` agar tidak N+1.
- Tes: `tests/Feature/Admin/ModuleCvSourcesAdminTest.php` (tautkan + cache ter-bump, tolak non-CV, tolak modul sendiri, tersembunyi untuk teori Try Out, `cv_button_label` tersimpan, readiness non-blokir).

### Tahap 4

- `CvServiceInterface::sectionsForModule()`: `PracticeForm::cvSources()` + `whereHas('cvSourceFor')` untuk modul itu + `module_id != modul itu`, eager load `module:id,title`, lalu satu query jawaban untuk user yang login. Total 2 query, tanpa N+1. Semua sumber dikembalikan, yang belum diisi `filled = false`.
- `ModulePlayer`: `#[Locked] public ?int $open_cv_source` (browser tidak bisa mengisinya langsung), `toggleCvSource(int)` memeriksa id terhadap `cvSources` (id asing = panel tertutup, tidak ada data terbuka), computed `cvSources` hanya memanggil service saat `section === 'praktik'`. Tidak ada kode yang menyentuh `$answers`.
- View: partial baru `partials/cv-reference-panel.blade.php`, di-include di `practice.blade.php` setelah kata pengantar dan sebelum daftar latihan, hanya bila `$cv_sources` tidak kosong. Kartu hanya dirender untuk sumber yang terbuka (data sumber lain tidak ada di HTML). Tombol `aria-expanded`/`aria-controls`, kartu `role="region"`. Salin: Alpine `navigator.clipboard` di konteks aman, fallback `textarea` + `execCommand`, bila gagal tampil "Gagal menyalin otomatis. Blok teksnya lalu salin manual ya." Tautan ke modul asal memakai `?section=praktik`. Ikon heroicons yang dipakai sudah dicek ada: `identification`, `chevron-up/down`, `book-open`, `document-duplicate`, `pencil-square`, `arrow-right`. Kelas hanya light (tanpa `dark:`).
- Product Tour `Module`: langkah "Data dirimu" dengan target `[data-tour="cv-reference-panel"]` (di modul tanpa panel jatuh ke kartu tengah, sesuai pola). Tidak ada tes yang menghitung jumlah langkah; ditambahkan tes bahwa targetnya ada.
- Seeder: `LearningSeeder` punya modul Template (`TEMPLATE_MODULES`). Modul "Esai Khusus :beasiswa" (per beasiswa) ditautkan ke "Pengalaman Organisasi" lewat `seedCvSources()` (`syncWithoutDetaching`, tidak mencabut tautan buatan admin), dan form itu diberi `cv_button_label = 'pengalaman organisasiku'` sebagai demo.

### Tahap 5

- Tes baru: `tests/Feature/Learning/CvReferencePanelTest.php` (11 tes: panel muncul/tidak, entri berlabel tanpa file/kode, satu sumber terbuka, belum diisi + tautan, dua mentee, id asing + properti terkunci, form nonaktif/modul draf, tidak mengubah `$answers`/`UserPracticeResponse`/gate, fallback label tombol, tanpa skor/label, langkah tur). Tes CV halaman: 3 tes baru di `CvPageTest.php`. Tes admin: `ModuleCvSourcesAdminTest.php` (6 tes). AI tidak tersentuh.
- Dokumentasi: `docs/database.md` (ERD, catatan `modules`/`practice_forms`/`module_cv_sources`, tabel kolom), `docs/architecture.md` (alur modul), `README.md` (tabel fitur), `AGENTS.md` (aturan produk 14 + baris area sensitif). `docs/routes.md` tidak diubah (tidak ada route baru).
- Gerbang: Pint lulus, PHPStan level 7 0 error, Pest 970 lulus (5.352 assertion). `composer test` sendiri berhenti di Pest karena batas `process-timeout` 300 detik milik Composer (suite ±294 detik); Pest dijalankan langsung dengan `php artisan test --compact`. `npm run build` dijalankan; tidak ada perubahan di `public/build`.

## Perbedaan dari prompt

- Pada Create Modul, opsi sumber tidak bisa mengecualikan "modul yang sama" karena record belum ada; semua form `include_in_cv` boleh dipilih (form milik modul baru belum mungkin ada).
- Bila label pertanyaan kosong, CV memakai label "Jawaban" (bukan kode internal).
- Selain dari Select, `cv_source_for_count` ditambahkan sebagai kolom tersembunyi-default di `PracticeFormsTable`.
- Sumber yang `include_in_cv`-nya dimatikan setelah ditautkan tidak dicabut otomatis dari pivot (perilaku sync Filament); disaring saat dibaca dan diberi peringatan readiness.

## Risiko

- **Tautan "Perbaiki di modul asal" bisa 403** bila modul asal masih terkunci untuk mentee (mis. modul asal lebih belakang di roadmap atau Template beasiswa lain). Readiness tidak mengecek urutan; admin sebaiknya memilih sumber dari modul yang lebih awal.
- **Perlu keputusan Farhan:** apakah Kebijakan Privasi perlu menyebut bahwa jawaban latihan bertanda CV ditampilkan kembali (hanya ke mentee itu sendiri) di modul lain. Teks legal tidak diubah.
- `PracticeForm` tidak punya guard delete (jawaban mentee ikut cascade); perilaku lama, di luar cakupan.
- Suite Pest mendekati batas 300 detik `composer test`; CI (`composer ci:check`) berisiko timeout walau tidak ada tes gagal.
- Panel belum diuji manual di browser (hanya lewat tes Livewire).
