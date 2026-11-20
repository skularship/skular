# Progres: Inti Materi WYSIWYG + tampilan dokumen

Catatan kerja untuk perubahan editor Inti Materi (Filament RichEditor) dan tampilan Inti Materi di langkah Pahami. Diperbarui setiap tahap selesai.

## Status tahap

| Tahap | Isi | Status |
|---|---|---|
| A | Data: `module_core_sections.body_format`, model, factory, helper body kosong | Selesai |
| B | Renderer: jalur HTML tersanitasi, prop `format`, test keamanan | Selesai |
| C | Admin: RichEditor, whitelist warna, plugin font, konversi Markdown, Pratinjau | Selesai |
| D | Frontend: lembar dokumen di Pahami, `prose.css` baru, daftar isi sticky | Selesai |
| E | Finishing: build aset, dokumentasi, `composer test` | Selesai |

## Keputusan teknis

- Plugin font family memakai TipTap milik Filament yang diekspos di `window.FilamentRichEditor.tiptap` (tanpa paket npm baru, tanpa bundler).
- Whitelist warna dan font disimpan di satu tempat: `config/content.php`.
- Sanitizer membuang `style` yang tersimpan, lalu menyusun ulang `style` hanya dari `data-color` / `data-font` (atau nilai `style` yang persis ada di whitelist).
- AGENTS.md diperbarui di commit yang sama dengan perubahan perilaku (Tahap B), bukan menunggu Tahap E.

### Tahap A

- Migration additive `2026_11_20_115808_add_body_format_to_module_core_sections_table` (string 16, default `markdown`). Tidak ada konversi data.
- Enum `App\Enums\MaterialFormat` (`markdown`, `html`), di-cast pada `ModuleCoreSection::$body_format`; `$attributes` model ikut default `markdown`.
- `ModuleCoreSection::hasBody()`: HTML tanpa teks (`<p></p>`, `&nbsp;`) dianggap kosong. Dipakai `Module::filledCoreSections()`, jadi readiness, outline, Pahami, dan Pratinjau ikut benar.
- Factory: state `html()`.

### Tahap B

- `config/content.php` (`material.text_colors` 6 warna, `material.fonts` 4 font) + `App\Support\MaterialStyles` (akses bertipe, pencocokan kunci/hex/nama font).
- `RichContentRendererInterface::toHtmlFromFormat()` dan `markdownToEditorHtml()`. `toHtml()` tidak berubah.
- Jalur HTML: `symfony/html-sanitizer` dengan default `Drop`; hanya elemen allowlist yang dipertahankan, pembungkus aman (`div`, `section`, `small`, ...) dilepas tapi teksnya tetap. Link hanya `http`/`https`/`mailto`, relatif ditolak; link eksternal diberi `rel="noopener noreferrer" target="_blank"`.
- `<span>` dan `<a>` disusun ulang dari nol: `style` tersimpan (termasuk variabel CSS Filament) tidak pernah dikeluarkan; warna/font diambil dari `data-color`/`data-font` atau nilai `style` yang persis ada di whitelist.
- Heading HTML digeser sama seperti Markdown (h1 → `sk-h1`, h2 → `sk-h2`, h3+ → `sk-h3`). `h1` diizinkan agar Markdown lama yang dikonversi tetap utuh.
- Batas input sanitizer dinaikkan ke 1.000.000 karakter (default Symfony 20.000 akan memotong materi panjang).
- `<x-learning.rich-content>` menerima prop `format` (default `markdown`).
- `composer require symfony/html-sanitizer` ditolak composer karena ada security advisory di lock (lihat Risiko). Paket tetap tersedia sebagai dependensi wajib `filament/support` (`^7.0|^8.0`), sama seperti CommonMark. `composer.json` tidak diubah.

### Tahap C

- `body` kolom inti materi memakai `RichEditor` (HTML). Toolbar: undo/redo | H2/H3 | bold/italic/underline | daftar | highlight, warna teks, dropdown "Jenis huruf" | kutipan, garis, tabel, link | hapus format. Tanpa `attachFiles`, `customTextColors(false)`, link hanya `http/https/mailto`.
- Warna: `textColors()` dibangun dari `config/content.php`. Key `teal` diganti `teal-brand` karena `teal` adalah nama palet Filament: Filament akan menyimpan warnanya sendiri (oklch) untuk key itu. Ada test penjaga.
- **Plugin font (keputusan teknis):** `FontFamilyPlugin` (RichContentPlugin) + `FontFamilyMark` (tiptap-php) + `resources/js/filament/rich-content-plugins/font-family.js`. File JS memakai `window.FilamentRichEditor.tiptap.core` (`Mark.create`), yang disediakan Filament agar ekstensi berbagi instance ProseMirror. Hasilnya tanpa paket npm baru dan tanpa bundler. Mark menyimpan key whitelist (`data-font="lora"`); stack CSS dibagikan ke JS lewat `FilamentAsset::registerScriptData(['skularMaterialFonts' => ...], package: 'app')`. File JS dipasang dengan `FilamentAsset::register(...)->loadedOnRequest()` dan diterbitkan ke `public/js/app/rich-content-plugins/font-family.js` (`php artisan filament:assets`). Dipilih custom mark, bukan `TextStyle` + `FontFamily` bawaan TipTap, agar yang tersimpan adalah key whitelist, bukan nama font bebas.
- Dropdown "Jenis huruf": "Rubik (bawaan)" menghapus mark; ikon grup menyala hanya bila font khusus aktif.
- Konversi Markdown lama: `formatStateUsing` → `markdownToEditorHtml()` hanya di state form; `mutateRelationshipDataBefore{Create,Save}Using` mengisi `body_format = html` saat disimpan.
- Editor diberi kelas pembungkus `sk-material-editor` (gaya `.sk-prose` dipasang di Tahap D).
- Pratinjau admin merender kolom dengan `body_format` dan level heading 5, sama dengan halaman mentee. Test pratinjau lama disesuaikan ke level 5.
- **Bug yang ditemukan saat uji browser:** menyimpan modul dengan kolom berisi daftar/tabel menghasilkan 500 `MaxNestingDepthExceededException` (batas payload Livewire 10). `config/livewire.php` diterbitkan dan `payload.max_nesting_depth` dinaikkan ke 32 sesuai dokumentasi Filament. Test Livewire tidak menangkap ini karena `fillForm` tidak melewati payload HTTP; ada test penjaga config.
- Uji browser (Edge headless, server sandbox SQLite): font Lora diterapkan lewat dropdown dan tersimpan, modal warna hanya menampilkan 6 warna, warna Teal tersimpan, notifikasi "Perubahan modul tersimpan", tanpa error konsol.

