# PROGRES-LEGAL.md — Halaman legal Skular

Catatan serah terima untuk pekerjaan halaman legal (prompt "Halaman Legal v1.0", 16 Nov 2026).
Bukan nasihat hukum. Semua teks legal wajib ditinjau konsultan hukum sebelum dianggap final.

## Status tahap

| Tahap | Status |
|---|---|
| T0 Rekon dan rencana | Selesai |
| T1 Konfigurasi legal terpusat | Selesai: `config/legal.php` (env `LEGAL_*`), `php artisan legal:check` (gagal di production / `--strict` bila ada `[ISI`/`[KEPUTUSAN`), checklist di README dan `.env.example` |
| T2 Halaman, rute, layout, tautan | Selesai: 4 rute publik, `<x-legal.document>`/`<x-legal.section>`/`<x-legal.links>`, layout public diperbaiki (FAQ aktif dinamis, skip-link `#konten-utama`, deskripsi per halaman), tautan di footer landing, layout public, auth, app (sidebar + bawah halaman di HP), form daftar, /premium; sitemap + SEO |
| T3 Kebijakan Privasi | Selesai (perlu tinjauan hukum) |
| T4 Cookie: kebijakan, banner, gating GA4 | Selesai: banner di alur halaman (tidak menutupi form/tombol bayar), `Terima semua`/`Tolak semua` setara, `Atur pilihan`, buka ulang dari footer; gtag.js hanya setelah izin; tarik izin = `ga-disable` + hapus `_ga*` |
| T5 Syarat & Ketentuan | Selesai (perlu tinjauan hukum); bagian Program apresiasi tersembunyi selama `legal.features.success_appreciation=false` |
| T6 Kebijakan Pengembalian Dana | Selesai + `docs/refund-manual.md` (langkah manual admin). Fitur refund admin = pekerjaan terpisah |
| T7 Pencatatan persetujuan | Selesai: `user_consents`, `ConsentService`, checkbox daftar (validasi server), middleware `legal.consent` + `/persetujuan` (Google baru, akun lama, versi naik), checkout, riwayat baca-saja di Filament (halaman edit pengguna) |
| T8 Tes, dokumentasi, penutup | Selesai: Pint, PHPStan, 762 tes Pest hijau; README, docs/routes.md, docs/database.md, AGENTS.md diperbarui. File ini dibiarkan sebagai catatan serah terima karena keputusan terbuka di bawah belum diisi |

### Catatan desain T7 (login Google)

Akun Google baru tetap dibuat saat callback (tidak mengubah `GoogleAuthService`, area Auth sensitif). Middleware `EnsureLegalConsent` di grup `auth`+`verified`+`legal.consent` menahan mentee di `/persetujuan` sampai menyetujui; tombol "Keluar" tersedia bila tidak setuju. Middleware yang sama menangani akun lama dan kenaikan versi. Halaman legal, logout, `/health`, dan `/settings/*` (hapus akun) tidak terkena. Hasil cek disimpan di session per kunci versi agar tidak query di setiap request.

Bila mentee menolak, akun Google tetap ada tanpa persetujuan (KEPUTUSAN: hapus otomatis atau tidak).

## Keputusan pemilik (Bagian A)

Bagian A di dokumen prompt belum diisi. Agent memakai **usulan default** dari dokumen untuk kebijakan
(ditandai di bawah) dan penanda `[ISI: ...]` untuk fakta yang tidak boleh dikarang.

| No | Item | Yang dipakai sekarang | Status |
|---|---|---|---|
| 1 | Badan usaha, alamat, NIB | `[ISI: ...]` lewat env `LEGAL_*` | Menunggu pemilik |
| 2 | Email kontak & privasi | `[ISI: ...]` lewat env `LEGAL_*` | Menunggu pemilik |
| 3 | Pengguna < 18 tahun | Usulan default: pernyataan "18 tahun ke atas, atau orang tua/wali sudah mengizinkan" saat daftar dan di layar persetujuan | KEPUTUSAN: konfirmasi pemilik + konsultan |
| 4 | Refund | Usulan default: penuh dalam 7 hari bila Pro belum dipakai (belum unggah Vault, belum buat checklist); penuh untuk tagihan ganda, salah tagih, gangguan teknis tak teratasi; lainnya ditinjau per kasus | KEPUTUSAN: konfirmasi pemilik |
| 5 | Biaya gateway saat refund | Usulan default: dikembalikan penuh | KEPUTUSAN: cek biaya Midtrans |
| 6 | Program apresiasi lolos | LOCKED: bagian nonaktif, tanpa angka/janji (`legal.features.success_appreciation=false`) | Sesuai |
| 7 | Berbagi data B2B | Usulan default: tidak menjual; hanya agregat/anonim atau dengan persetujuan terpisah | KEPUTUSAN: konfirmasi pemilik |
| 8 | Kota pengadilan | `[ISI: kota pengadilan negeri]` | Menunggu pemilik |
| 9 | Lokasi server, penyedia email | `[ISI: ...]` | Menunggu pemilik |

## T0. Inventaris data pribadi (dari kode, commit d9057f9)

