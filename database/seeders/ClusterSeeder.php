<?php

namespace Database\Seeders;

use App\Models\Cluster;
use Illuminate\Database\Seeder;

class ClusterSeeder extends Seeder
{
    /**
     * The default clusters offered during profiling.
     *
     * @var array<int, array{name: string, label: string, icon: string, description: string}>
     */
    public const CLUSTERS = [
        ['name' => 'teknologi', 'label' => 'Teknologi', 'icon' => 'cpu-chip', 'description' => 'Informatika, data, keamanan siber, dan pengembangan perangkat lunak.'],
        ['name' => 'bisnis', 'label' => 'Bisnis', 'icon' => 'briefcase', 'description' => 'Manajemen, akuntansi, kewirausahaan, dan ekonomi.'],
        ['name' => 'sosial', 'label' => 'Sosial & Humaniora', 'icon' => 'users', 'description' => 'Ilmu sosial, hukum, komunikasi, dan hubungan internasional.'],
        ['name' => 'pendidikan', 'label' => 'Pendidikan', 'icon' => 'academic-cap', 'description' => 'Keguruan, pendidikan bahasa, dan pengembangan kurikulum.'],
        ['name' => 'kesehatan', 'label' => 'Kesehatan', 'icon' => 'heart', 'description' => 'Kedokteran, keperawatan, gizi, dan kesehatan masyarakat.'],
        ['name' => 'engineering', 'label' => 'Engineering', 'icon' => 'wrench-screwdriver', 'description' => 'Teknik sipil, mesin, elektro, dan industri.'],
        ['name' => 'seni', 'label' => 'Seni & Desain', 'icon' => 'paintbrush', 'description' => 'Desain komunikasi visual, seni rupa, dan multimedia.'],
        ['name' => 'pertanian', 'label' => 'Pertanian', 'icon' => 'sun', 'description' => 'Agribisnis, agroteknologi, dan ilmu lingkungan.'],
    ];

    /**
     * Seed the application's clusters.
     */
    public function run(): void
    {
        foreach (self::CLUSTERS as $ordering => $cluster) {
            Cluster::query()->updateOrCreate(
                ['name' => $cluster['name']],
                [
                    'label' => $cluster['label'],
                    'icon' => $cluster['icon'],
                    'description' => $cluster['description'],
                    'ordering' => $ordering + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
