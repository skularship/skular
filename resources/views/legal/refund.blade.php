{{--
    Kebijakan Pengembalian Dana (route legal.refund). Rules come from
    config/legal.php `refund` (owner decision, Bagian A no. 4 and 5). Refund
    support per payment method follows the Midtrans refund documentation
    (credit card, e-wallet, QRIS, Kredivo, Akulaku; other methods by manual
    transfer). There is no refund flow in the code: admins follow
    docs/refund-manual.md. Raise legal.documents.refund.version when this text changes.
--}}
@php
    $document = \App\Enums\LegalDocument::Refund;
    $contactEmail = config('legal.contact.email');
    $windowDays = (int) config('legal.refund.window_days');
    $reviewDays = (int) config('legal.refund.review_days');
    $fullAmount = (bool) config('legal.refund.full_amount_including_fees');

    $summary = [
        __('Dalam :days hari sejak membayar, kamu bisa meminta refund penuh bila Pro belum kamu pakai.', ['days' => $windowDays]),
        __('Tagihan ganda, salah tagih, Pro tidak aktif setelah bayar, dan gangguan teknis yang tidak teratasi selalu kami kembalikan penuh.'),
        __('Di luar itu, permintaan tetap boleh diajukan dan kami tinjau per kasus.'),
        __('Ajukan lewat email atau halaman Bantuan dengan nomor pesanan (SKL-...) dan email akunmu.'),
        __('Kami meninjau paling lama :days hari kerja. Dana kembali ke metode bayar yang sama bila didukung Midtrans, atau lewat transfer bank.', ['days' => $reviewDays]),
        __('Data belajarmu tidak dihapus karena refund. Akses Pro dari pembayaran itu dicabut atau disesuaikan.'),
    ];

    $sections = [
        ['id' => 'situasi', 'title' => __('Kapan uangmu bisa kembali')],
        ['id' => 'dipakai', 'title' => __('Arti "Pro belum dipakai"')],
        ['id' => 'cara', 'title' => __('Cara mengajukan')],
        ['id' => 'proses', 'title' => __('Tinjauan dan metode pengembalian')],
        ['id' => 'akibat', 'title' => __('Yang terjadi setelah refund disetujui')],
        ['id' => 'apresiasi', 'title' => __('Program apresiasi lolos')],
        ['id' => 'hak-konsumen', 'title' => __('Hakmu sebagai konsumen')],
    ];
@endphp