### Tahap D

- Inti Materi di Pahami menjadi satu "lembar dokumen" (`article` putih, border halus, `px-5 py-8` / `sm:px-10 sm:py-12`, konten maks 68ch). Header: label "INTI MATERI" + judul modul + "N bagian · ± M menit baca" (200 kata/menit).
- Tiap kolom adalah satu bagian: "Bagian X dari N", subjudul `h4` 24px/800 teal tua, pemisah tipis + `mt-12`. Label bagian dan daftar isi disembunyikan bila hanya satu kolom. Penanda akhir "Kamu sudah sampai di akhir inti materi." sebelum Ringkasan.
- Daftar isi: chip horizontal di mobile; bar chip sticky di `lg`; daftar vertikal sticky di kiri pada `xl`. Daftar kiri dipindah ke `xl` karena di `lg` sidebar aplikasi (288px) menyisakan kolom baca ~430px. Item aktif mengikuti scroll lewat Alpine + IntersectionObserver (observer dilepas di `destroy()`).
- `prose.css` versi baru: 17px / 1.8, heading teal/teal tua/ink, list hanging indent dengan marker teal, blockquote amber, tabel dengan header `#d8ebef` dan scroll horizontal, `mark` lembut. Aturan yang sama dipasang ke area konten editor admin (`.sk-material-editor .fi-fo-rich-editor-content`); tanda kutip otomatis Filament di blockquote dimatikan di editor.
- Font tambahan (Plus Jakarta Sans, Nunito 400/700; Lora 400/700 normal+italic) di-host sendiri lewat `bunny()` dengan `preload: false`, sehingga hanya diunduh bila materi memakainya. Tidak ada request ke pihak ketiga, jadi Kebijakan Privasi/Cookie tidak berubah. Panel admin memuat ketiga font ini lewat `@fonts([...])` di render hook.
- Semua hook `data-test` dipertahankan (`inti-materi`, `inti-materi-kolom`, `rich-content`, `pahami-header`, `next-section`); hook baru: `inti-materi-daftar-isi`, `inti-materi-selesai`. Product Tour tidak menargetkan blok ini.
- Uji browser (sandbox): 360px tanpa scroll horizontal, `lg` bar sticky, `xl` daftar kiri sticky di 24px dan item aktif berpindah saat scroll; editor admin dan Pratinjau tampil sama dengan mentee; tanpa error konsol.

### Tahap E

- `npm run build`: `public/build` (CSS baru + file font Plus Jakarta Sans, Nunito, Lora) di-commit.
- README: baris fitur Inti Materi dan alur editor modul diperbarui. AGENTS.md dan `docs/database.md` sudah diperbarui di Tahap B, C, dan A.
- `composer test` lulus: Pint, PHPStan level 7 (0 error), 854 test Pest.
- Tidak ada `migrate`/`seed` ke DB `.env` (produksi). Migration diuji di SQLite sandbox (scratchpad) lewat override env proses.

## Risiko

- `composer audit` menemukan 18 advisory di 11 paket yang terkunci (antara lain `livewire/livewire` medium, `symfony/mime` medium, `symfony/http-foundation`, `symfony/html-sanitizer`). Advisory html-sanitizer (srcset, meta refresh, `allowLinkHosts`) tidak mengenai konfigurasi kita. Pembaruan dependensi di luar lingkup tugas ini.
- Span yang di-paste tanpa `class="color"` kehilangan warnanya saat disimpan dari editor (parser PHP Filament mensyaratkan kelas itu). Warna yang diberi lewat tombol editor selalu punya kelas itu.
- `payload.max_size` Livewire tetap 1 MB. Dokumen TipTap JSON lebih boros daripada HTML; modul dengan sangat banyak kolom panjang bisa mendekati batas ini.

## Sisa pekerjaan

- Jalankan migration di production saat deploy (`php artisan migrate`, additive).
- Setelah deploy, buka satu modul lama di admin dan simpan untuk memastikan konversi Markdown ke HTML sesuai harapan tim kurikulum.
