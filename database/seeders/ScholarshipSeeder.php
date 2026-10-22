<?php

namespace Database\Seeders;

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Enums\ScholarshipStatus;
use App\Models\Cluster;
use App\Models\Scholarship;
use Illuminate\Database\Seeder;

class ScholarshipSeeder extends Seeder
{
    /**
     * Demo scholarships. No real personal data is used.
     */
    public const SCHOLARSHIPS = [
        [
            'scholarship_name' => 'Beasiswa Unggulan Nusantara',
            'institution' => 'Yayasan Pendidikan Nusantara',
            'level' => 's1',
            'location' => 'Indonesia',
            'scope' => 'dalam_negeri',
            'sktm_require' => false,
            'minimum_value' => 3.25,
            'maximum_age' => 25,
            'closing_date' => '+45 days',
            'estimated_quota' => 150,
            'link' => 'https://example.com/beasiswa-unggulan',
            'note' => 'Mencakup bantuan biaya kuliah dan biaya hidup bulanan.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa Pemerintah Daerah Cendekia',
            'institution' => 'Pemerintah Provinsi',
            'level' => 's1',
            'location' => 'Indonesia',
            'scope' => 'dalam_negeri',
            'sktm_require' => true,
            'minimum_value' => 3.00,
            'maximum_age' => 23,
            'closing_date' => '+18 days',
            'estimated_quota' => 75,
            'link' => 'https://example.com/beasiswa-cendekia',
            'note' => 'Diperuntukkan bagi mahasiswa dari keluarga prasejahtera.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa Talenta Digital',
            'institution' => 'Komunitas Teknologi Indonesia',
            'level' => 's1',
            'location' => 'Indonesia',
            'scope' => 'dalam_negeri',
            'sktm_require' => false,
            'minimum_value' => 3.50,
            'maximum_age' => 24,
            'closing_date' => '+60 days',
            'estimated_quota' => 40,
            'link' => 'https://example.com/talenta-digital',
            'note' => 'Fokus pada mahasiswa bidang teknologi dan data.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Global Futures Scholarship',
            'institution' => 'Global Futures Foundation',
            'level' => 's2',
            'location' => 'Belanda',
            'scope' => 'luar_negeri',
            'sktm_require' => false,
            'minimum_value' => 3.50,
            'maximum_age' => 30,
            'closing_date' => '+90 days',
            'estimated_quota' => 20,
            'link' => 'https://example.com/global-futures',
            'note' => 'Termasuk tiket pesawat dan asuransi perjalanan.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Asia Pacific Leader Award',
            'institution' => 'Asia Pacific University Network',
            'level' => 's2',
            'location' => 'Australia',
            'scope' => 'luar_negeri',
            'sktm_require' => false,
            'minimum_value' => 3.40,
            'maximum_age' => 32,
            'closing_date' => '+120 days',
            'estimated_quota' => 15,
            'link' => 'https://example.com/apac-leader',
            'note' => null,
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa Vokasi Terampil',
            'institution' => 'Politeknik Negeri',
            'level' => 'd3',
            'location' => 'Yogyakarta',
            'scope' => 'dalam_negeri',
            'sktm_require' => true,
            'minimum_value' => 2.75,
            'maximum_age' => 21,
            'closing_date' => '+30 days',
            'estimated_quota' => 60,
            'link' => 'https://example.com/vokasi-terampil',
            'note' => 'Program diploma dengan ikatan kerja industri.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa Diploma Unggul',
            'institution' => 'Konsorsium Industri Manufaktur',
            'level' => 'd4',
            'location' => 'Bandung',
            'scope' => 'dalam_negeri',
            'sktm_require' => false,
            'minimum_value' => 3.00,
            'maximum_age' => 25,
            'closing_date' => '+25 days',
            'estimated_quota' => 30,
            'link' => 'https://example.com/diploma-unggul',
            'note' => 'Prioritas bidang engineering dan manufaktur.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa Muda Berprestasi',
            'institution' => 'Yayasan Generasi Emas',
            'level' => 'sma',
            'location' => 'Indonesia',
            'scope' => 'dalam_negeri',
            'sktm_require' => false,
            'minimum_value' => 3.00,
            'maximum_age' => 19,
            'closing_date' => '+40 days',
            'estimated_quota' => 100,
            'link' => 'https://example.com/muda-berprestasi',
            'note' => 'Terbuka untuk siswa SMA dan SMK sederajat.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa SMK Siap Kerja',
            'institution' => 'Badan Kerjasama Vokasi',
            'level' => 'smk',
            'location' => 'Jakarta',
            'scope' => 'dalam_negeri',
            'sktm_require' => true,
            'minimum_value' => 2.75,
            'maximum_age' => 20,
            'closing_date' => '+12 days',
            'estimated_quota' => 50,
            'link' => 'https://example.com/smk-siap-kerja',
            'note' => 'Termasuk program magang bersertifikat.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa Doktoral Riset',
            'institution' => 'Lembaga Riset Nasional',
            'level' => 's2',
            'location' => 'Jepang',
            'scope' => 'luar_negeri',
            'sktm_require' => false,
            'minimum_value' => 3.60,
            'maximum_age' => 35,
            'closing_date' => '+150 days',
            'estimated_quota' => 12,
            'link' => 'https://example.com/doktoral-riset',
            'note' => 'Disertai pendanaan riset dan publikasi internasional.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa Kesehatan Peduli',
            'institution' => 'Rumah Sakit Pendidikan',
            'level' => 's1',
            'location' => 'Indonesia',
            'scope' => 'dalam_negeri',
            'sktm_require' => true,
            'minimum_value' => 3.30,
            'maximum_age' => 26,
            'closing_date' => '-5 days',
            'estimated_quota' => 25,
            'link' => 'https://example.com/kesehatan-peduli',
            'note' => 'Sudah ditutup untuk periode ini.',
            'status' => 'closed',
        ],
        [
            'scholarship_name' => 'Beasiswa Seni Kreatif',
            'institution' => 'Institut Seni Indonesia',
            'level' => 's1',
            'location' => 'Indonesia',
            'scope' => 'dalam_negeri',
            'sktm_require' => false,
            'minimum_value' => null,
            'maximum_age' => 27,
            'closing_date' => '+75 days',
            'estimated_quota' => 35,
            'link' => 'https://example.com/seni-kreatif',
            'note' => 'Tidak ada batas IPK minimum, wajib melampirkan portofolio.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa Sosial Inklusif',
            'institution' => 'Yayasan Kemanusiaan',
            'level' => 's1',
            'location' => 'Indonesia',
            'scope' => 'dalam_negeri',
            'sktm_require' => true,
            'minimum_value' => 2.80,
            'maximum_age' => null,
            'closing_date' => '+55 days',
            'estimated_quota' => 80,
            'link' => 'https://example.com/sosial-inklusif',
            'note' => 'Tidak ada batas usia maksimal.',
            'status' => 'active',
        ],
        [
            'scholarship_name' => 'Beasiswa Agro Masa Depan',
            'institution' => 'Konsorsium Agribisnis',
            'level' => 'd4',
            'location' => 'Bogor',
            'scope' => 'dalam_negeri',
            'sktm_require' => false,
            'minimum_value' => 3.10,
            'maximum_age' => 25,
            'closing_date' => '+38 days',
            'estimated_quota' => 45,
            'link' => 'https://example.com/agro-masa-depan',
            'note' => 'Draft internal, belum dipublikasikan.',
            'status' => 'draft',
        ],
    ];

