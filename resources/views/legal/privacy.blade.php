{{--
    Kebijakan Privasi (route legal.privacy). Every statement mirrors the code at
    the time of writing (data inventory in PROGRES-LEGAL.md / docs/database.md).
    Facts the owner has not decided come from config/legal.php as [ISI: ...].
    Raise legal.documents.privacy.version when this text changes.
--}}
@php
    $document = \App\Enums\LegalDocument::Privacy;
    $company = config('legal.company.name');
    $privacyEmail = config('legal.contact.privacy_email');
    $contactEmail = config('legal.contact.email');
    $sessionMinutes = (int) config('session.lifetime');
    $chatLimit = (int) config('ai.module_chat.limit');

    $summary = [
        __('Kami mengumpulkan data yang kamu isi sendiri (akun, profil, jawaban latihan, dokumen) dan sedikit data teknis.'),
        __('Jawaban latihan, pertanyaan ke AI Mentor, isi brainstorming, dan jawaban Writing Try Out dikirim ke penyedia AI (OpenRouter dan penyedia model di belakangnya, bisa di luar Indonesia) untuk dinilai atau dijawab. Jawaban Speaking Try Out diubah menjadi teks oleh fitur pengenalan suara browsermu; Skular hanya menerima teksnya. Data profilmu tidak ikut dikirim.'),
        __('Kami tidak menjual data pribadimu. Data hanya dibagikan dalam bentuk agregat atau anonim, kecuali kamu memberi persetujuan terpisah.'),
        __('Google Analytics hanya aktif kalau kamu mengizinkan lewat banner cookie.'),
        __('Pembayaran diproses Midtrans. Kami tidak menyimpan nomor kartu atau rekeningmu.'),
        __('Kalau kamu belum 18 tahun, orang tua atau walimu perlu mengizinkan kamu memakai Skular.'),
        __('Kamu bisa meminta akses, perbaikan, atau penghapusan data lewat :email.', ['email' => $privacyEmail]),
    ];

    $sections = [
        ['id' => 'pengendali', 'title' => __('Siapa kami')],
        ['id' => 'data', 'title' => __('Data yang kami kumpulkan')],
        ['id' => 'tujuan', 'title' => __('Untuk apa data dipakai dan dasar hukumnya')],
        ['id' => 'ai', 'title' => __('Data yang dikirim ke penyedia AI')],
        ['id' => 'penerima', 'title' => __('Pihak yang menerima data')],
        ['id' => 'luar-negeri', 'title' => __('Transfer data ke luar Indonesia')],
        ['id' => 'berbagi', 'title' => __('Kami tidak menjual data')],
        ['id' => 'anak', 'title' => __('Pengguna di bawah 18 tahun')],
        ['id' => 'masa-simpan', 'title' => __('Berapa lama data disimpan dan hapus akun')],
        ['id' => 'hak', 'title' => __('Hak kamu dan cara mengajukannya')],
        ['id' => 'keamanan', 'title' => __('Keamanan data')],
        ['id' => 'kebocoran', 'title' => __('Jika terjadi kebocoran data')],
        ['id' => 'persetujuan', 'title' => __('Catatan persetujuan')],
        ['id' => 'perubahan', 'title' => __('Perubahan kebijakan')],
        ['id' => 'kontak', 'title' => __('Kontak dan pengaduan')],
    ];
@endphp

