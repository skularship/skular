{{--
    Syarat & Ketentuan (route legal.terms). Plan, referral, quota and limit
    facts are read from config and the services that enforce them, so the text
    follows the code. Prices are never written here; the Paket page shows them.
    The "Program apresiasi" section stays hidden while
    legal.features.success_appreciation is false.
    Raise legal.documents.terms.version when this text changes.
--}}
@php
    $document = \App\Enums\LegalDocument::Terms;
    $company = config('legal.company.name');
    $contactEmail = config('legal.contact.email');
    $chatLimit = (int) config('ai.module_chat.limit');
    $maxScholarships = \App\Services\ScholarshipApplicationService::MAX_SELECTIONS;
    $referralPercent = \App\Services\ReferralService::PERCENT;
    $showAppreciation = (bool) config('legal.features.success_appreciation');

    $summary = [
        __('Skular membantu kamu bersiap mendaftar beasiswa. Skular bukan penyelenggara beasiswa dan tidak berafiliasi dengan penyelenggara mana pun.'),
        __('Skular tidak menjamin kamu lolos beasiswa. Hasil seleksi sepenuhnya ditentukan penyelenggara.'),
        __('Penilaian dan saran AI bisa keliru. Jawaban akhirmu harus tulisanmu sendiri, dan kamu wajib mengikuti aturan penyelenggara soal pemakaian AI.'),
        __('Pro dibayar di muka lewat Midtrans, tanpa perpanjangan otomatis. Harga tampil di halaman Paket sebelum kamu membayar.'),
        __('Kamu bisa meminta pengembalian dana sesuai Kebijakan Pengembalian Dana.'),
        __('Tulisanmu tetap milikmu. Kami hanya memprosesnya untuk menjalankan layanan.'),
        __('Satu akun untuk satu orang. Jangan berbagi akun atau menyalahgunakan kuota AI dan kode referral.'),
    ];

    $sections = [
        ['id' => 'definisi', 'title' => __('Definisi')],
        ['id' => 'penerimaan', 'title' => __('Menerima syarat ini')],
        ['id' => 'pengguna', 'title' => __('Siapa yang boleh memakai Skular')],
        ['id' => 'akun', 'title' => __('Akun dan keamanannya')],
        ['id' => 'layanan', 'title' => __('Sifat layanan Skular')],
        ['id' => 'ai', 'title' => __('Pemakaian fitur AI')],
        ['id' => 'paket', 'title' => __('Paket dan pembayaran')],
        ['id' => 'referral', 'title' => __('Kode referral')],
        ['id' => 'pro-berakhir', 'title' => __('Saat Pro berakhir')],
        ['id' => 'konten', 'title' => __('Konten milikmu')],
        ['id' => 'larangan', 'title' => __('Hal yang dilarang')],
        ['id' => 'hki', 'title' => __('Hak atas materi Skular')],
        ['id' => 'penangguhan', 'title' => __('Penangguhan dan penutupan akun')],
        ['id' => 'tanggung-jawab', 'title' => __('Batas tanggung jawab')],
        ['id' => 'kahar', 'title' => __('Keadaan kahar')],
        ['id' => 'perubahan', 'title' => __('Perubahan syarat')],
        ['id' => 'hukum', 'title' => __('Hukum yang berlaku dan sengketa')],
        ['id' => 'kontak', 'title' => __('Kontak')],
    ];

    if ($showAppreciation) {
        array_splice($sections, 9, 0, [['id' => 'apresiasi', 'title' => __('Program apresiasi')]]);
    }

    $number = fn (string $id): int => array_search($id, array_column($sections, 'id'), true) + 1;
    $title = fn (string $id): string => $sections[$number($id) - 1]['title'];
@endphp

