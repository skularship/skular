# Brief Feedback Gate & Webinar Skular

17 Nov 2026 · Farhan · Versi untuk review pemilik (versi dev menyusul setelah disetujui)

## Ringkasan

Akses gratis Skular kita ubah jadi **pertukaran yang adil**: mentee membayar dengan feedback jujur, kita membalas dengan materi, AI, dan kelas intensif gratis. Dalam 2-4 minggu, data ini menjawab dua keputusan sekaligus: **apa yang harus diperbaiki** dan **kapan serta di harga berapa Pro dinyalakan**.

Tiga temuan penting dari kode:

- Feedback modul **sudah ada** di langkah Checkpoint, tetapi **opsional** dan hanya berisi centang hal positif. Jadi yang perlu dilakukan adalah upgrade, bukan membangun dari nol.
- Pembayaran (Midtrans) dan fitur Pro (Vault, Checklist, reminder) **sudah dibangun tapi masih mati** (`PREMIUM_ENABLED=false`). Yang kurang adalah bukti bahwa orang mau bayar, bukan fiturnya.
- Dashboard admin belum punya pandangan agregat atas feedback. Respons hanya bisa dibaca satu per satu.

Catatan: website live tidak bisa dibuka dari lingkungan analisis, jadi analisis berdasarkan kode di repo.

## Analisis user flow dan perilaku mentee

Alur: Daftar → Profiling 8 langkah → **[baru] Layar komitmen** → Pilih maks 5 beasiswa → Roadmap → Materi + kuis → Praktik + nilai AI → **[baru] Gate feedback wajib** → modul berikutnya; di akhir fase → **[baru] Gate milestone fase** → undangan webinar.

- **Nilai pertama datang terlambat.** Mentee mengisi 8 langkah profiling sebelum melihat rekomendasi. Titik rawan berhenti; perlu diukur di funnel.
- **Momen paling emosional ada di penilaian AI praktik.** Feedback paling jujur diambil tepat setelahnya (di Checkpoint).
- **Feedback sekarang bias positif.** Pilihan yang ada hanya hal baik, tanpa skala dan tanpa ruang untuk keluhan.
- **Feedback opsional berarti sunyi.** "Tandai selesai" tidak menunggu feedback.
- **Belum ada sinyal bayar.** Tidak ada pertanyaan tentang alternatif, fitur bernilai, atau harga wajar.

## Rancangan feedback gate wajib

Feedback jadi **syarat** membuka modul berikutnya, selesai dalam < 45 detik. Wajib, singkat, jujur.

### 1. Kontrak di awal: "Akses eksklusif"

Di akhir profiling, satu layar komitmen:

> *Kamu masuk program akses gratis Skular. Gratisnya eksklusif: sebagai gantinya, kamu berkomitmen memberi masukan jujur di setiap akhir modul. Masukanmu langsung kami pakai untuk memperbaiki Skular.*
>
> [ ] Saya setuju dan siap memberi masukan

Waktu persetujuan dicatat di data user.

### 2. Gate tiap modul (Checkpoint)

| No | Pertanyaan | Bentuk | Wajib |
| --- | --- | --- | --- |
| 1 | Seberapa membantu modul ini untuk persiapan beasiswamu? | Skala 1-5 | Ya |
| 2 | Bagian mana yang paling menyulitkan? | Chip: Materi, Quick Question, Praktik, Penilaian AI, AI Mentor, Alur website, Tidak ada | Ya |
| 3 | Pertanyaan terbuka bergilir | Teks | Ya, min 20 karakter |
| 4 | Bila skor 1-3: "Apa yang bikin kurang membantu?" | Teks | Ya bila muncul |

Pertanyaan bergilir (diatur admin per modul):

- "Di bagian mana kamu sempat bingung atau hampir berhenti?"
- "Apa satu hal yang paling berguna dari modul ini buat aplikasimu?"
- "Kalau bisa ubah satu hal di modul ini, apa?"

### 3. Gate milestone (sekali per fase)

| No | Pertanyaan | Bentuk |
| --- | --- | --- |
| 1 | Kalau Skular hilang besok, seberapa kecewa kamu? | Sangat / Agak / Biasa saja |
| 2 | Sebelum Skular, persiapan pakai apa dan berapa biayanya? | Chip + kisaran nominal |
| 3 | Fitur mana yang paling layak dibayar? | Maks 2: AI penilaian praktik, AI Mentor, Vault, Checklist + reminder, Kelas/webinar |
| 4 | Harga per bulan yang wajar? | < Rp25 rb, 25-50 rb, 50-100 rb, > 100 rb |
| 5 | Mau ikut kelas intensif / webinar gratis? | Ya / Tidak + jam (Malam hari kerja / Weekend) |

