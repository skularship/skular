{{--
    Kebijakan Cookie (route legal.cookies). Cookie names and lifetimes are read
    from config (session, legal) so the table follows the running app.
    Raise legal.documents.cookies.version when this text changes: every visitor
    is then asked for their cookie choice again.
--}}
@php
    $document = \App\Enums\LegalDocument::Cookies;
    $sessionCookie = config('session.cookie');
    $sessionMinutes = (int) config('session.lifetime');
    $consentCookie = config('legal.consent_cookie.name');
    $consentDays = (int) config('legal.consent_cookie.days');

    $summary = [
        __('Cookie esensial selalu aktif karena login dan keamanan formulir tidak bisa berjalan tanpanya.'),
        __('Google Analytics (cookie analitik) hanya aktif kalau kamu memilih "Terima semua" atau mengaktifkannya di "Atur pilihan".'),
        __('Kami tidak memakai cookie iklan.'),
        __('Pilihanmu disimpan di cookie :name selama :days hari.', ['name' => $consentCookie, 'days' => $consentDays]),
        __('Kamu bisa mengubah pilihan kapan saja lewat "Pengaturan cookie" di bagian bawah halaman.'),
    ];

    $sections = [
        ['id' => 'apa-itu', 'title' => __('Apa itu cookie')],
        ['id' => 'kategori', 'title' => __('Kategori cookie di Skular')],
        ['id' => 'daftar', 'title' => __('Daftar cookie dan penyimpanan browser')],
        ['id' => 'pihak-ketiga', 'title' => __('Layanan pihak ketiga')],
        ['id' => 'mengatur', 'title' => __('Cara mengubah pilihan')],
        ['id' => 'perubahan', 'title' => __('Perubahan kebijakan')],
    ];
@endphp

