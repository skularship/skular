# Dev Brief: Alur Sederhana, Feedback Gate, Event, dan Tracking Perilaku

Untuk: Rafi · Dari: Farhan · 18 Nov 2026
Target pekan ini: **Tracking Customer Behavior & Feedback**.
Konteks keputusan produk: `docs/brief-feedback-gate.md`.

Urutan kerja (tiap bagian = 1 PR, boleh dirilis terpisah):

| # | Bagian | Estimasi | Prioritas |
|---|---|---|---|
| 1 | Tracking perilaku (fondasi data) | 1 hari | Wajib pekan ini |
| 2 | Alur baru: daftar → Info Beasiswa → "Cari yang sesuai untukku" | 0,5-1 hari | Wajib pekan ini |
| 3 | Feedback gate wajib per modul + komitmen | 1,5 hari | Wajib pekan ini |
| 4 | Fitur Event (webinar, kelas, mentoring) | 2-3 hari | Mulai pekan ini |
| 5 | Gate milestone fase + halaman Insight di admin | 1,5 hari | Pekan depan |

Aturan umum: ikuti `AGENTS.md` (migration additive, logika di Service + interface, copy "kamu", test Pest, `composer test` lulus).

---

## Prinsip UI/UX: ramah untuk semua umur

Pengguna bukan hanya anak muda, tapi juga orang tua. Berlaku untuk semua layar baru:

- **Satu tujuan per layar**, satu tombol utama yang besar dan jelas.
- Ukuran teks dasar minimal 16 px, tombol minimal 44 px tinggi, kontras tinggi.
- Bahasa sehari-hari tanpa istilah teknis ("Cari beasiswa yang cocok untukku", bukan "Mulai profiling").
- Tidak ada yang wajib di awal kecuali yang benar-benar perlu; semua bisa dilewati lalu dikembalikan.
- Pilihan berbentuk tombol/chip, bukan dropdown kecil atau ketik bebas, bila memungkinkan.

---

## 1. Tracking perilaku (fondasi)

Tujuan: tahu di mana mentee berhenti dan apa yang mereka klik, tanpa tool pihak ketiga.

**Migration:** `create_user_events_table` (nama bebas, jangan bentrok dengan fitur Event; saran: `activity_logs`).

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | |
| user_id | foreignId nullable | null untuk pengunjung belum login |
| session_key | string(64) nullable | dari `session()->getId()`, untuk pengunjung anonim |
| name | string(64), index | lihat daftar event |
| properties | json nullable | mis. `{module_id, scholarship_id, filter}` |
| created_at | timestamp, index | |

**Service:** `ActivityTrackerInterface::track(string $name, array $properties = [], ?User $user = null): void`. Tulis sinkron (satu insert), bungkus try/catch agar tracking tidak pernah merusak alur utama.

**Event minimum yang dicatat** (nama konstan di enum `ActivityName`):

| Event | Dipanggil di |
|---|---|
| `registered` | `RegisterResponse` |
| `scholarships_viewed` | `Scholarships\Index::mount` |
| `scholarships_filtered` | `Scholarships\Index::updated` (filter apa) |
| `scholarship_opened` | `Scholarships\Show::mount` |
| `match_cta_clicked` | tombol "Cari yang sesuai untukku" |
| `profiling_started` / `profiling_completed` | `Profiling\Wizard` |
| `scholarships_selected` | `Recommendation` setelah `select()` |
| `module_opened` / `module_completed` | `ModulePlayer` |
| `practice_submitted` | `ModulePlayer::submitPractice` |
| `feedback_submitted` | `saveModuleFeedback` |
| `event_registered` / `event_attended` | fitur Event |
| `premium_viewed` / `checkout_started` | `Premium\Index`, `CheckoutService` |

**Admin:** widget Filament "Funnel Mentee" (rentang 7/30 hari): Daftar → Lihat beasiswa → Klik cari yang sesuai → Profiling selesai → Pilih beasiswa → Modul 1 selesai → Feedback → Daftar event. Tampilkan jumlah dan % lanjut antar-langkah.

**Test:** tracker gagal (DB error) tidak melempar exception; tiap event utama tercatat sekali.

---

## 2. Alur baru: Info Beasiswa dulu, profiling belakangan

Sekarang: daftar → langsung profiling 8 langkah. Mulai sekarang: daftar → **Info Beasiswa** (`/scholarships`) yang sudah punya filter → tombol jelas **"Cari beasiswa yang cocok untukku"** → profiling.

Perubahan:

