# Technical SEO Skular

Dokumen ini menjelaskan implementasi SEO yang aktif di repository dan langkah
manual yang masih dibutuhkan setelah deploy. Implementasi ini belum membuktikan
bahwa Google sudah mengindeks website: indexing baru terjadi setelah crawler
menemukan dan memproses halaman.

## Peta implementasi

| Komponen | Sumber |
|---|---|
| Domain canonical | `config/seo.php` → `seo.url` (dari `SEO_CANONICAL_URL` atau `APP_URL`) |
| Helper URL | `app/Support/Seo.php` |
| Meta tags, canonical, OG, X Card | `resources/views/partials/head.blade.php` |
| Sitemap | `app/Http/Controllers/SitemapController.php` + `resources/views/sitemap.blade.php` |
| Robots | `public/robots.txt` |
| Social preview 1200 × 630 | `public/images/og-skular.jpg` |
| Structured data | JSON-LD `Organization` + `WebSite` di `resources/views/welcome.blade.php` |

Tidak ada sistem SEO kedua: semua layout memakai partial `partials/head`, dan
tidak ada middleware atau `X-Robots-Tag` yang menambahkan aturan robots lain.

## Kebijakan indexing

| Halaman | Kebijakan | Alasan |
|---|---|---|
| `/` | `index, follow`, ada di sitemap | Landing page publik |
| `/faq` | `index, follow`, ada di sitemap | Konten publik dari FAQ terbit |
| `/login`, `/register`, reset password, 2FA | `noindex, follow` | Bukan halaman untuk hasil pencarian |
| `/dashboard`, `/profiling`, `/library`, `/cv`, `/scholarships*`, `/modules/*`, `/settings/*` | `noindex, nofollow` | Butuh sesi; authorization tidak diubah |
| `/admin` | Tidak di-crawl (`Disallow: /admin`) | Panel internal Filament |
| `/health` | Tidak di sitemap | Endpoint operasional |
| 404 | `noindex, follow` | Halaman error |

Halaman privat tetap dilindungi middleware `auth`/`verified`; robots dan noindex
hanya mengatur crawling/indexing, bukan pengganti authorization.

## Sitemap

- URL: `https://skularship.com/sitemap.xml`
- `Content-Type: application/xml; charset=UTF-8`
- Isi saat ini: `/` dan `/faq` (URL absolut, tanpa query, tanpa duplikat)
- `lastmod` berasal dari waktu ubah nyata: `filemtime` view landing dan
  `updated_at` FAQ terbit terbaru. Tidak memakai waktu akses sitemap.
- Bila URL publik bertambah melewati batas 50.000 URL per file, pecah menjadi
  sitemap index (`/sitemap.xml` → daftar `/sitemap-*.xml`).

## Robots

```
User-agent: *
Allow: /

Disallow: /admin

Sitemap: https://skularship.com/sitemap.xml
```

CSS, JavaScript, gambar, dan font tidak diblokir. `/livewire` juga tidak
diblokir karena aset Livewire dilayani dari path tersebut.

## Variabel environment

```
APP_URL=https://skularship.com
SEO_CANONICAL_URL=          # opsional; kosong berarti memakai APP_URL
```

Host loopback (`localhost`, `127.0.0.1`) tidak pernah dipakai untuk canonical,
sitemap, atau OG. Pastikan `APP_URL` production memakai HTTPS.

## Google Search Console (langkah manual pemilik domain)

1. **Tambah properti.** Buka <https://search.google.com/search-console> →
   *Add property*. Gunakan **Domain property** (`skularship.com`) bila DNS bisa
   diubah, atau **URL prefix** (`https://skularship.com/`) bila cukup verifikasi
   file/meta.
2. **Verifikasi kepemilikan.**
   - Domain property: tambahkan TXT record DNS yang diberikan Google.
   - URL prefix: unduh file HTML verifikasi ke `public/` lalu deploy, atau
     gunakan tag meta di `partials/head` sementara.
   - Jangan commit token atau credential verifikasi ke repository.
3. **Kirim sitemap.** Menu *Sitemaps* → masukkan `sitemap.xml` → *Submit*.
   Status awal biasanya "Couldn't fetch" selama beberapa jam.
4. **Periksa status sitemap.** *Sitemaps* menampilkan status *Success*, jumlah
   URL ditemukan, dan waktu terakhir dibaca. Error umum: 404, 5xx, atau
   `Content-Type` bukan XML.
5. **URL Inspection.** Masukkan `https://skularship.com/` → *Test live URL*
   untuk memastikan HTML awal berisi title, description, canonical, dan
   `index, follow`. Gunakan *Request indexing* hanya untuk halaman yang penting
   dan sudah final.
6. **Periksa masalah indexing.** Menu *Pages* memisahkan *Indexed*,
   *Not indexed*, dan alasan (mis. *Excluded by 'noindex'*, *Crawled - currently
   not indexed*, redirect, 404). Halaman privat memang seharusnya muncul sebagai
   *Excluded by 'noindex'*.
7. **Pantau performa.** Menu *Performance*: query, halaman, negara, dan device.
   Landing page adalah target utama; tambahkan halaman FAQ sebagai pembanding.

## Yang masih manual setelah deploy

- Set `APP_URL` production (HTTPS) dan opsional `SEO_CANONICAL_URL`.
- Verifikasi domain dan submit sitemap di Search Console.
- Pastikan server mengembalikan 200 untuk `/robots.txt`, `/sitemap.xml`, dan
  `/images/og-skular.jpg` tanpa autentikasi.
- Bila domain berubah, perbarui `public/robots.txt` (satu-satunya file statis
  yang memuat domain) dan jalankan ulang test SEO.
- Uji preview social via WhatsApp/Facebook/LinkedIn debugger setelah deploy.