<x-layouts::public :title="$document->label()" :description="__('Cookie apa saja yang dipakai Skular, untuk apa, berapa lama, dan cara mengubah pilihan cookie-mu.')">
    <x-legal.document :document="$document" :summary="$summary" :sections="$sections">
        <x-legal.section id="apa-itu" number="1" :title="$sections[0]['title']">
            <p>{{ __('Cookie adalah file kecil yang disimpan browser saat kamu membuka situs. Selain cookie, kami juga memakai penyimpanan browser (localStorage dan sessionStorage) untuk beberapa hal kecil. Kebijakan ini menjelaskan keduanya.') }}</p>
        </x-legal.section>

        <x-legal.section id="kategori" number="2" :title="$sections[1]['title']">
            <ul>
                <li>{{ __('Esensial: selalu aktif, tidak butuh persetujuan. Dipakai untuk login, keamanan formulir, dan menyimpan pilihan cookie-mu.') }}</li>
                <li>{{ __('Analitik: opsional. Google Analytics 4 untuk menghitung kunjungan halaman. Tidak dimuat sama sekali sebelum kamu mengizinkan.') }}</li>
            </ul>
            <p>{{ __('Skular tidak memakai cookie iklan atau pelacakan untuk iklan.') }}</p>
        </x-legal.section>

        <x-legal.section id="daftar" number="3" :title="$sections[2]['title']">
            <div class="sk-legal-table">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Nama') }}</th>
                            <th>{{ __('Penyedia') }}</th>
                            <th>{{ __('Tujuan') }}</th>
                            <th>{{ __('Masa berlaku') }}</th>
                            <th>{{ __('Kategori') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $sessionCookie }}</td>
                            <td>Skular</td>
                            <td>{{ __('Menjaga sesi login dan isi formulir antarhalaman.') }}</td>
                            <td>{{ __(':minutes menit sejak aktivitas terakhir', ['minutes' => $sessionMinutes]) }}</td>
                            <td>{{ __('Esensial') }}</td>
                        </tr>
                        <tr>
                            <td>XSRF-TOKEN</td>
                            <td>Skular</td>
                            <td>{{ __('Mencegah formulir dikirim oleh situs lain atas namamu (keamanan CSRF).') }}</td>
                            <td>{{ __(':minutes menit', ['minutes' => $sessionMinutes]) }}</td>
                            <td>{{ __('Esensial') }}</td>
                        </tr>
                        <tr>
                            <td>remember_web_*</td>
                            <td>Skular</td>
                            <td>{{ __('Membuatmu tetap masuk bila kamu mencentang "Ingat saya". Tidak dibuat bila tidak dicentang.') }}</td>
                            <td>{{ __('Sekitar 400 hari, atau sampai kamu keluar') }}</td>
                            <td>{{ __('Esensial') }}</td>
                        </tr>
                        <tr>
                            <td>{{ $consentCookie }}</td>
                            <td>Skular</td>
                            <td>{{ __('Menyimpan pilihan cookie-mu dan versi kebijakan yang kamu pilih.') }}</td>
                            <td>{{ __(':days hari', ['days' => $consentDays]) }}</td>
                            <td>{{ __('Esensial') }}</td>
                        </tr>
                        <tr>
                            <td>_ga</td>
                            <td>Google</td>
                            <td>{{ __('Membedakan pengunjung untuk statistik kunjungan.') }}</td>
                            <td>{{ __('Hingga 2 tahun') }}</td>
                            <td>{{ __('Analitik') }}</td>
                        </tr>
                        <tr>
                            <td>_ga_*</td>
                            <td>Google</td>
                            <td>{{ __('Menyimpan status sesi kunjungan untuk Google Analytics 4.') }}</td>
                            <td>{{ __('Hingga 2 tahun') }}</td>
                            <td>{{ __('Analitik') }}</td>
                        </tr>
                        <tr>
                            <td>skular.tour.* ({{ __('localStorage') }})</td>
                            <td>Skular</td>
                            <td>{{ __('Mengingat bahwa tur pengenalan di halaman depan sudah kamu lihat atau lewati.') }}</td>
                            <td>{{ __('Sampai kamu menghapus data browser') }}</td>
                            <td>{{ __('Esensial') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Jawaban profiling (sessionStorage)') }}</td>
                            <td>Skular</td>
                            <td>{{ __('Menyimpan sementara jawaban profiling dan Profiling Kilat supaya tidak hilang saat kamu kembali atau memuat ulang halaman.') }}</td>
                            <td>{{ __('Sampai tab ditutup atau jawaban dikirim') }}</td>
                            <td>{{ __('Esensial') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p>{{ __('Masa berlaku cookie Google ditentukan oleh Google dan bisa berubah. Data yang dikirim ke Google Analytics sudah dibersihkan dari parameter URL, token reset password, dan ID verifikasi email.') }}</p>
        </x-legal.section>

        <x-legal.section id="pihak-ketiga" number="4" :title="$sections[3]['title']">
            <ul>
                <li>{{ __('Midtrans: saat kamu menekan tombol bayar di halaman Paket, Skular memuat skrip pembayaran Midtrans Snap. Midtrans bisa memasang cookie sendiri yang diperlukan untuk memproses pembayaran dan mencegah penipuan. Skrip ini tidak dimuat di halaman lain.') }}</li>
                <li>{{ __('Google: bila kamu masuk dengan Google, kamu diarahkan ke halaman Google yang memakai cookie Google sendiri.') }}</li>
            </ul>
            <p>{{ __('Cookie pihak ketiga diatur oleh kebijakan masing-masing penyedia.') }}</p>
        </x-legal.section>

        <x-legal.section id="mengatur" number="5" :title="$sections[4]['title']">
            <p>{{ __('Klik "Pengaturan cookie" di bagian bawah halaman untuk membuka lagi pilihan cookie. Kalau kamu menarik izin analitik, Google Analytics berhenti mengirim data dan cookie _ga dihapus dari browsermu.') }}</p>
            <p>
                <button type="button" data-cookie-settings class="rounded-xl border border-brand-teal px-4 py-2 text-sm font-semibold text-brand-teal">{{ __('Buka pengaturan cookie') }}</button>
            </p>
            <p>{{ __('Kamu juga bisa menghapus atau memblokir cookie lewat pengaturan browser. Kalau cookie esensial diblokir, kamu tidak bisa masuk ke akun Skular.') }}</p>
        </x-legal.section>

        <x-legal.section id="perubahan" number="6" :title="$sections[5]['title']">
            <p>{{ __('Kalau daftar cookie berubah, nomor versi kebijakan ini naik dan banner cookie akan muncul lagi supaya kamu bisa memilih ulang.') }}</p>
        </x-legal.section>
    </x-legal.document>
</x-layouts::public>