| Data | Tempat simpan | Tujuan | Penerima di luar Skular | Masa simpan (perilaku kode) |
|---|---|---|---|---|
| Nama, email, hash password, `remember_token` | `users` | Akun, login | Midtrans (nama, email saat checkout); penyedia email (email) | Sampai akun dihapus |
| `google_id`, `avatar_url` | `users` | Login Google | Google (OAuth) | Sampai akun dihapus |
| 2FA secret, recovery codes, passkey | `users`, `passkeys` (cascade) | Keamanan akun | - | Sampai akun dihapus / dinonaktifkan |
| Profil: `age`, `gpa`, `has_sktm`, `level`, `desired_level`, `desired_location`, `cluster_id`, `phone` | `users` | Rekomendasi beasiswa (deterministik, **tidak** dikirim ke AI) | - | Sampai akun dihapus |
| `onboarding_tours`, `profile_completed_at` | `users` | Status tur | - | Sampai akun dihapus |
| Jawaban praktik (teks + file) | `user_practice_responses` (cascade), file `storage/app/private/practice/{user_id}` | Penilaian AI | OpenRouter + penyedia model (isi jawaban, bukan profil) | Baris: sampai akun dihapus. **File tidak ikut terhapus** |
| Sesi brainstorming | `user_brainstorming_sessions` (cascade) | AI Brainstorming | OpenRouter | Sampai akun dihapus |
| Chat AI Mentor | **Tidak disimpan di DB**; transkrip hanya di state komponen Livewire (maks `MAX_TRANSCRIPT`) | AI Mentor | OpenRouter (pertanyaan + konteks modul) | Selama halaman terbuka |
| Pemakaian kuota Mentor | `module_mentor_usages` (cascade) | Batas kuota | - | Sampai akun dihapus |
| Progres, checkpoint, feedback modul | `checkpoints`, `module_feedbacks` (cascade) | Jalur belajar | - | Sampai akun dihapus |
| Pilihan beasiswa + bukti daftar | `scholarship_applications` (cascade), file `private/{PROOF_FOLDER}/{user_id}` | Tracker, twibbon | - | Baris: sampai akun dihapus. **File tidak ikut terhapus** |
| Checklist, reminder log | `checklist_items`, `reminder_logs` (cascade lewat application) | Pro | Penyedia email | Sampai akun dihapus |
| Vault (pdf/jpg/png/doc/docx, maks 50 file × 5 MB) | `vault_documents` (cascade), file `private/vault/{user_id}` | Pro | - | Baris: sampai akun dihapus. **File tidak ikut terhapus** |
| Tiket bantuan + screenshot + user agent | `support_tickets` (`user_id` → NULL), file `private/{SCREENSHOT_FOLDER}/{user_id}` | Bantuan | - | **Tetap tersimpan** tanpa tautan akun |
| Laporan info beasiswa | `scholarship_info_reports` (`user_id` → NULL) | Koreksi data beasiswa | - | **Tetap tersimpan** tanpa tautan akun |
| Order (order_id `SKL-…`, nominal, kode promo, referrer) | `orders` (**cascade**) | Pembayaran | Midtrans | **Ikut terhapus** saat akun dihapus |
| Langganan Pro | `subscriptions` (cascade) | Hak akses Pro | - | Ikut terhapus |
| Notifikasi Midtrans mentah | `payment_events` (tanpa FK user) | Audit pembayaran | - | **Tetap tersimpan** (payload JSON dari Midtrans) |
| Kode referral | `referral_codes` (cascade); `orders.referrer_user_id` → NULL | Referral | - | Sampai akun dihapus |
| Session | tabel `sessions` (`user_id`, `ip_address`, `user_agent`, payload) | Login | - | Sesuai `SESSION_LIFETIME` (120 menit) + lottery GC |
| Log aplikasi | `storage/logs` | Operasional | Hosting | Tergantung rotasi log server. AI: hanya metadata (id, durasi, alasan), isi jawaban tidak dicatat. `slow.request` mencatat path, bukan query |
| Role/permission admin | `model_has_roles` (cascade) | Akses admin | - | Sampai akun dihapus |
| Tabel `agent_conversations` (laravel/ai) | Ada di skema, **tidak dipakai** (agent tidak memakai RemembersConversations) | - | - | - |
| Analytics GA4 | Cookie `_ga`, `_ga_*` di browser; data di Google | Statistik kunjungan, URL dibersihkan dari token/email | Google | Sesuai pengaturan retensi properti GA4 `[ISI: retensi GA4]` |

Cookie & storage terverifikasi dari config/kode: `skular-session` (`config/session.php`, dari `APP_NAME=Skular`, 120 menit),
`XSRF-TOKEN` (umur sama dengan session), `remember_web_<hash>` (hanya bila "Ingat saya", default Laravel ±400 hari),
GA4 `_ga`/`_ga_<id>` (hanya production), Midtrans Snap (`snap.js` dimuat hanya saat klik bayar di /premium),
`localStorage` `skular.tour.<key>` (tur landing tamu), `sessionStorage` jawaban wizard profiling & Profiling Kilat (sementara).

