<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\ModuleCategory;
use App\Enums\ModuleType;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\QuickQuestion;
use App\Models\Scholarship;
use App\Models\TwibbonTemplate;
use GdImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LearningSeeder extends Seeder
{
    /**
     * The learning curriculum, including the AI knowledge used for feedback.
     */
    public const CURRICULUM = [
        [
            'title' => 'Fase 1 — Fondasi Diri',
            'subtitle' => 'Kenali dirimu sebelum melamar',
            'fase_order' => 1,
            'short_description' => 'Bangun pemahaman tentang tujuan, kekuatan, dan cerita dirimu.',
            'modules' => [
                [
                    'title' => 'Mengenal Tujuan dan Motivasi Beasiswa',
                    'modul_order' => 1,
                    'type' => 'reading',
                    'duration' => 25,
                    'goal' => 'Mentee mampu merumuskan motivasi utama melamar beasiswa.',
                    'summary_material' => 'Beasiswa bukan sekadar bantuan dana. Reviewer mencari kandidat dengan motivasi yang jelas, tujuan yang terukur, dan kontribusi yang berdampak.',
                    'insights' => [
                        'Motivasi generik seperti "ingin membanggakan orang tua" kurang kuat.',
                        'Tujuan belajar harus spesifik dan dapat diukur.',
                        'Hubungkan tujuanmu dengan dampak bagi orang lain.',
                    ],
                    'inti_material' => 'Motivasi yang kuat memiliki tiga lapis: alasan personal, tujuan belajar, dan kontribusi yang ingin kamu berikan. Tuliskan ketiganya dalam satu paragraf singkat sebelum menyusun esai.',
                    'ai_knowledge' => 'Kriteria penilaian: (1) kejelasan alasan personal, (2) tujuan belajar yang terukur, (3) kaitan dengan kontribusi sosial, (4) tidak memakai klise dan tidak menyalin template esai umum.',
                    'quick_questions' => [
                        [
                            'question' => 'Motivasi yang kuat cukup menjelaskan bahwa kita butuh uang untuk kuliah.',
                            'answer' => false,
                            'explanation' => 'Kebutuhan dana bukan motivasi utama yang dinilai reviewer. Tunjukkan tujuan belajar dan kontribusi.',
                        ],
                        [
                            'question' => 'Tujuan belajar sebaiknya spesifik dan dapat diukur.',
                            'answer' => true,
                            'explanation' => 'Tujuan yang spesifik menunjukkan kematangan perencanaan belajar.',
                        ],
                    ],
                    'practice_form' => [
                        'title' => 'Rumusan Motivasi Beasiswa',
                        'description' => 'Tuliskan motivasi, tujuan belajar, dan kontribusi yang ingin kamu berikan.',
                    ],
                ],
                [
                    'title' => 'Menyusun Personal Branding',
                    'modul_order' => 2,
                    'type' => 'mixed',
                    'duration' => 30,
                    'goal' => 'Mentee mampu mendeskripsikan kekuatan dirinya secara ringkas dan meyakinkan.',
                    'summary_material' => 'Personal branding adalah cara kamu menjelaskan nilai dirimu dalam 30 detik. Susun berdasarkan bukti, bukan klaim.',
                    'insights' => [
                        'Gunakan kata kerja dan hasil, bukan kata sifat.',
                        'Kuantifikasi pencapaian bila memungkinkan.',
                        'Konsisten antara CV, esai, dan wawancara.',
                    ],
                    'inti_material' => 'Mulai dari tiga pencapaian yang paling kamu banggakan, lalu tarik satu benang merah kompetensi dari ketiganya. Itulah inti personal branding-mu.',
                    'ai_knowledge' => 'Nilai jawaban berdasarkan: penggunaan bukti konkret, ada kuantifikasi pencapaian, dan konsistensi antara kekuatan yang diklaim dan contoh yang diberikan.',
                    'quick_questions' => [
                        [
                            'question' => 'Personal branding sebaiknya memakai kata sifat seperti "saya orang yang rajin".',
                            'answer' => false,
                            'explanation' => 'Lebih kuat bila memakai bukti dan hasil nyata.',
                        ],
                    ],
                    'practice_form' => [
                        'title' => 'Pengalaman Organisasi',
                        'description' => 'Ceritakan pengalaman organisasi atau kepemimpinanmu. Data ini juga otomatis muncul di halaman CV.',
                        'include_in_cv' => true,
                        'cv_ordering' => 1,
                        'fields' => [
                            [
                                'name' => 'peran',
                                'label' => 'Peran / Jabatan',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'Contoh: Ketua BEM 2025',
                                'help_text' => 'Tuliskan jabatan dan periode.',
                                'options' => [],
                            ],
                            [
                                'name' => 'deskripsi',
                                'label' => 'Deskripsi Singkat',
                                'type' => 'textarea',
                                'required' => true,
                                'placeholder' => 'Apa tanggung jawab dan pencapaianmu?',
                                'help_text' => 'Fokus pada kontribusi dan hasil.',
                                'options' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Fase 2 — Menulis Dokumen',
            'subtitle' => 'Susun esai dan CV yang meyakinkan',
            'fase_order' => 2,
            'short_description' => 'Kuasi struktur esai motivasi dan penyusunan CV yang rapi.',
            'modules' => [
                [
                    'title' => 'Anatomi Esai Motivasi',
                    'modul_order' => 1,
                    'type' => 'reading',
                    'duration' => 35,
                    'goal' => 'Mentee mampu menulis esai dengan struktur pembuka, isi, dan penutup yang jelas.',
                    'summary_material' => 'Esai yang kuat mengikuti alur: pembuka yang personal, isi berbasis bukti, dan penutup yang menyambungkan tujuan dengan beasiswa.',
                    'insights' => [
                        'Satu paragraf satu ide utama.',
                        'Hindari kalimat pembuka yang terlalu umum.',
                        'Tutup dengan rencana konkret setelah lulus.',
                    ],
                    'inti_material' => 'Gunakan kerangka 4 paragraf: (1) momen yang memicu minat, (2) bukti usaha selama ini, (3) rencana belajar, dan (4) kontribusi setelah lulus.',
                    'ai_knowledge' => 'Nilai esai berdasarkan struktur paragraf, kekhususan cerita, bukti pendukung, dan keterkaitan penutup dengan program beasiswa. Beri catatan bila ada bagian klise atau tidak spesifik.',
                    'quick_questions' => [
                        [
                            'question' => 'Satu paragraf sebaiknya memuat beberapa ide utama sekaligus.',
                            'answer' => false,
                            'explanation' => 'Satu paragraf satu ide utama agar esai mudah diikuti.',
                        ],
                        [
                            'question' => 'Penutup esai sebaiknya menjelaskan rencana setelah lulus.',
                            'answer' => true,
                            'explanation' => 'Rencana konkret menunjukkan keseriusan dan dampak jangka panjang.',
                        ],
                    ],
                    'practice_form' => [
                        'title' => 'Draft Esai Motivasi',
                        'description' => 'Tulis draft esai motivasi 4 paragraf.',
                    ],
                ],
                [
                    'title' => 'Menyusun CV yang ATS-Friendly',
                    'modul_order' => 2,
                    'type' => 'reading',
                    'duration' => 30,
                    'goal' => 'Mentee mampu menyusun CV ringkas yang mudah dibaca sistem dan manusia.',
                    'summary_material' => 'CV yang baik satu sampai dua halaman, memakai format sederhana, dan menempatkan pencapaian di depan.',
                    'insights' => [
                        'Hindari grafik dan kolom bertingkat untuk sistem ATS.',
                        'Tulis pencapaian dengan pola aksi, konteks, dan hasil.',
                        'Urutkan pengalaman dari yang terbaru.',
                    ],
                    'inti_material' => 'Gunakan pola: Kata kerja aksi + konteks/tugas + hasil terukur. Contoh: "Memimpin 5 divisi dan meningkatkan partisipasi anggota 40%".',
                    'ai_knowledge' => 'Nilai CV berdasarkan kelengkapan bagian, pola aksi-konteks-hasil, keterbacaan ATS (tanpa kolom rumit), dan konsistensi format tanggal.',
                    'quick_questions' => [
                        [
                            'question' => 'Pengalaman sebaiknya diurutkan dari yang paling lama ke yang terbaru.',
                            'answer' => false,
                            'explanation' => 'Urutkan dari yang paling baru.',
                        ],
                    ],
                    'practice_form' => [
                        'title' => 'Prestasi dan Penghargaan',
                        'description' => 'Catat prestasi dan penghargaan yang relevan. Data ini otomatis masuk ke halaman CV.',
                        'include_in_cv' => true,
                        'cv_ordering' => 2,
                        'fields' => [
                            [
                                'name' => 'nama_prestasi',
                                'label' => 'Nama Prestasi',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'Contoh: Juara 2 Lomba Karya Tulis Nasional',
                                'help_text' => 'Sertakan tingkat dan penyelenggara bila ada.',
                                'options' => [],
                            ],
                            [
                                'name' => 'tahun',
                                'label' => 'Tahun',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => '2025',
                                'help_text' => null,
                                'options' => [],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Video: Memulai Cerita Motivasi',
                    'modul_order' => 3,
                    'type' => 'video',
                    'duration' => 15,
                    'goal' => 'Mentee memahami cara membangun cerita motivasi yang meyakinkan lewat contoh video.',
                    'summary_material' => 'Video ini membahas bagaimana minat yang konsisten dan kegigihan membentuk cerita motivasi yang kuat.',
                    'insights' => [
                        'Perhatikan bagaimana pembicara menghubungkan pengalaman pribadi dengan tujuan besar.',
                        'Catat dua gagasan yang bisa kamu adaptasi, bukan kalimat yang disalin.',
                        'Tulis satu paragraf reaksi setelah menonton.',
                    ],
                    'inti_material' => 'Setelah menonton, tuliskan satu momen yang paling relevan dengan hidupmu. Gunakan momen itu sebagai pembuka esai motivasi.',
                    'link_video' => 'https://www.youtube.com/embed/H14bBuluwB8',
                    'ai_knowledge' => 'Fokus bantuan: cara mengadaptasi pesan video menjadi cerita personal. Ingatkan mentee untuk tidak menyalin kalimat pembicara.',
                    'quick_questions' => [
                        [
                            'question' => 'Video contoh boleh disalin langsung ke dalam esai.',
                            'answer' => false,
                            'explanation' => 'Gunakan video sebagai inspirasi, bukan untuk disalin mentah-mentah.',
                        ],
                    ],
                    'practice_form' => [
                        'title' => 'Refleksi Video',
                        'description' => 'Tuliskan bagian video yang paling relevan dan rencana memakainya di esai.',
                    ],
                ],
            ],
        ],
        [
            'title' => 'Fase 3 — Persiapan Pendaftaran',
            'subtitle' => 'Lengkapi berkas dan latih wawancara',
            'fase_order' => 3,
            'short_description' => 'Persiapan teknis pendaftaran sampai simulasi wawancara.',
            'modules' => [
                [
                    'title' => 'Checklist Dokumen Pendaftaran',
                    'modul_order' => 1,
                    'type' => 'reading',
                    'duration' => 20,
                    'goal' => 'Mentee memiliki daftar dokumen lengkap sebelum mendaftar.',
                    'summary_material' => 'Sebagian besar kegagalan pendaftaran terjadi karena dokumen tidak lengkap atau tidak sesuai format.',
                    'insights' => [
                        'Baca panduan pendaftaran dua kali sebelum mengunggah.',
                        'Beri nama berkas dengan format konsisten.',
                        'Simpan salinan digital semua dokumen.',
                    ],
                    'inti_material' => 'Buat tabel dokumen berisi nama berkas, format, ukuran maksimum, dan status. Perbarui status setiap kali dokumen selesai.',
                    'ai_knowledge' => 'Nilai jawaban berdasarkan kelengkapan item dokumen, kesesuaian format, dan penamaan berkas yang konsisten.',
                    'quick_questions' => [
                        [
                            'question' => 'Dokumen pendaftaran cukup diperiksa sekali saja.',
                            'answer' => false,
                            'explanation' => 'Periksa ulang terhadap panduan sebelum mengirim.',
                        ],
                    ],
                    'practice_form' => [
                        'title' => 'Checklist Dokumen',
                        'description' => 'Isi status setiap dokumen pendaftaranmu.',
                    ],
                ],
                [
                    'title' => 'Simulasi Wawancara Beasiswa',
                    'modul_order' => 2,
                    'type' => 'practice',
                    'duration' => 40,
                    'goal' => 'Mentee mampu menjawab pertanyaan wawancara dengan struktur STAR.',
                    'summary_material' => 'Wawancara beasiswa menilai kejelasan berpikir, ketulusan motivasi, dan kesiapan rencana belajar.',
                    'insights' => [
                        'Gunakan struktur Situation, Task, Action, Result.',
                        'Sebutkan peranmu secara jelas, hindari "kami" saja.',
                        'Siapkan dua contoh untuk setiap kompetensi utama.',
                    ],
                    'inti_material' => 'Latih tiga pertanyaan tersering: ceritakan dirimu, mengapa memilih program ini, dan apa rencanamu setelah lulus.',
                    'ai_knowledge' => 'Nilai jawaban wawancara berdasarkan penerapan struktur STAR, kejelasan peran personal, relevansi contoh, dan kecocokan dengan program beasiswa.',
                    'quick_questions' => [
                        [
                            'question' => 'Struktur STAR berarti Situation, Task, Action, Result.',
                            'answer' => true,
                            'explanation' => 'Struktur ini membantu jawaban tetap fokus dan terukur.',
                        ],
                    ],
                    'practice_form' => [
                        'title' => 'Latihan Jawaban Wawancara',
                        'description' => 'Jawab pertanyaan wawancara berikut menggunakan struktur STAR.',
                        'fields' => [
                            [
                                'name' => 'pertanyaan',
                                'label' => 'Pertanyaan',
                                'type' => 'select',
                                'required' => true,
                                'placeholder' => null,
                                'help_text' => 'Pilih satu pertanyaan untuk dilatih.',
                                'options' => [
                                    'Ceritakan tentang dirimu',
                                    'Mengapa memilih program ini',
                                    'Apa rencanamu setelah lulus',
                                ],
                            ],
                            [
                                'name' => 'jawaban',
                                'label' => 'Jawabanmu (struktur STAR)',
                                'type' => 'textarea',
                                'required' => true,
                                'placeholder' => 'Situation: ... Task: ... Action: ... Result: ...',
                                'help_text' => 'Maksimal 300 kata.',
                                'options' => [],
                            ],
                            [
                                'name' => 'tingkat_kesiapan',
                                'label' => 'Tingkat Kesiapan',
                                'type' => 'radio',
                                'required' => true,
                                'placeholder' => null,
                                'help_text' => 'Sejujur mungkin, ini hanya untukmu.',
                                'options' => ['Belum siap', 'Cukup siap', 'Sangat siap'],
                            ],
                        ],
                    ],
                ],
            ],
        ],

        // Perpustakaan: pelatihan tambahan lintas beasiswa, tidak masuk roadmap utama.
        [
            'title' => 'Perpustakaan Skular',
            'subtitle' => 'Pelatihan tambahan lintas beasiswa',
            'fase_order' => 4,
            'short_description' => 'Materi pendukung seperti persiapan bahasa dan penulisan akademik yang bisa diakses kapan saja.',
            'modules' => [
                [
                    'title' => 'Persiapan TOEFL',
                    'modul_order' => 1,
                    'type' => 'reading',
                    'category' => 'perpustakaan',
                    'duration' => 45,
                    'goal' => 'Mentee memahami struktur tes TOEFL dan strategi latihan harian.',
                    'summary_material' => 'TOEFL menguji listening, structure, reading, dan writing. Kunci suksesnya adalah latihan rutin dengan pola soal yang konsisten.',
                    'insights' => [
                        'Mulai dari diagnosa skor untuk tahu titik lemahmu.',
                        'Latih listening 15 menit setiap hari.',
                        'Biasakan membaca teks akademik berbahasa Inggris.',
                    ],
                    'inti_material' => 'Bagi belajar menjadi tiga blok: listening, structure & written expression, dan reading comprehension. Catat pola kesalahan untuk dievaluasi setiap pekan.',
                    'ai_knowledge' => 'Fokus bantuan: strategi menjawab TOEFL, manajemen waktu, dan latihan mandiri. Jangan mengklaim skor pasti.',
                    'practice_form' => [
                        'title' => 'Rencana Belajar Bahasa Inggris',
                        'description' => 'Susun rencana latihan bahasa Inggris mingguanmu.',
                    ],
                ],
                [
                    'title' => 'Academic Writing',
                    'modul_order' => 2,
                    'type' => 'mixed',
                    'category' => 'perpustakaan',
                    'duration' => 40,
                    'goal' => 'Mentee mampu menulis paragraf akademik yang runtut dan didukung bukti.',
                    'summary_material' => 'Tulisan akademik menuntut tesis yang jelas, argumen terstruktur, dan dukungan bukti atau data.',
                    'insights' => [
                        'Satu paragraf, satu gagasan utama.',
                        'Gunakan bukti atau contoh konkret.',
                        'Hindari kalimat bertele-tele.',
                    ],
                    'inti_material' => 'Gunakan struktur tesis — dukungan — kesimpulan. Setiap paragraf dibuka dengan kalimat topik, lalu didukung bukti, dan ditutup dengan kaitan ke tesis.',
                    'ai_knowledge' => 'Fokus bantuan: kejelasan tesis, koherensi paragraf, penggunaan bukti, dan gaya bahasa formal.',
                    'practice_form' => [
                        'title' => 'Latihan Paragraf Akademik',
                        'description' => 'Tulis satu paragraf akademik dengan tesis dan bukti.',
                    ],
                ],
            ],
        ],
    ];

    /**
     * Scholarship-specific modules seeded for every demo scholarship.
     *
     * The `:beasiswa` and `:institusi` placeholders are replaced with the
     * scholarship name and institution, so each scholarship gets its own
     * tailored copy of the module instead of generic shared content.
     *
     * @var array<int, array{fase_order: int, title: string, type: string, modul_order: int, duration: int, goal: string, introduction: string, summary_material: string, insights: array<int, string>, inti_material: string, ai_knowledge: string, quick_questions: array<int, array{question: string, answer: bool, explanation: string}>, practice_form: array{title: string, description: string, fields: array<int, array{name: string, label: string, type: string, required: bool, placeholder: string|null, help_text: string|null, options: array<int, string>}>}}>
     */
    public const TEMPLATE_MODULES = [
        [
            'fase_order' => 2,
            'title' => 'Esai Khusus :beasiswa',
            'type' => 'mixed',
            'modul_order' => 4,
            'duration' => 30,
            'goal' => 'Mentee mampu menyusun esai yang menjawab kriteria :beasiswa.',
            'introduction' => 'Modul ini adalah versi khusus esai motivasi untuk :beasiswa. Baca kembali panduan resmi dari :institusi sebelum mulai menulis.',
            'summary_material' => ':beasiswa menilai kesesuaian motivasi, rencana belajar, dan kontribusi dengan program yang ditawarkan. Esai yang kuat menjawab ketiganya secara eksplisit.',
            'insights' => [
                'Sebutkan nama program dan alasan memilihnya di paragraf pembuka.',
                'Hubungkan pengalamanmu dengan nilai yang dicari :beasiswa.',
                'Tutup dengan kontribusi konkret setelah lulus.',
            ],
            'inti_material' => 'Mulai dari satu kalimat tesis yang menghubungkan pengalamanmu dengan tujuan :beasiswa. Kembangkan tiga bukti pendukung, lalu akhiri dengan rencana studi dan kontribusi yang terukur.',
            'ai_knowledge' => 'Nilai esai berdasarkan kesesuaian dengan kriteria :beasiswa: kejelasan tesis, bukti pendukung, dan kontribusi setelah lulus. Tandai bagian yang masih generik atau tidak spesifik.',
            'quick_questions' => [
                [
                    'question' => 'Esai khusus :beasiswa sebaiknya menyebut nama program secara eksplisit.',
                    'answer' => true,
                    'explanation' => 'Menyebut program secara eksplisit menunjukkan esai tidak memakai template umum.',
                ],
                [
                    'question' => 'Cukup menjelaskan bahwa :beasiswa adalah satu-satunya beasiswa yang masih buka.',
                    'answer' => false,
                    'explanation' => 'Alasan administratif tidak menunjukkan motivasi dan kesesuaian dengan program.',
                ],
            ],
            'practice_form' => [
                'title' => 'Draft Esai :beasiswa',
                'description' => 'Tulis draft esai motivasi khusus untuk :beasiswa.',
                'fields' => [
                    [
                        'name' => 'nilai_yang_dicari',
                        'label' => 'Nilai yang dicari :beasiswa',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Contoh: kepemimpinan, dampak sosial, dan kemandirian.',
                        'help_text' => 'Ringkas dari panduan resmi penyelenggara.',
                        'options' => [],
                    ],
                    [
                        'name' => 'draft_esai',
                        'label' => 'Draft Esai',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tulis esai empat paragraf di sini...',
                        'help_text' => 'Maksimal 500 kata.',
                        'options' => [],
                    ],
                ],
            ],
        ],
        [
            'fase_order' => 3,
            'title' => 'Berkas dan Seleksi :beasiswa',
            'type' => 'practice',
            'modul_order' => 3,
            'duration' => 35,
            'goal' => 'Mentee menyiapkan berkas dan latihan seleksi sesuai ketentuan :beasiswa.',
            'introduction' => 'Setiap beasiswa punya format berkas dan tahapan seleksi sendiri. Modul ini mengumpulkan persiapan khusus :beasiswa dalam satu tempat.',
            'summary_material' => ':beasiswa biasanya meminta dokumen tambahan seperti surat rekomendasi, esai, atau portofolio. Lengkapi semuanya sebelum tenggat dari :institusi.',
            'insights' => [
                'Baca panduan berkas :beasiswa baris per baris.',
                'Minta surat rekomendasi minimal dua minggu sebelum tenggat.',
                'Latih pertanyaan seleksi yang khas untuk program ini.',
            ],
            'inti_material' => 'Susun daftar berkas khusus :beasiswa, tandai mana yang sudah siap, dan jadwalkan pengiriman lebih awal. Setelah berkas lengkap, latih dua pertanyaan seleksi yang paling sering muncul.',
            'ai_knowledge' => 'Fokus bantuan: kelengkapan berkas khusus :beasiswa, kesesuaian format, dan kesiapan jawaban seleksi. Jangan mengarang persyaratan yang tidak disebutkan mentee.',
            'quick_questions' => [
                [
                    'question' => 'Berkas khusus :beasiswa sebaiknya disiapkan setelah pengumuman seleksi.',
                    'answer' => false,
                    'explanation' => 'Berkas harus lengkap sebelum tenggat pendaftaran, bukan setelah seleksi.',
                ],
            ],
            'practice_form' => [
                'title' => 'Checklist Berkas :beasiswa',
                'description' => 'Catat status setiap berkas khusus yang diminta :beasiswa.',
                'fields' => [
                    [
                        'name' => 'berkas',
                        'label' => 'Nama berkas',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'Contoh: Surat rekomendasi dosen',
                        'help_text' => 'Tulis satu berkas per jawaban.',
                        'options' => [],
                    ],
                    [
                        'name' => 'status',
                        'label' => 'Status',
                        'type' => 'select',
                        'required' => true,
                        'placeholder' => null,
                        'help_text' => null,
                        'options' => ['Belum dimulai', 'Sedang diproses', 'Selesai'],
                    ],
                ],
            ],
        ],
    ];

    /**
     * Get the curriculum with an explicit shape so optional module sections stay visible.
     *
     * @return array<int, array{title: string, subtitle: string, fase_order: int, short_description: string, modules: array<int, array{title: string, modul_order: int, type: string, category?: string, duration: int, goal: string, summary_material: string, insights: array<int, string>, inti_material: string, ai_knowledge: string, link_video?: string, quick_questions?: array<int, array{question: string, answer: bool, explanation: string}>, practice_form?: array{title: string, description: string, include_in_cv?: bool, cv_ordering?: int, fields?: array<int, array{name: string, label: string, type: string, required: bool, placeholder: string|null, help_text: string|null, options: array<int, string>}>}}>}>
     */
    public static function curriculum(): array
    {
        return self::CURRICULUM;
    }

    /**
     * Seed the learning curriculum and the default twibbon template.
     */
    public function run(): void
    {
        foreach (self::curriculum() as $fase_data) {
            $fase = Fase::query()->updateOrCreate(
                ['title' => $fase_data['title']],
                [
                    'subtitle' => $fase_data['subtitle'],
                    'fase_order' => $fase_data['fase_order'],
                    'short_description' => $fase_data['short_description'],
                ],
            );

            foreach ($fase_data['modules'] as $module_data) {
                $module = Module::query()->updateOrCreate(
                    ['fase_id' => $fase->id, 'title' => $module_data['title']],
                    [
                        'modul_order' => $module_data['modul_order'],
                        'type' => ModuleType::from($module_data['type']),
                        // Seeded curriculum is intended to be live for mentees.
                        'content_status' => ContentStatus::Published,
                        'category' => ModuleCategory::from($module_data['category'] ?? ModuleCategory::Inti->value),
                        'duration' => $module_data['duration'],
                        'goal' => $module_data['goal'],
                        'summary_material' => $module_data['summary_material'],
                        'insights' => $module_data['insights'],
                        'inti_material' => $module_data['inti_material'],
                        'link_video' => $module_data['link_video'] ?? null,
                        'ai_knowledge' => $module_data['ai_knowledge'],
                    ],
                );

                $this->seedQuickQuestions($module, $module_data['quick_questions'] ?? []);
                $this->seedPracticeForm($module, $module_data['practice_form'] ?? null);
            }
        }

        $this->seedTemplateModules();
        $this->seedDefaultTwibbonTemplate();
    }

    /**
     * Seed the scholarship-tailored Template modules.
     *
     * Each demo scholarship receives its own copy of every template module so
     * the tailored curriculum can be demonstrated for any of them.
     */
    private function seedTemplateModules(): void
    {
        $scholarships = Scholarship::query()->orderBy('id')->get();

        foreach ($scholarships as $scholarship) {
            foreach (self::TEMPLATE_MODULES as $module_data) {
                $fase = Fase::query()->where('fase_order', $module_data['fase_order'])->first();

                if ($fase === null) {
                    continue;
                }

                $module = Module::query()->updateOrCreate(
                    [
                        'scholarship_id' => $scholarship->id,
                        'title' => $this->templateText($module_data['title'], $scholarship),
                    ],
                    [
                        'fase_id' => $fase->id,
                        'modul_order' => $module_data['modul_order'],
                        'type' => ModuleType::from($module_data['type']),
                        'content_status' => ContentStatus::Published,
                        'category' => ModuleCategory::Template,
                        'duration' => $module_data['duration'],
                        'goal' => $this->templateText($module_data['goal'], $scholarship),
                        'introduction' => $this->templateText($module_data['introduction'], $scholarship),
                        'summary_material' => $this->templateText($module_data['summary_material'], $scholarship),
                        'insights' => array_map(
                            fn (string $insight): string => $this->templateText($insight, $scholarship),
                            $module_data['insights'],
                        ),
                        'inti_material' => $this->templateText($module_data['inti_material'], $scholarship),
                        'ai_knowledge' => $this->templateText($module_data['ai_knowledge'], $scholarship),
                    ],
                );

                $this->seedQuickQuestions($module, array_map(
                    fn (array $question): array => [
                        'question' => $this->templateText($question['question'], $scholarship),
                        'answer' => $question['answer'],
                        'explanation' => $this->templateText($question['explanation'], $scholarship),
                    ],
                    $module_data['quick_questions'],
                ));

                $this->seedPracticeForm($module, [
                    'title' => $this->templateText($module_data['practice_form']['title'], $scholarship),
                    'description' => $this->templateText($module_data['practice_form']['description'], $scholarship),
                    'fields' => $module_data['practice_form']['fields'],
                ]);
            }
        }
    }

    /**
     * Replace the scholarship placeholders used by the template modules.
     */
    private function templateText(string $text, Scholarship $scholarship): string
    {
        return strtr($text, [
            ':beasiswa' => $scholarship->scholarship_name,
            ':institusi' => $scholarship->institution ?? $scholarship->scholarship_name,
        ]);
    }

    /**
     * Seed the answerable quick questions of a module.
     *
     * @param  array<int, array{question: string, answer: bool, explanation: string}>  $questions
     */
    private function seedQuickQuestions(Module $module, array $questions): void
    {
        foreach ($questions as $index => $question_data) {
            QuickQuestion::query()->updateOrCreate(
                ['module_id' => $module->id, 'question' => $question_data['question']],
                [
                    'answer' => $question_data['answer'],
                    'explanation' => $question_data['explanation'],
                    'ordering' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Seed the written exercise of a module.
     *
     * @param  array{title: string, description: string, include_in_cv?: bool, cv_ordering?: int, fields?: array<int, array{name: string, label: string, type: string, required: bool, placeholder: string|null, help_text: string|null, options: array<int, string>}>}|null  $form_data
     */
    private function seedPracticeForm(Module $module, ?array $form_data): void
    {
        if ($form_data === null) {
            return;
        }

        PracticeForm::query()->updateOrCreate(
            ['module_id' => $module->id, 'title' => $form_data['title']],
            [
                'description' => $form_data['description'],
                'fields' => $form_data['fields'] ?? [
                    [
                        'name' => 'jawaban',
                        'label' => 'Jawabanmu',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tulis jawabanmu di sini...',
                        'help_text' => 'Gunakan bahasa yang jelas dan runtut.',
                        'options' => [],
                    ],
                ],
                'ordering' => 1,
                'is_active' => true,
                'include_in_cv' => $form_data['include_in_cv'] ?? false,
                'cv_ordering' => $form_data['cv_ordering'] ?? 0,
            ],
        );
    }

    /**
     * Ensure a default twibbon template image exists so the feature works out of the box.
     */
    private function seedDefaultTwibbonTemplate(): void
    {
        $path = 'twibbon/templates/default.png';

        $template = TwibbonTemplate::query()->updateOrCreate(
            ['name' => 'Template Kelulusan Skular'],
            [
                'description' => 'Template bawaan untuk merayakan kelulusan beasiswa.',
                'image_path' => $path,
                'font_path' => null,
                'placements' => TwibbonTemplate::DEFAULT_PLACEMENTS,
                'is_active' => true,
                'ordering' => 1,
            ],
        );

        if (Storage::disk('public')->exists($template->image_path)) {
            return;
        }

        Storage::disk('public')->put($template->image_path, $this->makeDefaultTwibbonImage());
    }

    /**
     * Render a simple 1200x1200 brand placeholder template with GD.
     */
    private function makeDefaultTwibbonImage(): string
    {
        $size = 1200;
        $canvas = imagecreatetruecolor($size, $size);

        // Vertical brand gradient from deep teal to warm amber.
        for ($y = 0; $y < $size; $y++) {
            $ratio = $y / ($size - 1);

            $colour = $this->allocateColour(
                $canvas,
                $this->channel(39, 242, $ratio),
                $this->channel(124, 201, $ratio),
                $this->channel(147, 76, $ratio),
            );

            imageline($canvas, 0, $y, $size, $y, $colour);
        }

        $white = $this->allocateColour($canvas, 255, 255, 255);
        $frame = $this->allocateColour($canvas, 255, 255, 255, 70);

        for ($offset = 24; $offset < 32; $offset++) {
            imagerectangle($canvas, $offset, $offset, $size - $offset, $size - $offset, $frame);
        }

        // Placeholder avatar circle so the composition reads as a twibbon.
        $centre = intdiv($size, 2);
        $avatar_y = (int) round($size * 0.36);

        imagefilledellipse($canvas, $centre, $avatar_y, 420, 420, $this->allocateColour($canvas, 255, 255, 255, 95));

        imagesetthickness($canvas, 8);
        imageellipse($canvas, $centre, $avatar_y, 420, 420, $this->allocateColour($canvas, 255, 255, 255, 30));

        imageline($canvas, (int) round($size * 0.28), (int) round($size * 0.52), (int) round($size * 0.72), (int) round($size * 0.52), $white);

        ob_start();
        imagepng($canvas);
        $contents = ob_get_clean();
        imagedestroy($canvas);

        return $contents === false ? '' : $contents;
    }

    /**
     * Interpolate a single colour channel between two brand colours.
     *
     * @return int<0, 255>
     */
    private function channel(int $from, int $to, float $ratio): int
    {
        $value = (int) round($from + ($to - $from) * $ratio);

        return min(255, max(0, $value));
    }

    /**
     * Allocate a colour on the canvas, failing loudly when GD refuses.
     *
     * @param  int<0, 255>  $red
     * @param  int<0, 255>  $green
     * @param  int<0, 255>  $blue
     * @param  int<0, 127>  $alpha
     */
    private function allocateColour(GdImage $canvas, int $red, int $green, int $blue, int $alpha = 0): int
    {
        $colour = imagecolorallocatealpha($canvas, $red, $green, $blue, $alpha);

        if ($colour === false) {
            throw new RuntimeException('Unable to allocate a colour for the twibbon template.');
        }

        return $colour;
    }
}