<x-layouts::public :title="$document->label()" :description="__('Data apa yang Skular kumpulkan, untuk apa, siapa yang menerimanya, berapa lama disimpan, dan cara kamu memakai hak atas datamu.')">
    <x-legal.document :document="$document" :summary="$summary" :sections="$sections">
        <x-legal.section id="pengendali" number="1" :title="$sections[0]['title']">
            <p>{{ __('Skular (skularship.com) adalah platform persiapan beasiswa yang dikelola oleh :company, beralamat di :address. Dalam kebijakan ini, "Skular" atau "kami" berarti badan usaha tersebut, dan "kamu" berarti pengguna Skular.', ['company' => $company, 'address' => config('legal.company.address')]) }}</p>
            <p>{{ __('Untuk data yang kamu berikan di Skular, kami adalah pengendali data pribadi menurut Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP). Pertanyaan soal data pribadi bisa dikirim ke :email.', ['email' => $privacyEmail]) }}</p>
        </x-legal.section>

        <x-legal.section id="data" number="2" :title="$sections[1]['title']">
            <div class="sk-legal-table">
                <table>
                    <thead>
                        <tr><th>{{ __('Kelompok') }}</th><th>{{ __('Isinya') }}</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>{{ __('Akun') }}</td><td>{{ __('Nama, email, password (disimpan dalam bentuk hash, bukan teks asli), ID dan foto profil Google bila kamu masuk dengan Google, passkey, dan pengaturan verifikasi dua langkah.') }}</td></tr>
                        <tr><td>{{ __('Profil beasiswa') }}</td><td>{{ __('Usia, IPK atau nilai rata-rata, status SKTM, jenjang pendidikan sekarang dan tujuan, lokasi studi tujuan, bidang (klaster), dan nomor telepon bila kamu mengisinya.') }}</td></tr>
                        <tr><td>{{ __('Isi belajar') }}</td><td>{{ __('Jawaban latihan (termasuk esai dan cerita pribadi) dan file yang kamu lampirkan di latihan, sesi AI Brainstorming, progres modul, jawaban Quick Question, dan jumlah pemakaian AI Mentor. Percakapan AI Mentor tidak kami simpan di database; isinya hanya ada selama halaman modul terbuka.') }}</td></tr>
                        <tr><td>{{ __('Masukan') }}</td><td>{{ __('Persetujuanmu pada program akses gratis (dan waktunya), masukan di akhir setiap modul (skor seberapa membantu, bagian yang paling menyulitkan, jawaban pertanyaan terbuka, dan alasan bila skornya rendah), serta skor dan masukan setelah event.') }}</td></tr>
                        <tr><td>{{ __('Try Out') }}</td><td>{{ __('Jawaban Listening dan Reading, esai Writing, transkrip jawaban Speaking (teks dari browsermu, tanpa rekaman suara) dan lama kamu berbicara, skor dan level hasil Try Out, mode yang kamu pilih, waktu mengerjakan, dan jumlah pertanyaan ke mentor AI Try Out. Percakapan mentor AI Try Out tidak kami simpan di database.') }}</td></tr>
                        <tr><td>{{ __('Tes IQ Latihan') }}</td><td>{{ __('Jawaban pilihan gandamu (termasuk berapa kali kamu menggantinya), waktu mulai dan selesai, lama kamu berada di setiap soal, jumlah jawaban benar dan kategori skor latihan, serta jumlah pertanyaan ke mentor AI Tes IQ per hari. Tidak ada angka IQ yang dihitung atau disimpan. Percakapan mentor AI Tes IQ tidak kami simpan di database.') }}</td></tr>
                        <tr><td>{{ __('Event') }}</td><td>{{ __('Event yang kamu daftari, jawaban pertanyaan pendaftaran event, status kehadiran, dan catatan pengingat email yang sudah dikirim.') }}</td></tr>
                        <tr><td>{{ __('Aktivitas di Skular') }}</td><td>{{ __('Catatan langkah yang kamu lakukan di Skular, misalnya membuka halaman beasiswa, filter yang dipakai, menekan tombol cari beasiswa yang cocok, mulai dan menyelesaikan profiling atau modul, dan mendaftar event, beserta waktunya. Kata yang kamu ketik di kolom pencarian tidak dicatat. Catatan ini disimpan di server Skular sendiri, tanpa alamat IP dan tanpa layanan pihak ketiga.') }}</td></tr>
                        <tr><td>{{ __('Label akun') }}</td><td>{{ __('Label yang diberikan admin pada akunmu, misalnya alumni program tertentu, untuk mengundangmu ke event khusus dan memisahkan data masukan.') }}</td></tr>
                        <tr><td>{{ __('Dokumen') }}</td><td>{{ __('Bukti pendaftaran beasiswa yang kamu unggah, dan dokumen di Vault (khusus Pro: PDF, JPG, PNG, DOC, DOCX).') }}</td></tr>
                        <tr><td>{{ __('Beasiswa pilihan') }}</td><td>{{ __('Beasiswa yang kamu pilih (maksimal 5), status pendaftaranmu, dan checklist persiapan (Pro).') }}</td></tr>
                        <tr><td>{{ __('Pembayaran') }}</td><td>{{ __('Nomor pesanan (format SKL-...), paket, nominal, kode referral yang dipakai, status pembayaran, dan masa aktif Pro. Kami juga menyimpan notifikasi status pembayaran yang dikirim Midtrans. Nomor kartu, rekening, dan PIN kamu masukkan langsung di halaman Midtrans, bukan di Skular.') }}</td></tr>
                        <tr><td>{{ __('Bantuan dan laporan') }}</td><td>{{ __('Isi tiket bantuan, screenshot yang kamu lampirkan, informasi browser (user agent) saat tiket dikirim, dan laporan info beasiswa yang kamu kirim.') }}</td></tr>
                        <tr><td>{{ __('Teknis') }}</td><td>{{ __('Data sesi login (alamat IP dan informasi browser, disimpan selama sesi berlaku), log server untuk mencari error, dan, hanya bila kamu mengizinkan, data kunjungan dari Google Analytics.') }}</td></tr>
                        <tr><td>{{ __('Persetujuan') }}</td><td>{{ __('Dokumen dan versi yang kamu setujui, waktunya, dan dari mana (daftar, login Google, pembayaran, atau persetujuan ulang). Kami tidak menyimpan alamat IP untuk catatan ini.') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <p>{{ __('Menurut UU PDP, data anak dan data keuangan pribadi termasuk data pribadi yang bersifat spesifik. Karena itu kami hanya meminta yang perlu, dan kamu sebaiknya tidak menulis data yang tidak diminta.') }}</p>
        </x-legal.section>

        <x-legal.section id="tujuan" number="3" :title="$sections[2]['title']">
            <p>{{ __('UU PDP (Pasal 20) mengatur dasar pemrosesan data pribadi. Kami memakai dasar berikut:') }}</p>
            <ul>
                <li>{{ __('Pelaksanaan perjanjian: membuat dan menjalankan akunmu, memberi rekomendasi beasiswa dari profil, menampilkan roadmap modul, menilai latihan, menjalankan AI Mentor dan Brainstorming, mengelola pendaftaran event dan mengirim pengingatnya, memproses pembayaran Pro, mengirim email pengingat tenggat dan masa Pro, dan menjawab tiket bantuan. Masukan di akhir setiap modul adalah syarat program akses gratis yang kamu setujui sebelum membuka modul.') }}</li>
                <li>{{ __('Persetujuan: mengirim isi tulisanmu ke penyedia AI di luar Indonesia, Google Analytics, dan berbagi data yang bisa mengenali kamu kepada pihak lain (hanya bila kamu setuju secara terpisah).') }}</li>
                <li>{{ __('Kewajiban hukum: menyimpan catatan yang diwajibkan peraturan dan menanggapi permintaan aparat yang sah.') }}</li>
                <li>{{ __('Kepentingan yang sah: menjaga keamanan akun dan layanan, mencegah penyalahgunaan kuota AI dan kode referral, mencari penyebab error dari log, dan memperbaiki alur serta modul Skular dari catatan aktivitas dan masukan (dilihat tim dalam bentuk jumlah per langkah dan per modul).') }}</li>
            </ul>
            <p>{{ __('Rekomendasi beasiswa, batas 5 beasiswa, dan pembukaan modul dihitung dengan aturan tetap di sistem, bukan oleh AI. Penilaian latihan oleh AI hanya saran belajar dan tidak menentukan hal lain tentang akunmu.') }}</p>
        </x-legal.section>

        <x-legal.section id="ai" number="4" :title="$sections[3]['title']">
            <p>{{ __('Fitur AI Skular memakai OpenRouter, layanan yang meneruskan permintaan ke penyedia model AI. Yang dikirim:') }}</p>
            <ul>
                <li>{{ __('Penilaian latihan: jawaban yang kamu kirim di form latihan, beserta soal dan materi modulnya.') }}</li>
                <li>{{ __('AI Mentor: pertanyaanmu, beberapa pesan terakhir di percakapan itu, dan isi modul yang sedang kamu buka. Kuota pertanyaan ke AI saat ini :limit per modul.', ['limit' => $chatLimit]) }}</li>
                <li>{{ __('AI Brainstorming: jawaban yang kamu tulis di sesi brainstorming.') }}</li>
                <li>{{ __('Try Out Writing: esai yang kamu kirim, beserta soal dan panduan penilaiannya.') }}</li>
                <li>{{ __('Try Out Speaking: transkrip jawabanmu (teks, bukan rekaman suara) dikirim lewat OpenRouter untuk dinilai.') }}</li>
                <li>{{ __('Mentor AI Try Out (paket Pro): pertanyaanmu, beberapa pesan terakhir, soal yang kamu tanyakan beserta jawabanmu, kunci, dan pembahasannya, serta teks teori Try Out.') }}</li>
                <li>{{ __('Mentor AI Tes IQ (akses berbayar): pertanyaanmu, beberapa pesan terakhir, ringkasan skor latihanmu, soal yang kamu tanyakan beserta jawabanmu, kunci, dan pembahasannya, serta catatan materi Tes IQ dari tim Skular.') }}</li>
            </ul>
            <p>{{ __('Nama, email, dan data profil beasiswamu tidak ikut dikirim. Log Skular hanya mencatat data teknis permintaan AI (waktu, durasi, status), bukan isi jawabanmu. OpenRouter dan penyedia model memproses data sesuai kebijakan privasi mereka masing-masing.') }}</p>
            <p class="sk-legal-note">{{ __('Jangan menulis NIK, nomor rekening, password, data kesehatan, atau data pribadi orang lain di jawaban latihan atau chat AI. Tulis cerita pribadimu secukupnya untuk latihan.') }}</p>
        </x-legal.section>

        <x-legal.section id="penerima" number="5" :title="$sections[4]['title']">
            <ul>
                <li>{{ __('Midtrans: memproses pembayaran. Menerima nama, email, nomor pesanan, paket, dan nominal.') }}</li>
                <li>{{ __('Google: login dengan Google (bila kamu memilihnya) dan Google Analytics (hanya bila kamu mengizinkan).') }}</li>
                <li>{{ __('OpenRouter dan penyedia model AI: lihat bagian 4.') }}</li>
                <li>{{ __('Layanan pengenalan suara bawaan browsermu (Google untuk Chrome, Microsoft untuk Edge, Apple untuk Safari): saat kamu merekam jawaban Speaking Try Out, browsermu mengirim suaramu ke layanan ini untuk diubah menjadi teks, sesuai kebijakan privasi masing-masing. Skular tidak menerima maupun menyimpan rekaman suaramu.') }}</li>
                <li>{{ __('Penyedia hosting (server di :location): menyimpan database, file, dan log.', ['location' => config('legal.hosting.server_location')]) }}</li>
                <li>{{ __('Penyedia email (:provider): mengirim email verifikasi, reset password, dan pengingat (termasuk pengingat event).', ['provider' => config('legal.hosting.mail_provider')]) }}</li>
                <li>{{ __('Tim Skular yang berwenang: admin hanya melihat data sesuai perannya di panel admin.') }}</li>
                <li>{{ __('Aparat penegak hukum atau lembaga berwenang, bila diwajibkan peraturan.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="luar-negeri" number="6" :title="$sections[5]['title']">
            <p>{{ __('Google, OpenRouter, penyedia model AI yang dipakai OpenRouter, serta layanan pengenalan suara browser, dapat memproses data di luar Indonesia. UU PDP (Pasal 56) membolehkan transfer ke luar Indonesia bila negara tujuan memberi pelindungan yang setara atau lebih tinggi, bila ada pelindungan yang memadai dan mengikat, atau dengan persetujuan subjek data.') }}</p>
            <p>{{ __('Kami meminta persetujuanmu untuk transfer ini saat kamu menyetujui Kebijakan Privasi. Kalau kamu tidak setuju data tulisanmu diproses di luar Indonesia, jangan memakai fitur AI. Fitur lain tetap bisa kamu pakai.') }}</p>
        </x-legal.section>

        <x-legal.section id="berbagi" number="7" :title="$sections[6]['title']">
            <p>{{ __('Skular tidak menjual data pribadimu. Kami hanya membagikan data kepada pihak lain dalam bentuk agregat atau anonim yang tidak bisa dipakai untuk mengenali kamu, misalnya jumlah pengguna per jenjang. Data yang bisa mengenali kamu hanya dibagikan kepada pihak lain bila kamu memberi persetujuan terpisah untuk keperluan itu.') }}</p>
        </x-legal.section>

        <x-legal.section id="anak" number="8" :title="$sections[7]['title']">
            <p>{{ __('Banyak pengguna Skular adalah pelajar. UU PDP (Pasal 25) mengatur bahwa pemrosesan data pribadi anak diselenggarakan secara khusus dan wajib mendapat persetujuan orang tua dan/atau wali.') }}</p>
            <p>{{ __('Saat mendaftar, kamu menyatakan bahwa kamu berusia 18 tahun ke atas, atau orang tua/walimu sudah mengizinkan kamu memakai Skular dan menyetujui kebijakan ini. Kami belum memverifikasi usia secara otomatis.') }}</p>
            <p>{{ __('Orang tua atau wali bisa menghubungi :email untuk bertanya, meminta salinan data, atau meminta akun anaknya dihapus. Sertakan email akun anak dan bukti bahwa kamu orang tua atau walinya.', ['email' => $privacyEmail]) }}</p>
        </x-legal.section>

        <x-legal.section id="masa-simpan" number="9" :title="$sections[8]['title']">
            <p>{{ __('Data akun, profil, isi belajar, dokumen, dan pilihan beasiswa disimpan selama akunmu aktif. Sesi login berakhir setelah :minutes menit tidak aktif, kecuali kamu memilih "Ingat saya".', ['minutes' => $sessionMinutes]) }}</p>
            <h3>{{ __('Saat kamu menghapus akun') }}</h3>
            <p>{{ __('Kamu bisa menghapus akun di Pengaturan dengan memasukkan password. Akun yang dibuat lewat Google belum punya password yang kamu ketahui, jadi minta penghapusan lewat :email.', ['email' => $privacyEmail]) }}</p>
            <ul>
                <li>{{ __('Yang langsung terhapus dari database: akun, profil, passkey, progres modul, jawaban latihan, jawaban dan hasil Try Out, jawaban dan hasil Tes IQ, sesi brainstorming, masukan modul, pendaftaran dan masukan event, catatan aktivitas, label akun, beasiswa pilihan, checklist, data Vault, langganan Pro, kode referral, dan riwayat pesanan.') }}</li>
                <li>{{ __('Yang tetap tersimpan tanpa tautan ke akunmu: tiket bantuan dan laporan info beasiswa yang pernah kamu kirim, serta notifikasi status pembayaran dari Midtrans.') }}</li>
                <li>{{ __('File yang kamu unggah (lampiran latihan, bukti pendaftaran, dokumen Vault, screenshot tiket) saat ini belum terhapus otomatis dari server saat akun dihapus. Minta penghapusannya lewat :email dan kami hapus secara manual.', ['email' => $privacyEmail]) }}</li>
                <li>{{ __('Catatan transaksi di Midtrans dan data di Google mengikuti kebijakan masing-masing.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="hak" number="10" :title="$sections[9]['title']">
            <p>{{ __('Sesuai UU PDP (Pasal 5 sampai 13), kamu berhak:') }}</p>
            <ul>
                <li>{{ __('mendapat informasi tentang siapa yang memproses datamu, untuk apa, dan dasar hukumnya;') }}</li>
                <li>{{ __('melengkapi, memperbarui, atau memperbaiki data yang salah (sebagian besar bisa langsung di halaman Profil dan Pengaturan);') }}</li>
                <li>{{ __('mendapat akses dan salinan datamu;') }}</li>
                <li>{{ __('meminta pemrosesan dihentikan, dan datamu dihapus atau dimusnahkan;') }}</li>
                <li>{{ __('menarik persetujuan yang pernah kamu berikan;') }}</li>
                <li>{{ __('mengajukan keberatan atas keputusan yang hanya didasarkan pada pemrosesan otomatis;') }}</li>
                <li>{{ __('meminta pemrosesan ditunda atau dibatasi;') }}</li>
                <li>{{ __('mendapat datamu dalam format elektronik yang umum dipakai dan memindahkannya;') }}</li>
                <li>{{ __('menggugat dan menerima ganti rugi atas pelanggaran pemrosesan datamu.') }}</li>
            </ul>
            <p>{{ __('Kirim permintaan ke :email dari email akunmu. Tulis hak yang ingin kamu pakai. Kami bisa meminta konfirmasi untuk memastikan permintaan benar dari kamu.', ['email' => $privacyEmail]) }}</p>
            <p>{{ __('UU PDP mengatur batas waktu 3 x 24 jam sejak permintaan diterima untuk memberi akses (Pasal 32), memperbaiki data (Pasal 30), menghentikan pemrosesan setelah persetujuan ditarik (Pasal 40), dan menunda atau membatasi pemrosesan (Pasal 41). Kami mengikuti batas waktu itu. Permintaan lain kami tindak lanjuti secepatnya dan kami kabari perkembangannya.') }}</p>
            <p>{{ __('Menarik persetujuan untuk Google Analytics cukup lewat tombol "Pengaturan cookie" di bagian bawah halaman.') }}</p>
        </x-legal.section>

        <x-legal.section id="keamanan" number="11" :title="$sections[10]['title']">
            <p>{{ __('Langkah keamanan yang sudah berjalan:') }}</p>
            <ul>
                <li>{{ __('Password disimpan dalam bentuk hash. Kamu bisa mengaktifkan verifikasi dua langkah dan passkey.') }}</li>
                <li>{{ __('Dokumen Vault, bukti pendaftaran, lampiran latihan, dan screenshot tiket disimpan di penyimpanan privat tanpa alamat publik. Dokumen Vault hanya bisa diunduh oleh pemiliknya setelah login. Bukti pendaftaran, lampiran latihan, dan screenshot tiket juga bisa dibuka admin yang berwenang untuk keperluan peninjauan dan bantuan.') }}</li>
                <li>{{ __('Notifikasi pembayaran dari Midtrans diperiksa tanda tangannya dan nominalnya sebelum Pro diaktifkan.') }}</li>
                <li>{{ __('Akses panel admin dibatasi berdasarkan peran.') }}</li>
            </ul>
            <p>{{ __('Tidak ada sistem yang sepenuhnya bebas risiko. Jaga password-mu dan jangan berbagi akun.') }}</p>
        </x-legal.section>

        <x-legal.section id="kebocoran" number="12" :title="$sections[11]['title']">
            <p>{{ __('Bila terjadi kegagalan pelindungan data pribadi, kami akan menyelidiki dan menanganinya, lalu memberi tahu kamu dan lembaga pelindungan data pribadi secara tertulis paling lambat 3 x 24 jam, sesuai UU PDP (Pasal 46). Pemberitahuan memuat data apa yang terungkap, kapan dan bagaimana terjadinya, serta langkah penanganan dan pemulihan.') }}</p>
        </x-legal.section>

        <x-legal.section id="persetujuan" number="13" :title="$sections[12]['title']">
            <p>{{ __('Saat kamu daftar, masuk pertama kali dengan Google, menyetujui versi baru, atau membayar, kami mencatat dokumen dan versi yang kamu setujui beserta waktunya. Catatan ini menjadi bukti persetujuan dan disimpan selama akunmu ada.') }}</p>
            <p>{{ __('Pilihan cookie disimpan di browsermu (cookie :cookie), bukan di akunmu. Lihat Kebijakan Cookie.', ['cookie' => config('legal.consent_cookie.name')]) }}</p>
        </x-legal.section>

        <x-legal.section id="perubahan" number="14" :title="$sections[13]['title']">
            <p>{{ __('Kalau kebijakan ini berubah, nomor versi dan tanggal berlaku di atas ikut berubah. Untuk perubahan isi, kamu akan diminta menyetujui versi baru saat masuk ke Skular berikutnya.') }}</p>
        </x-legal.section>

        <x-legal.section id="kontak" number="15" :title="$sections[14]['title']">
            <p>{{ __('Permintaan dan pertanyaan data pribadi: :privacy. Bantuan umum: :contact atau halaman Bantuan setelah login.', ['privacy' => $privacyEmail, 'contact' => $contactEmail]) }}</p>
            <p>{{ __('Kalau jawaban kami belum menyelesaikan masalahmu, kamu bisa mengadu ke lembaga yang berwenang di bidang pelindungan data pribadi sesuai peraturan yang berlaku.') }}</p>
        </x-legal.section>
    </x-legal.document>
</x-layouts::public>