<x-layouts::public :title="$document->label()" :description="__('Kapan pembayaran Pro Skular bisa dikembalikan, berapa, cara mengajukan, dan berapa lama prosesnya.')">
    <x-legal.document :document="$document" :summary="$summary" :sections="$sections">
        <x-legal.section id="situasi" number="1" :title="$sections[0]['title']">
            <div class="sk-legal-table">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Situasi') }}</th>
                            <th>{{ __('Bisa refund?') }}</th>
                            <th>{{ __('Berapa') }}</th>
                            <th>{{ __('Batas pengajuan') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('Tagihan ganda (satu pembelian terbayar dua kali)') }}</td>
                            <td>{{ __('Ya') }}</td>
                            <td>{{ __('Penuh untuk pembayaran yang dobel') }}</td>
                            <td>{{ __('Kapan saja') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Salah tagih (nominal tidak sesuai harga di halaman Paket atau potongan yang berlaku)') }}</td>
                            <td>{{ __('Ya') }}</td>
                            <td>{{ __('Selisihnya, atau penuh bila kamu membatalkan') }}</td>
                            <td>{{ __('Kapan saja') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pembayaran berhasil tetapi Pro tidak aktif') }}</td>
                            <td>{{ __('Ya') }}</td>
                            <td>{{ __('Kami aktifkan Pro, atau refund penuh bila kamu memilih refund') }}</td>
                            <td>{{ __('Kapan saja') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Gangguan teknis di Skular yang membuat fitur Pro tidak bisa dipakai dan tidak teratasi setelah kamu laporkan') }}</td>
                            <td>{{ __('Ya') }}</td>
                            <td>{{ __('Penuh, atau sebanding sisa masa Pro bila sebagian masa sudah terpakai normal') }}</td>
                            <td>{{ __('Selama masa Pro') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Berubah pikiran, Pro belum dipakai') }}</td>
                            <td>{{ __('Ya') }}</td>
                            <td>{{ __('Penuh') }}</td>
                            <td>{{ __(':days hari sejak membayar', ['days' => $windowDays]) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Tidak sengaja memperpanjang Pro (membeli lagi saat Pro masih aktif)') }}</td>
                            <td>{{ __('Ya, karena masa baru belum mulai berjalan') }}</td>
                            <td>{{ __('Penuh untuk pembelian perpanjangan itu') }}</td>
                            <td>{{ __(':days hari sejak membayar', ['days' => $windowDays]) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Situasi lain, atau lewat batas waktu di atas') }}</td>
                            <td>{{ __('Ditinjau per kasus') }}</td>
                            <td>{{ __('Sesuai hasil tinjauan') }}</td>
                            <td>{{ __('Selama masa Pro') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @if ($fullAmount)
                <p>{{ __('Refund penuh berarti seluruh nominal yang kamu bayar, tanpa dipotong biaya payment gateway.') }}</p>
            @endif
            <p>{{ __('Tidak ada penagihan otomatis di Skular, jadi kamu tidak akan ditagih lagi setelah masa Pro berakhir.') }}</p>
        </x-legal.section>

        <x-legal.section id="dipakai" number="2" :title="$sections[1]['title']">
            <p>{{ __('Pro dianggap belum dipakai bila sejak pembayaran itu kamu belum mengunggah dokumen ke Vault dan belum membuat atau mengubah checklist beasiswa. Membaca halaman, belajar modul, dan memakai fitur yang juga ada di paket Gratis tidak dihitung sebagai memakai Pro.') }}</p>
        </x-legal.section>

        <x-legal.section id="cara" number="3" :title="$sections[2]['title']">
            <p>{{ __('Kirim permintaan ke :email, atau lewat halaman Bantuan setelah login. Sertakan:', ['email' => $contactEmail]) }}</p>
            <ul>
                <li>{{ __('nomor pesanan (dimulai dengan SKL-, ada di halaman Pembayaran);') }}</li>
                <li>{{ __('email akun Skular;') }}</li>
                <li>{{ __('alasan dan situasimu (lihat tabel di atas);') }}</li>
                <li>{{ __('untuk tagihan ganda atau salah tagih: bukti pembayaran;') }}</li>
                <li>{{ __('bila kamu membayar dengan virtual account, transfer bank, atau gerai retail: nama bank, nomor rekening, dan nama pemilik rekening tujuan refund.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="proses" number="4" :title="$sections[3]['title']">
            <ul>
                <li>{{ __('Kami meninjau dan mengabari hasilnya paling lama :days hari kerja sejak data lengkap kami terima.', ['days' => $reviewDays]) }}</li>
                <li>{{ __('Pembayaran dengan kartu kredit, e-wallet (seperti GoPay, ShopeePay, DANA, OVO), QRIS, Kredivo, atau Akulaku dikembalikan ke metode yang sama lewat Midtrans. Setiap metode punya batas waktu refund sendiri dari penyedianya; ada yang hanya 7 hari. Bila batas itu terlewat, kami kembalikan lewat transfer bank.') }}</li>
                <li>{{ __('Pembayaran dengan virtual account, transfer bank, atau gerai retail tidak bisa dikembalikan otomatis oleh Midtrans. Untuk metode ini, kami mentransfer dana ke rekening yang kamu berikan.') }}</li>
                <li>{{ __('Lama dana sampai ke rekening atau saldomu setelah refund kami proses tergantung bank atau penyedia e-wallet. Kami kirim konfirmasi ke emailmu saat refund kami proses.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="akibat" number="5" :title="$sections[4]['title']">
            <ul>
                <li>{{ __('Masa Pro dari pembayaran yang dikembalikan dicabut. Untuk refund sebagian, masa Pro disesuaikan dengan nominal yang tidak dikembalikan.') }}</li>
                <li>{{ __('Data belajar, jawaban latihan, dan pilihan beasiswamu tetap ada.') }}</li>
                <li>{{ __('Dokumen di Vault tetap bisa kamu unduh dan hapus, seperti saat Pro berakhir.') }}</li>
                <li>{{ __('Potongan kode referral yang terpakai di pembayaran itu tidak bisa dipakai ulang.') }}</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="apresiasi" number="6" :title="$sections[5]['title']">
            <p>{{ __('Program apresiasi bagi pengguna yang lolos beasiswa belum berjalan. Program itu, bila nanti ada, adalah bentuk apresiasi, bukan pengembalian dana dan bukan janji kelulusan. Ketentuan dan nominalnya akan ditampilkan sebelum kamu membayar. Selama belum ada pengumuman di halaman ini dan di Syarat & Ketentuan, tidak ada program apresiasi yang berlaku.') }}</p>
        </x-legal.section>

        <x-legal.section id="hak-konsumen" number="7" :title="$sections[6]['title']">
            <p>{{ __('Kebijakan ini tidak mengurangi hakmu sebagai konsumen menurut peraturan perundang-undangan. Kalau kamu tidak setuju dengan hasil tinjauan kami, balas email kami dan jelaskan alasanmu. Penyelesaian sengketa mengikuti Syarat & Ketentuan.') }}</p>
        </x-legal.section>
    </x-legal.document>
</x-layouts::public>