<x-layouts::public :title="$document->label()" :description="__('Syarat pemakaian Skular: sifat layanan, pemakaian AI, paket Pro dan pembayaran, konten pengguna, dan penyelesaian sengketa.')">
    <x-legal.document :document="$document" :summary="$summary" :sections="$sections">
        <x-legal.section id="definisi" :number="$number('definisi')" :title="$title('definisi')">
            <ul>
                <li>{{ __('"Skular" atau "kami": :company, pengelola situs skularship.com.', ['company' => $company]) }}</li>
                <li>{{ __('"Kamu" atau "pengguna": orang yang membuat akun atau memakai Skular.') }}</li>
                <li>{{ __('"Layanan": semua fitur Skular, termasuk profiling, rekomendasi beasiswa, roadmap modul, latihan, AI Mentor, AI Brainstorming, tracker beasiswa, dan fitur Pro.') }}</li>
                <li>{{ __('"Penyelenggara": lembaga, perusahaan, atau pemerintah yang membuka dan menyeleksi beasiswa.') }}</li>
                <li>{{ __('"Pro": paket berbayar dengan masa aktif tertentu.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="penerimaan" :number="$number('penerimaan')" :title="$title('penerimaan')">
            <p>{{ __('Dengan membuat akun atau memakai Skular, kamu menyetujui Syarat & Ketentuan ini dan Kebijakan Privasi. Saat daftar, kamu mencentang persetujuan ini. Kami mencatat versi yang kamu setujui.') }}</p>
        </x-legal.section>

        <x-legal.section id="pengguna" :number="$number('pengguna')" :title="$title('pengguna')">
            <p>{{ __('Kamu boleh memakai Skular bila berusia 18 tahun ke atas. Kalau kamu belum 18 tahun, kamu boleh memakai Skular bila orang tua atau walimu sudah mengizinkan dan menyetujui syarat ini serta Kebijakan Privasi. Saat mendaftar, kamu menyatakan hal tersebut benar.') }}</p>
            <p>{{ __('Pembayaran Pro untuk pengguna di bawah 18 tahun sebaiknya dilakukan atau disetujui orang tua atau wali.') }}</p>
        </x-legal.section>

        <x-legal.section id="akun" :number="$number('akun')" :title="$title('akun')">
            <ul>
                <li>{{ __('Satu akun untuk satu orang. Isi data dengan benar.') }}</li>
                <li>{{ __('Jaga kerahasiaan password dan passkey-mu. Kami menyarankan verifikasi dua langkah.') }}</li>
                <li>{{ __('Beri tahu kami lewat :email bila kamu menduga akunmu dipakai orang lain.', ['email' => $contactEmail]) }}</li>
                <li>{{ __('Kamu bertanggung jawab atas aktivitas di akunmu, kecuali bila penyalahgunaan terjadi karena kelalaian kami.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="layanan" :number="$number('layanan')" :title="$title('layanan')">
            <p>{{ __('Skular adalah platform persiapan beasiswa dan tidak berafiliasi dengan penyelenggara beasiswa mana pun. Pendaftaran beasiswa selalu gratis dan dilakukan di situs resmi penyelenggaranya.') }}</p>
            <ul>
                <li>{{ __('Skular tidak menyeleksi penerima beasiswa dan tidak menjamin kamu lolos. Keputusan seleksi sepenuhnya ada di penyelenggara.') }}</li>
                <li>{{ __('Info beasiswa di Skular (syarat, tanggal, tautan) kami rangkum dari sumber publik dan bisa berubah sewaktu-waktu. Selalu cek ke situs resmi penyelenggara sebelum mendaftar. Kamu bisa melaporkan info yang keliru lewat fitur laporan di halaman beasiswa.') }}</li>
                <li>{{ __('Rekomendasi beasiswa dihitung dari profil yang kamu isi. Kamu bisa memilih sampai :max beasiswa.', ['max' => $maxScholarships]) }}</li>
                <li>{{ __('Kami bisa menambah, mengubah, atau menghentikan fitur. Untuk perubahan yang mengurangi fitur Pro yang sudah kamu bayar, berlaku Kebijakan Pengembalian Dana.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="ai" :number="$number('ai')" :title="$title('ai')">
            <ul>
                <li>{{ __('Penilaian latihan, AI Mentor, dan AI Brainstorming adalah saran belajar. AI bisa keliru atau kurang lengkap. Pertimbangkan sendiri sebelum memakainya.') }}</li>
                <li>{{ __('AI di Skular menjelaskan, menilai, memberi kerangka, dan berdiskusi. AI tidak menulis jawaban akhirmu. Jawaban yang kamu kirim ke penyelenggara harus karyamu sendiri.') }}</li>
                <li>{{ __('Kamu bertanggung jawab mengikuti aturan penyelenggara tentang pemakaian AI dan plagiarisme.') }}</li>
                <li>{{ __('Ada batas pemakaian supaya layanan adil dan stabil: pertanyaan ke AI Mentor yang dijawab AI dibatasi :limit per modul (saran pertanyaan dari tim tidak memakai kuota), dan permintaan AI dibatasi beberapa kali per menit.', ['limit' => $chatLimit]) }}</li>
                <li>{{ __('Isi yang kamu kirim ke fitur AI diproses penyedia AI, sebagian di luar Indonesia. Lihat Kebijakan Privasi.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="paket" :number="$number('paket')" :title="$title('paket')">
            <ul>
                <li>{{ __('Skular punya paket Gratis dan Pro. Pro tersedia untuk 1 bulan dan 12 bulan. Isi tiap paket, harga, dan promo yang berlaku tampil di halaman Paket sebelum kamu membayar. Harga yang berlaku adalah harga yang tampil di halaman pembayaran saat kamu membayar.') }}</li>
                <li>{{ __('Pro dibayar di muka sekali bayar lewat Midtrans. Tidak ada perpanjangan atau penagihan otomatis.') }}</li>
                <li>{{ __('Biaya tambahan dari metode pembayaran atau pajak, bila ada, ditampilkan sebelum kamu membayar.') }}</li>
                <li>{{ __('Pro aktif setelah Midtrans mengonfirmasi pembayaranmu berhasil. Biasanya langsung, tetapi bisa butuh waktu tergantung metode bayar.') }}</li>
                <li>{{ __('Kalau kamu membeli Pro saat Pro masih aktif, masa aktif baru menyambung dari tanggal berakhir yang lama.') }}</li>
                <li>{{ __('Pengembalian dana diatur dalam Kebijakan Pengembalian Dana.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="referral" :number="$number('referral')" :title="$title('referral')">
            <ul>
                <li>{{ __('Setiap pengguna mendapat kode referral sendiri.') }}</li>
                <li>{{ __('Kode referral memberi potongan :percent% dari harga launching, hanya untuk paket Pro 12 bulan selama periode harga launching berjalan. Potongan dihitung oleh sistem saat kamu membayar.', ['percent' => $referralPercent]) }}</li>
                <li>{{ __('Kamu tidak bisa memakai kodemu sendiri, dan setiap akun hanya bisa memakai kode referral satu kali.') }}</li>
                <li>{{ __('Saat ini pemilik kode tidak mendapat imbalan. Bila nanti ada, aturannya akan ditulis di sini lebih dulu.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="pro-berakhir" :number="$number('pro-berakhir')" :title="$title('pro-berakhir')">
            <p>{{ __('Kami mengirim email pengingat sebelum masa Pro berakhir. Setelah Pro berakhir, akunmu kembali ke paket Gratis. Data belajarmu tetap ada. Dokumen di Vault tetap bisa kamu unduh dan hapus, tetapi kamu tidak bisa menambah dokumen baru. Fitur khusus Pro lain, seperti checklist dan pengingat untuk lebih dari satu beasiswa, berhenti sampai kamu membeli Pro lagi.') }}</p>
        </x-legal.section>

        @if ($showAppreciation)
            <x-legal.section id="apresiasi" :number="$number('apresiasi')" :title="$title('apresiasi')">
                <p>{{ __('[KEPUTUSAN: ketentuan program apresiasi, nominal yang tampil di depan, syarat bukti lolos, dan tenggat klaim]') }}</p>
                <p>{{ __('Program ini adalah apresiasi, bukan pengembalian dana dan bukan janji kelulusan.') }}</p>
            </x-legal.section>
        @endif

        <x-legal.section id="konten" :number="$number('konten')" :title="$title('konten')">
            <ul>
                <li>{{ __('Jawaban latihan, esai, cerita, dan dokumen yang kamu tulis atau unggah tetap milikmu.') }}</li>
                <li>{{ __('Kamu memberi Skular izin terbatas untuk menyimpan, menampilkan kepadamu, dan memproses konten itu (termasuk mengirimnya ke penyedia AI) hanya untuk menjalankan layanan untukmu.') }}</li>
                <li>{{ __('Kami tidak memakai kontenmu untuk keperluan lain, termasuk perbaikan layanan, kecuali sesuai Kebijakan Privasi.') }}</li>
                <li>{{ __('Pastikan kamu berhak atas konten yang kamu unggah, dan jangan mengunggah data pribadi orang lain tanpa izin.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="larangan" :number="$number('larangan')" :title="$title('larangan')">
            <p>{{ __('Saat memakai Skular, kamu tidak boleh:') }}</p>
            <ul>
                <li>{{ __('mengakses akun, data, atau bagian sistem yang bukan hakmu, atau mencoba membobol keamanan Skular;') }}</li>
                <li>{{ __('mengambil data atau materi Skular secara otomatis (scraping, bot) tanpa izin tertulis;') }}</li>
                <li>{{ __('menyalahgunakan kuota AI, misalnya dengan membuat banyak akun atau mengakali batas pemakaian;') }}</li>
                <li>{{ __('mengunggah konten yang melanggar hukum, mengandung virus, melanggar hak orang lain, atau berisi kebencian dan pelecehan;') }}</li>
                <li>{{ __('berbagi, menjual, atau meminjamkan akun;') }}</li>
                <li>{{ __('menyalahgunakan kode referral, misalnya memakai akun palsu untuk mendapat potongan.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="hki" :number="$number('hki')" :title="$title('hki')">
            <p>{{ __('Materi modul, soal latihan, desain, merek, dan kode Skular adalah milik Skular atau pemberi lisensinya. Kamu boleh memakainya untuk belajar pribadi. Kamu tidak boleh menyalin, menjual, atau menyebarkannya ulang tanpa izin tertulis.') }}</p>
            <p>{{ __('Nama beasiswa dan penyelenggara disebut hanya untuk menjelaskan informasi beasiswa. Nama dan merek itu milik pemiliknya masing-masing.') }}</p>
        </x-legal.section>

        <x-legal.section id="penangguhan" :number="$number('penangguhan')" :title="$title('penangguhan')">
            <p>{{ __('Kami bisa menangguhkan atau menutup akun yang melanggar syarat ini. Kecuali untuk pelanggaran berat atau bila diwajibkan hukum, kami memberi tahu alasannya lebih dulu dan memberi kesempatan untuk menjelaskan lewat :email.', ['email' => $contactEmail]) }}</p>
            <p>{{ __('Bila akun Pro ditutup bukan karena pelanggaranmu, sisa masa Pro dihitung sesuai Kebijakan Pengembalian Dana. Kamu bisa menutup akunmu sendiri kapan saja; lihat Kebijakan Privasi untuk data yang ikut terhapus.') }}</p>
        </x-legal.section>

        <x-legal.section id="tanggung-jawab" :number="$number('tanggung-jawab')" :title="$title('tanggung-jawab')">
            <ul>
                <li>{{ __('Kami berusaha menjaga Skular tetap berjalan dan informasinya akurat, tetapi layanan bisa terganggu untuk perawatan atau karena masalah teknis.') }}</li>
                <li>{{ __('Kami tidak bertanggung jawab atas keputusan seleksi penyelenggara, perubahan program beasiswa oleh penyelenggara, atau kerugian karena kamu tidak mengecek info ke situs resmi penyelenggara.') }}</li>
                <li>{{ __('Kami tetap bertanggung jawab atas kerugian yang timbul karena kesalahan atau kelalaian kami sesuai hukum yang berlaku. Syarat ini tidak menghapus hakmu sebagai konsumen menurut peraturan perundang-undangan.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="kahar" :number="$number('kahar')" :title="$title('kahar')">
            <p>{{ __('Kami tidak dianggap melanggar syarat ini bila layanan terganggu karena hal di luar kendali wajar kami, seperti bencana alam, gangguan listrik atau internet skala luas, gangguan penyedia layanan pihak ketiga, atau kebijakan pemerintah. Kami akan memberi kabar dan memulihkan layanan secepatnya. Bila Pro tidak bisa dipakai dalam waktu lama, berlaku Kebijakan Pengembalian Dana.') }}</p>
        </x-legal.section>

        <x-legal.section id="perubahan" :number="$number('perubahan')" :title="$title('perubahan')">
            <p>{{ __('Kami bisa mengubah syarat ini. Nomor versi dan tanggal berlaku di atas ikut berubah. Untuk perubahan isi, kamu diminta menyetujui versi baru saat masuk berikutnya. Perubahan tidak mengurangi hak atas Pro yang sudah kamu bayar sebelum perubahan berlaku.') }}</p>
        </x-legal.section>

        <x-legal.section id="hukum" :number="$number('hukum')" :title="$title('hukum')">
            <p>{{ __('Syarat ini tunduk pada hukum Republik Indonesia. Kalau ada masalah, hubungi kami dulu lewat :email supaya bisa diselesaikan secara musyawarah. Kalau dalam 30 hari belum selesai, sengketa diselesaikan melalui Pengadilan Negeri :city, tanpa mengurangi hakmu menempuh jalur penyelesaian sengketa konsumen lain yang diatur peraturan perundang-undangan.', ['email' => $contactEmail, 'city' => config('legal.jurisdiction_city')]) }}</p>
        </x-legal.section>

        <x-legal.section id="kontak" :number="$number('kontak')" :title="$title('kontak')">
            <p>{{ __(':company, :address. Email: :email. Setelah login, kamu juga bisa memakai halaman Bantuan.', ['company' => $company, 'address' => config('legal.company.address'), 'email' => $contactEmail]) }}</p>
        </x-legal.section>
    </x-legal.document>
</x-layouts::public>