    /**
     * Field of study assigned to each demo scholarship so cluster matching is demonstrable.
     *
     * @var array<string, string>
     */
    public const CLUSTER_BY_SCHOLARSHIP = [
        'Beasiswa Talenta Digital' => 'teknologi',
        'Beasiswa Vokasi Terampil' => 'engineering',
        'Beasiswa Diploma Unggul' => 'engineering',
        'Beasiswa Seni Kreatif' => 'seni',
        'Beasiswa Kesehatan Peduli' => 'kesehatan',
        'Beasiswa Sosial Inklusif' => 'sosial',
        'Beasiswa Agro Masa Depan' => 'pertanian',
        'Beasiswa Pemerintah Daerah Cendekia' => 'pendidikan',
        'Global Futures Scholarship' => 'bisnis',
        'Asia Pacific Leader Award' => 'bisnis',
    ];

    /**
     * Get the demo scholarships with an explicit shape.
     *
     * @return array<int, array{scholarship_name: string, institution: string, level: string, location: string, scope: string, sktm_require: bool, minimum_value: float|null, maximum_age: int|null, closing_date: string|null, estimated_quota: int|null, link: string, note: string|null, status: string}>
     */
    public static function scholarships(): array
    {
        return self::SCHOLARSHIPS;
    }

    /**
     * Seed the application's scholarships.
     */
    public function run(): void
    {
        foreach (self::scholarships() as $scholarship) {
            Scholarship::query()->updateOrCreate(
                ['scholarship_name' => $scholarship['scholarship_name']],
                [
                    'institution' => $scholarship['institution'],
                    'level' => EducationLevel::from($scholarship['level']),
                    'location' => $scholarship['location'],
                    'scope' => LocationScope::from($scholarship['scope']),
                    'sktm_require' => $scholarship['sktm_require'],
                    'minimum_value' => $scholarship['minimum_value'],
                    'maximum_age' => $scholarship['maximum_age'],
                    'opening_date' => now()->subDays(30),
                    'closing_date' => $scholarship['closing_date'] === null
                        ? null
                        : now()->modify($scholarship['closing_date']),
                    'estimated_quota' => $scholarship['estimated_quota'],
                    'link' => $scholarship['link'],
                    'verification_date' => now()->addDays(140),
                    'note' => $scholarship['note'],
                    'status' => ScholarshipStatus::from($scholarship['status']),
                    'cluster_id' => $this->clusterIdFor($scholarship['scholarship_name']),
                ],
            );
        }
    }

    /**
     * Resolve the demo cluster for a scholarship name.
     */
    private function clusterIdFor(string $scholarship_name): ?int
    {
        $cluster_name = self::CLUSTER_BY_SCHOLARSHIP[$scholarship_name] ?? null;

        if ($cluster_name === null) {
            return null;
        }

        $cluster_id = Cluster::query()->where('name', $cluster_name)->value('id');

        return is_numeric($cluster_id) ? (int) $cluster_id : null;
    }
}
