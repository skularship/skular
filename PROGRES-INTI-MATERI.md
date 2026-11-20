# Progres: Inti Materi WYSIWYG + tampilan dokumen

Catatan kerja untuk perubahan editor Inti Materi (Filament RichEditor) dan tampilan Inti Materi di langkah Pahami. Diperbarui setiap tahap selesai.

## Status tahap

| Tahap | Isi | Status |
|---|---|---|
| A | Data: `module_core_sections.body_format`, model, factory, helper body kosong | Belum |
| B | Renderer: jalur HTML tersanitasi, prop `format`, test keamanan | Belum |
| C | Admin: RichEditor, whitelist warna, plugin font, konversi Markdown, Pratinjau | Belum |
| D | Frontend: lembar dokumen di Pahami, `prose.css` baru, daftar isi sticky | Belum |
| E | Finishing: build aset, dokumentasi, `composer test` | Belum |

## Keputusan teknis

- Plugin font family memakai TipTap milik Filament yang diekspos di `window.FilamentRichEditor.tiptap` (tanpa paket npm baru, tanpa bundler).
- Whitelist warna dan font disimpan di satu tempat: `config/content.php`.
- Sanitizer membuang `style` yang tersimpan, lalu menyusun ulang `style` hanya dari `data-color` / `data-font` (atau nilai `style` yang persis ada di whitelist).
- AGENTS.md diperbarui di commit yang sama dengan perubahan perilaku (Tahap B), bukan menunggu Tahap E.

## Sisa pekerjaan

Semua tahap.