Pertanyaan 1 = tes Sean Ellis: **≥ 40% "Sangat kecewa"** = sinyal kuat siap dimonetisasi.

## Webinar dan kelas intensif sebagai wawancara kelompok

| Format | Siapa | Tujuan | Frekuensi |
| --- | --- | --- | --- |
| Kelas intensif gratis | Peserta berbayar mentoring Akademi Unggulan yang belum lolos | Merawat pelanggan pertama, gali alasan gagal dan kebutuhan berikutnya | 1 batch, 2-4 sesi |
| Webinar sharing | Mentee yang menjawab "Ya" di gate milestone | Gali masalah, alternatif, kesediaan bayar | 2x sebulan |

Beri materi yang benar-benar dibutuhkan (bedah esai, evaluasi dokumen yang gagal); kepercayaan pelanggan pertama lebih mahal dari data.

Susunan sesi 90 menit:

1. Materi inti, 45 menit.
2. Bedah kasus peserta, 20 menit.
3. Sharing terbuka, 20 menit:
   - "Terakhir kali menyiapkan beasiswa, bagian mana paling berat?"
   - "Sebelum Skular, kamu menyelesaikannya dengan cara apa?"
   - "Di Skular, kapan kamu hampir berhenti?"
   - "Kalau Skular hilang besok, kamu cari gantinya atau cuek?"
4. Polling 2 menit: kepuasan 1-5 dan harga wajar.

Dari sisi web: ekspor daftar peserta webinar dari gate milestone, dan tag "Alumni Akademi Unggulan" di data user.

## Dashboard admin dan metrik keputusan

Halaman "Insight Feedback" di `/admin`:

| Metrik | Cara baca | Ambang keputusan |
| --- | --- | --- |
| Rata-rata skor membantu per modul | Skor < 3,5 masuk antrean perbaikan | Perbaiki 3 modul terburuk dulu |
| Kategori kesulitan terbanyak | Bar chart per modul | > 30% di satu modul = masalah serius |
| Funnel per modul | Di mana orang berhenti | Turun > 40% di satu langkah = cek UX |
| % "Sangat kecewa" | Gate milestone | **≥ 40% = nyalakan Pro** |
| Fitur layak dibayar | Peringkat pilihan | Fitur teratas = jualan utama Pro |
| Sebaran harga wajar | Rentang terbanyak | Patokan harga launching |
| Feed jawaban terbuka | Filter per modul dan skor | Bahan cerita dan copy marketing |
| Calon peserta webinar | Ekspor CSV | Undangan webinar |

Keputusan Pro setelah minimal **40 respons gate milestone**.

## Scope untuk Rafi, risiko, dan keputusan

### Urutan kerja

1. **Hari 1-2:** Gate modul wajib (upgrade form Checkpoint, kunci "Tandai selesai" di server). Data lama `module_feedbacks` tetap; kolom baru additive.
2. **Hari 2:** Layar komitmen di akhir profiling.
3. **Hari 3-4:** Gate milestone per fase + opt-in webinar.
4. **Hari 4-5:** Halaman Insight Feedback + ekspor CSV.
5. **Menyusul:** tag Alumni Akademi Unggulan, pertanyaan bergilir yang diatur admin.

### Risiko

- **Jawaban asal:** min 20 karakter, tolak teks berulang/tanda baca saja.
- **Mentee berhenti karena gate:** bila langkah feedback kehilangan > 15% orang, pangkas jadi 2 pertanyaan.
- **Mentee lama:** gate hanya untuk modul yang belum selesai.
- **Bias pelanggan pertama:** pisahkan data alumni Akademi Unggulan saat memutuskan harga.

### Yang perlu diputuskan

- [ ] Setuju skala 1-5 dan daftar chip kesulitan?
- [ ] Rentang harga sesuai rencana harga Pro?
- [ ] Ambang 40% "Sangat kecewa" sebagai syarat nyalakan Pro?
- [ ] Jadwal batch pertama kelas intensif Akademi Unggulan
