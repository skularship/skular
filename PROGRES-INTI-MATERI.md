# Progres: Inti Materi WYSIWYG + tampilan dokumen

Catatan kerja untuk perubahan editor Inti Materi (Filament RichEditor) dan tampilan Inti Materi di langkah Pahami. Diperbarui setiap tahap selesai.

## Status tahap

| Tahap | Isi | Status |
|---|---|---|
| A | Data: `module_core_sections.body_format`, model, factory, helper body kosong | Selesai |
| B | Renderer: jalur HTML tersanitasi, prop `format`, test keamanan | Selesai |
| C | Admin: RichEditor, whitelist warna, plugin font, konversi Markdown, Pratinjau | Belum |
| D | Frontend: lembar dokumen di Pahami, `prose.css` baru, daftar isi sticky | Belum |
| E | Finishing: build aset, dokumentasi, `composer test` | Belum |

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

## Risiko

- `composer audit` menemukan 18 advisory di 11 paket yang terkunci (antara lain `livewire/livewire` medium, `symfony/mime` medium, `symfony/http-foundation`, `symfony/html-sanitizer`). Advisory html-sanitizer (srcset, meta refresh, `allowLinkHosts`) tidak mengenai konfigurasi kita. Pembaruan dependensi di luar lingkup tugas ini.

## Sisa pekerjaan

Tahap C sampai E.