1. `app/Http/Responses/RegisterResponse.php`: redirect ke `route('scholarships.index')`, bukan `profiling`.
2. `app/Http/Responses/LoginResponse.php`: mentee tanpa profil lengkap → `scholarships.index` (bukan profiling).
3. `Scholarships\Index` view: banner/kartu di atas daftar untuk mentee yang belum profiling:
   - Judul: "Bingung pilih yang mana?"
   - Teks: "Jawab beberapa pertanyaan singkat, nanti Skular carikan beasiswa yang paling cocok untukmu."
   - Tombol besar: "Cari beasiswa yang cocok untukku" → `route('profiling')` + track `match_cta_clicked`.
   - Untuk mentee yang sudah profiling: tombol "Lihat rekomendasiku".
4. Kartu dashboard "Lengkapi profiling dulu" (`Dashboard.php` ±baris 182): ganti copy jadi "Cari beasiswa yang cocok untukmu", tombol "Mulai".
5. Cek middleware/guard lain yang memaksa ke profiling (grep `route('profiling')`); halaman beasiswa harus bisa dipakai tanpa profiling.
6. Product Tour: tambah langkah di `ProductTourService::steps()` untuk banner baru (`data-tour="match-cta"`).
7. Filter di Info Beasiswa: perbesar target sentuh dan tambahkan tombol "Hapus filter" yang terlihat.

Opsional (diskusikan dulu): buka `/scholarships` tanpa login agar bisa dilihat sebelum daftar; tombol "cocokkan" baru meminta daftar.

**Test:** update test RegisterResponse/LoginResponse; banner tampil untuk mentee tanpa profil, tidak tampil (diganti "Lihat rekomendasiku") untuk yang sudah.

---

## 3. Feedback gate wajib per modul

### 3a. Komitmen "akses eksklusif"

- Migration: `users.feedback_commitment_at` (timestamp nullable).
- Tampilkan sekali, sebagai langkah terakhir profiling **atau** saat pertama membuka modul (mana yang lebih dulu). Copy:
  > Kamu masuk program akses gratis Skular. Gratisnya eksklusif: sebagai gantinya, kamu memberi masukan jujur di akhir setiap modul. Masukanmu langsung kami pakai untuk memperbaiki Skular.
  >
  > [Saya setuju]
- Modul tidak bisa dibuka sebelum setuju (cek di server, `ModulePlayer::mount`).

### 3b. Form feedback baru di Checkpoint

Migration additive di `module_feedbacks` (data lama `ratings`, `suggestion` tetap):

| Kolom | Tipe |
|---|---|
| helpful_score | unsignedTinyInteger nullable (1-5) |
| difficulty | string(32) nullable (enum `FeedbackDifficulty`) |
| open_question | string nullable (teks pertanyaan yang ditampilkan) |
| open_answer | text nullable |
| low_score_reason | text nullable |

Isi form (gantikan centang positif `RATING_OPTIONS` untuk feedback baru):

1. "Seberapa membantu modul ini untuk persiapan beasiswamu?" — 5 tombol besar 1-5 dengan label ujung "Tidak membantu" / "Sangat membantu".
2. "Bagian mana yang paling menyulitkan?" — chip: Materi, Quick Question, Praktik, Penilaian AI, AI Mentor, Alur website, Tidak ada.
3. Pertanyaan terbuka bergilir (min 20 karakter):
   - "Di bagian mana kamu sempat bingung atau hampir berhenti?"
   - "Apa satu hal yang paling berguna dari modul ini buat aplikasimu?"
   - "Kalau bisa ubah satu hal di modul ini, apa?"
   Pilih berdasarkan `module.sort_order % 3` (atau kolom admin `modules.feedback_question`, nullable, menang bila diisi).
4. Bila skor 1-3: "Apa yang bikin kurang membantu?" (wajib, min 20 karakter).

Validasi server (`ModuleFeedbackService`): tolak teks yang hanya pengulangan karakter/tanda baca.

**Gate:** `confirmCheckpoint()` dan `ProgressService::completeModule()` menolak bila belum ada feedback dengan `helpful_score` untuk modul itu. Pesan: "Isi masukan singkat dulu ya, setelah itu modul berikutnya terbuka." Gabungkan simpan feedback + tandai selesai dalam satu tombol: **"Kirim masukan & lanjut"**.

Mentee lama: modul yang sudah selesai tidak diminta mengisi mundur.

**Test:** tidak bisa selesai tanpa feedback; skor rendah wajib alasan; teks sampah ditolak; modul lama yang sudah selesai tidak terpengaruh. Jaga test gate checkpoint yang sudah ada tetap hijau.

---

## 4. Fitur Event (pengganti Google Form untuk webinar)

Tujuan: Webinar dan Mentoring Personal tetap jadi kekuatan Skular, tapi pendaftaran, pengingat, dan absensi berjalan sendiri di website.

### Data

`events`

