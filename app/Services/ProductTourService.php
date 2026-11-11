<?php

namespace App\Services;

use App\Contracts\ProductTourServiceInterface;
use App\Data\TourStepData;
use App\Enums\ProductTour;
use App\Models\User;

/**
 * Coach-mark content of every onboarding tour and the per-mentee record of
 * finished tours (users.onboarding_tours).
 *
 * Copy follows the product rules: "kamu", plain language, labels instead of
 * AI scores, at most 5 scholarships, and the AI only helps; it never writes
 * the mentee's answer. Targets are CSS selectors on stable data-test /
 * data-tour hooks; a missing target falls back to a centred card.
 */
class ProductTourService implements ProductTourServiceInterface
{
    /**
     * Get the coach-mark steps of a tour, in order.
     *
     * @param  ProductTour  $tour  The tour.
     * @return array<int, TourStepData>
     */
    public function steps(ProductTour $tour): array
    {
        $rows = match ($tour) {
            ProductTour::Landing => [
                [null, 'Selamat datang di Skular 👋', 'Skular menemanimu menyiapkan beasiswa dari nol: kenali profilmu, pilih beasiswa yang cocok, lalu belajar lewat roadmap dengan latihan yang diberi feedback.'],
                ['#manfaat', 'Apa yang kamu dapat', 'Rekomendasi beasiswa sesuai profilmu, modul belajar bertahap, dan feedback untuk setiap latihan menulismu.'],
                ['#cara-kerja', 'Empat tahap, satu perjalanan', 'Profiling, rekomendasi beasiswa, roadmap belajar, lalu latihan dan checkpoint. Setelah daftar, panduan ini menemanimu di setiap tahap.'],
                ['#cek-kesiapan', 'Cek kesiapanmu dulu', 'Penasaran seberapa siap kamu? Coba Profiling Kilat di sini, tanpa perlu daftar.'],
                ['#mulai', 'Siap mulai?', 'Daftar gratis, lalu isi profiling 8 langkah. Kami pandu langkah demi langkah sampai kamu siap mendaftar beasiswa.'],
            ],
            ProductTour::Profiling => [
                [null, 'Profiling 8 langkah', 'Jawab 8 pertanyaan singkat tentang jenjang, usia, IPK, dan tujuan studimu. Jawabanmu dipakai untuk mencocokkan beasiswa.'],
                ['[data-test="profiling-wizard"]', 'Satu pertanyaan per langkah', 'Pilih jawaban yang paling sesuai kondisimu sekarang. Jujur itu penting supaya rekomendasinya akurat.'],
                ['[data-test="profiling-next"]', 'Lanjut atau kembali kapan saja', 'Jawabanmu tersimpan sementara di perangkat ini, jadi aman kalau kamu kembali ke langkah sebelumnya. Profil bisa kamu ubah lagi nanti.'],
            ],
            ProductTour::Recommendation => [
                ['[data-test="recommendation-grid"]', 'Beasiswa yang cocok untukmu', 'Setiap kartu diberi label Sangat Cocok, Cocok, atau Kurang Cocok berdasarkan profilmu. Buka kartunya untuk melihat detail dan alasan kecocokannya.'],
                ['[data-test="scholarship-card"] label', 'Pilih maksimal 5 beasiswa', 'Centang beasiswa yang mau kamu siapkan. Beasiswa yang pendaftarannya sudah tutup tidak bisa dipilih.'],
                ['[data-test="make-primary"]', 'Tandai beasiswa utama', 'Tekan ikon bintang untuk memilih satu beasiswa utama. Roadmap belajarmu mengikuti beasiswa ini lebih dulu.'],
                ['[data-test="confirm-selection"]', 'Simpan dan mulai belajar', 'Setelah disimpan, roadmap belajarmu langsung tersusun di dashboard.'],
            ],
            ProductTour::Dashboard => [
                [null, 'Ini ruang belajarmu', 'Semua yang perlu kamu kerjakan untuk beasiswa yang dipilih ada di sini.'],
                ['[data-test="progress-overview"]', 'Pantau progresmu', 'Lihat jumlah modul yang selesai dan hasil penilaian AI dalam bentuk label, dari Perlu diperbaiki sampai Sangat baik.'],
                ['[data-test="next-best-action"]', 'Langkah berikutnya', 'Bingung mulai dari mana? Kartu ini selalu menunjukkan satu langkah paling penting untukmu sekarang.'],
                ['[data-test="dashboard-roadmap"]', 'Roadmap belajar', 'Lihat fase yang sedang kamu kerjakan, lalu buka roadmap lengkap untuk melihat semua modulnya.'],
                ['[data-tour="main-nav"]', 'Menu utama', 'Dari sini kamu bisa membuka Roadmap, Beasiswa Saya, CV, Panduan, dan Bantuan kalau ada kendala.'],
            ],
            ProductTour::Roadmap => [
                ['[data-test="roadmap-active-fase"]', 'Fase aktif', 'Kerjakan modul secara berurutan. Modul berikutnya terbuka setelah modul sebelumnya selesai.'],
                ['[data-test="roadmap-upcoming-fase"]', 'Fase berikutnya', 'Fase ini terbuka otomatis setelah fase aktifmu selesai.'],
                ['[data-test="roadmap-locked"]', 'Masih tertutup awan', 'Fase lainnya disembunyikan supaya kamu fokus. Tekan "Lihat lebih lanjut" kalau ingin melihat namanya.'],
            ],
            ProductTour::Module => [
                ['[data-module-steps]', '5 langkah di setiap modul', 'Pembukaan, Pahami, Quick Question, Praktik, lalu Checkpoint. Ikuti urutannya supaya pemahamanmu utuh.'],
                [null, 'Pahami dan Quick Question', 'Baca materinya dulu, lalu jawab kuis Benar/Salah singkat untuk mengecek pemahamanmu.'],
                [null, 'Praktik dengan feedback AI', 'Tulis jawabanmu sendiri. AI menilainya dengan label dan saran perbaikan, tapi tidak menuliskan jawaban untukmu. AI Mentor siap menjawab pertanyaan, maksimal '.$this->mentorLimit().' pertanyaan per modul.'],
                [null, 'Checkpoint', 'Checkpoint terbuka setelah semua latihan tersimpan dan selesai dinilai. Tandai modul selesai untuk membuka modul berikutnya.'],
            ],
            ProductTour::Tracker => [
                ['[data-test="application-card"]', 'Beasiswa Saya', 'Pantau status setiap beasiswa yang kamu pilih, dari persiapan sampai hasil.'],
                ['[data-test="open-confirm"]', 'Konfirmasi pendaftaran', 'Sudah mendaftar di situs penyelenggara? Unggah bukti pendaftarannya di sini.'],
                [null, 'Laporkan hasilnya', 'Setelah pengumuman, laporkan hasilmu. Kalau lolos, kamu bisa membuat twibbon untuk merayakannya.'],
                [null, 'Perjalananmu lengkap 🎉', 'Itu seluruh alur Skular. Kalau ada kendala, buka menu Bantuan. Panduan ini bisa kamu ulang kapan saja dari menu Panduan.'],
            ],
        };

        return array_map(
            fn (array $row): TourStepData => new TourStepData(target: $row[0], title: $row[1], body: $row[2]),
            $rows,
        );
    }

