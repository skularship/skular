<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * The guided product tours of the mentee journey, in journey order:
 * landing → profiling → recommendation → dashboard → roadmap → module → Beasiswa Saya.
 */
enum ProductTour: string implements HasLabel
{
    use HasSelectOptions;

    case Landing = 'landing';
    case Profiling = 'profiling';
    case Recommendation = 'recommendation';
    case Dashboard = 'dashboard';
    case Roadmap = 'roadmap';
    case Module = 'module';
    case Tracker = 'tracker';

    /**
     * Get the Indonesian label shown on the Panduan page.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Landing => 'Kenalan dengan Skular',
            self::Profiling => 'Isi profiling 8 langkah',
            self::Recommendation => 'Pilih beasiswa yang cocok',
            self::Dashboard => 'Kenali ruang belajarmu',
            self::Roadmap => 'Ikuti roadmap belajar',
            self::Module => 'Kerjakan modul sampai checkpoint',
            self::Tracker => 'Daftar dan laporkan hasil',
        };
    }

    /**
     * Get the one-line summary of this stage shown on the Panduan page.
     */
    public function summary(): string
    {
        return match ($this) {
            self::Landing => 'Lihat apa yang Skular tawarkan dan cek kesiapanmu lewat Profiling Kilat sebelum daftar.',
            self::Profiling => 'Jawab 8 pertanyaan singkat tentang jenjang, usia, IPK, dan tujuan studimu.',
            self::Recommendation => 'Pilih maksimal 5 beasiswa yang cocok, lalu tandai satu sebagai beasiswa utama.',
            self::Dashboard => 'Pantau progres, label hasil penilaian AI, dan langkah berikutnya yang paling penting.',
            self::Roadmap => 'Kerjakan fase aktif modul demi modul. Fase berikutnya terbuka setelah fase ini selesai.',
            self::Module => 'Pembukaan, Pahami, Quick Question, Praktik dengan feedback AI, lalu Checkpoint.',
            self::Tracker => 'Unggah bukti pendaftaran, laporkan hasilnya, dan rayakan dengan twibbon kalau lolos.',
        };
    }

    /**
     * Get the heroicon that represents this stage.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Landing => 'sparkles',
            self::Profiling => 'identification',
            self::Recommendation => 'academic-cap',
            self::Dashboard => 'home',
            self::Roadmap => 'map',
            self::Module => 'book-open',
            self::Tracker => 'document-arrow-up',
        };
    }

    /**
     * Get the named route where this tour runs, used by "Ulangi tur".
     *
     * The module tour runs inside any module, so it points at the roadmap
     * where the mentee picks the module to open.
     */
    public function routeName(): string
    {
        return match ($this) {
            self::Landing => 'home',
            self::Profiling => 'profiling',
            self::Recommendation => 'profiling.recommendation',
            self::Dashboard => 'dashboard',
            self::Roadmap, self::Module => 'roadmap.show',
            self::Tracker => 'scholarships.tracker',
        };
    }
}