| Kolom | Tipe | Catatan |
|---|---|---|
| id, slug | | slug unik untuk URL |
| title | string | |
| type | string | enum `EventType`: Webinar, KelasIntensif, MentoringPersonal |
| description | text | Markdown, render via `<x-learning.rich-content>` |
| image_path | string nullable | poster |
| starts_at, ends_at | datetime | simpan UTC, tampil WIB |
| location_url | string nullable | link Zoom/Meet, **hanya tampil untuk yang terdaftar**, mulai H-1 |
| capacity | unsignedInteger nullable | null = tanpa batas |
| audience | string | enum: Semua, Pro, Undangan (khusus daftar email/tag) |
| registration_questions | json nullable | maks 3 pertanyaan singkat dari admin (pengganti GForm) |
| status | string | Draft, Published, Cancelled |
| recording_url | string nullable | diisi setelah acara |

`event_registrations`

| Kolom | Tipe | Catatan |
|---|---|---|
| event_id, user_id | foreignId | unique bersama |
| answers | json nullable | jawaban `registration_questions` |
| status | string | Registered, Cancelled, Attended, NoShow |
| reminded_at | json nullable | catat H-1 / H-1 jam terkirim |
| feedback_score | unsignedTinyInteger nullable | 1-5 setelah acara |
| feedback_text | text nullable | |
| timestamps | | |

Untuk audience "Undangan": tabel `event_invitations` (event_id, email) atau tag di user (`users.tags` json, mis. `alumni_akademi_unggulan`). Saran: tag di user, karena juga dipakai untuk memisahkan data feedback.

### Halaman mentee

- `/event` (`Events\Index`): daftar acara mendatang (kartu besar: poster, judul, tanggal & jam WIB, tombol "Daftar"). Tab sederhana: "Akan datang" / "Rekaman".
- `/event/{slug}` (`Events\Show`): detail + tombol **"Daftar sekarang"** → isi pertanyaan singkat (bila ada) → selesai. Setelah daftar: "Kamu terdaftar. Link acara muncul di sini H-1." + tombol "Tambah ke kalender" (file .ics).
- Dashboard: kartu "Acara berikutnya untukmu" (1 acara terdekat).
- Menu sidebar: "Event".
- Kapasitas penuh → tombol jadi "Penuh" (cek dengan `lockForUpdate` seperti pola `ScholarshipApplicationService`).
- Setelah acara: kartu meminta skor 1-5 + 1 kalimat masukan (mengisi `feedback_score`, `feedback_text`).

### Pengingat

- Command `events:remind` dijadwalkan tiap 15 menit di `routes/console.php` (pola seperti `reminders:dispatch`): kirim email H-1 hari dan H-1 jam, antrean `reminders`, idempoten lewat `reminded_at`.
- Mail `EventReminderMail` (link acara + tombol "Tambah ke kalender").

### Admin (Filament)

- Resource `Events`: form (semua kolom di atas), tabel (tanggal, tipe, terdaftar/kapasitas, status).
- RelationManager `Registrations`: daftar peserta + jawaban, aksi massal **"Tandai hadir"**, ekspor CSV.
- Aksi "Duplikat event" untuk acara rutin.
- Jalankan `shield:generate` lalu `RoleSeeder`.

### Tracking

`event_registered`, `event_attended`, dan skor feedback acara masuk ke funnel (bagian 1).

**Test:** daftar dua kali tidak dobel; kapasitas penuh ditolak; audience Pro/Undangan ditegakkan di server; link acara tidak tampil untuk yang tidak terdaftar atau sebelum H-1; reminder tidak terkirim dua kali.

---

## 5. Gate milestone fase + Insight Feedback (pekan depan)

- Tabel `phase_feedbacks` (user_id, fase_id unique bersama; `disappointment` enum Sangat/Agak/Biasa; `previous_solution` json; `previous_cost_range` string; `valued_features` json maks 2; `fair_price_range` string; `wants_event` bool; `preferred_time` string).
- Muncul setelah modul terakhir sebuah fase selesai; wajib sebelum modul fase berikutnya terbuka.
- `wants_event = true` → otomatis masuk audience undangan webinar berikutnya.
- Halaman admin **Insight Feedback**: skor rata-rata per modul, sebaran kesulitan, funnel (bagian 1), % "Sangat kecewa" (target ≥ 40% dari minimal 40 respons), fitur layak dibayar, sebaran harga, feed jawaban terbuka, filter tag `alumni_akademi_unggulan`, ekspor CSV.
- Rentang harga final menunggu konfirmasi Farhan.

---

## Definition of done per PR

- [ ] `composer test` lulus, test baru untuk aturan bisnis di atas.
- [ ] Copy Bahasa Indonesia memakai "kamu", tombol dan teks memenuhi prinsip UI semua umur.
- [ ] Migration additive, diuji di salinan SQLite, tidak menyentuh data mentee lama.
- [ ] `npm run build` dan `public/build` ikut di-commit bila ada perubahan frontend.
- [ ] README/`docs/` diperbarui (route baru, tabel baru, command terjadwal).