    /**
     * Determine whether the tour should open by itself for this mentee.
     *
     * @param  User  $user  The signed-in mentee.
     * @param  ProductTour  $tour  The tour on the current page.
     */
    public function shouldAutoStart(User $user, ProductTour $tour): bool
    {
        return ! $this->isCompleted($user, $tour);
    }

    /**
     * Remember that the mentee finished or skipped a tour.
     *
     * @param  User  $user  The signed-in mentee.
     * @param  ProductTour  $tour  The finished tour.
     */
    public function markCompleted(User $user, ProductTour $tour): void
    {
        if ($this->isCompleted($user, $tour)) {
            return;
        }

        $tours = $this->completedTours($user);
        $tours[$tour->value] = now()->toIso8601String();

        $user->forceFill(['onboarding_tours' => $tours])->save();
    }

    /**
     * Forget a finished tour so it opens again on its page.
     *
     * @param  User  $user  The signed-in mentee.
     * @param  ProductTour  $tour  The tour to replay.
     */
    public function reset(User $user, ProductTour $tour): void
    {
        $tours = $this->completedTours($user);

        if (! array_key_exists($tour->value, $tours)) {
            return;
        }

        unset($tours[$tour->value]);

        $user->forceFill(['onboarding_tours' => $tours === [] ? null : $tours])->save();
    }

    /**
     * Determine whether the mentee finished or skipped a tour.
     *
     * @param  User  $user  The signed-in mentee.
     * @param  ProductTour  $tour  The tour.
     */
    public function isCompleted(User $user, ProductTour $tour): bool
    {
        return array_key_exists($tour->value, $this->completedTours($user));
    }

    /**
     * Get the stored {tour key: timestamp} map, ignoring malformed data.
     *
     * @return array<string, string>
     */
    private function completedTours(User $user): array
    {
        $tours = $user->onboarding_tours;

        if (! is_array($tours)) {
            return [];
        }

        $completed = [];

        foreach ($tours as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $completed[$key] = $value;
            }
        }

        return $completed;
    }

    /**
     * Get the per-module AI Mentor quota so the copy never drifts from the config.
     */
    private function mentorLimit(): int
    {
        $limit = config('ai.module_chat.limit', 5);

        return is_numeric($limit) ? (int) $limit : 5;
    }
}