### Apa yang terjadi saat akun dihapus (`Settings/DeleteUserForm`)

- Butuh password saat ini. **Akun yang dibuat lewat Google memakai password acak** sehingga praktis tidak bisa menghapus sendiri.
- `User::delete()` tanpa hook model: semua tabel ber-FK cascade ikut terhapus (lihat tabel), termasuk `orders` dan `subscriptions`.
- Tiket bantuan dan laporan info beasiswa tetap ada dengan `user_id = NULL` (isi tiket bisa memuat data pribadi).
- `payment_events` tetap ada.
- File di disk privat (practice, bukti daftar, vault, screenshot tiket) **tidak dihapus** (yatim di `storage/app/private`).
- Backup database/hosting: tidak diatur di repo.

### KEPUTUSAN TERBUKA (bukan wewenang agent)

1. **Hak hapus vs "data mentee adalah aset" vs kewajiban arsip keuangan.** Hapus akun menghapus order (bukti transaksi) tetapi menyisakan file di disk dan tiket bantuan. Perlu kebijakan: data apa dihapus, apa disimpan (dan berapa lama) untuk kewajiban hukum/pajak, dan pembersihan file. Halaman privasi menulis perilaku apa adanya.
2. **Pengguna Google tidak bisa menghapus akun sendiri** (password acak). Sementara: lewat email privasi. Perlu perbaikan alur (pekerjaan auth terpisah).
3. **Usia/anak (Bagian A no. 3)**: dipakai pernyataan mandiri, tanpa verifikasi. UU PDP Pasal 25 ayat (2) mewajibkan persetujuan orang tua/wali untuk data anak; cukup tidaknya pernyataan mandiri perlu pendapat konsultan.
4. **Refund (no. 4, 5)** dan **berbagi data B2B (no. 7)**: memakai usulan default, perlu konfirmasi.
5. **Pendaftaran PSE lingkup privat.** Permenkominfo 5/2020 Pasal 2 ayat (1) "Setiap PSE Lingkup Privat wajib melakukan pendaftaran"; ayat (2) huruf b mencakup situs untuk menawarkan/memperdagangkan barang/jasa dan pemrosesan data pribadi. Status pendaftaran Skular tidak diketahui agent: `[ISI: nomor TDPSE]` tidak ditampilkan, cek oleh pemilik.
6. **Retensi log server, backup, lokasi server, penyedia email**: tidak ada di repo.

### Hasil pencarian (T0)

- **Midtrans, syarat website aktivasi** (docs.midtrans.com, "website or application criterias"): situs harus aktif, bisa diakses, sesuai kategori bisnis, info produk/jasa jelas dan lengkap, harga dalam Rupiah. Halaman resmi yang dibaca **tidak** menyebut wajib ada S&K/refund/kontak. Dugaan di Bagian D belum terbukti, tetapi halaman legal tetap berguna.
- **Midtrans refund** (docs.midtrans.com/reference/refund-transaction, /docs/how-can-i-refund-transaction): refund lewat Midtrans hanya untuk kartu kredit, e-wallet (GoPay, ShopeePay, DANA, OVO), QRIS, Kredivo, Akulaku, status Settlement. Jendela: mis. QRIS off-us 7 hari, GoPay QRIS on-us 45 hari. Virtual account/bank transfer/minimarket tidak didukung: "the process of refunding funds to customers is completely left to the merchant" (transfer manual).
- **Sumber hukum terverifikasi** (dibaca): UU 27/2022 lewat transkripsi Wikisource (PDF resmi JDIH Kemenkeu berupa pindaian): Pasal 4, 5–13, 20, 25, 30, 32, 40, 41, 43, 44, 46, 56. Permenkominfo 5/2020 Pasal 2 (JDIH Komdigi).
- **Tidak terverifikasi** (tidak dikutip di halaman): UU 8/1999 Pasal 18 (teks tidak terbaca dari BPK), PP 71/2019 Pasal 6, PP 17/2025 (PP TUNAS) termasuk apakah berlaku untuk Skular, PP 33/2026 (teks resmi tidak ditemukan; ulasan pihak ketiga menyebut berlaku 16 Januari 2027).

## Rencana berkas

- `config/legal.php` (+ env `LEGAL_*` di `.env.example`), `app/Console/Commands/LegalCheckCommand.php` (`legal:check`)
- `routes/web.php`: 4 rute legal + `persetujuan`; `app/Http/Middleware/EnsureLegalConsent.php`
- `resources/views/legal/*.blade.php`, komponen `resources/views/components/legal/*`
- `resources/views/layouts/public.blade.php` (nav aktif dinamis, skip-link generik, footer legal)
- `resources/views/partials/{analytics,cookie-consent,legal-links}.blade.php`, `resources/js/cookie-consent.js`
- Migration `create_user_consents_table`, model `UserConsent`, enum `LegalDocument`/`ConsentSource`, `ConsentServiceInterface` + `ConsentService`
- `CreateNewUser`, `register.blade.php`, `Premium/Index`, Livewire `Legal/Consent`
- Filament: `UserConsentsRelationManager` (baca-saja)
- Tes, README, docs, AGENTS.md
