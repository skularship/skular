# Refund manual (admin)

Skular belum punya alur refund di aplikasi (tidak ada tombol refund di Filament, tidak ada status `refunded` di `OrderStatus`). Sampai fitur itu dibangun sebagai pekerjaan terpisah, refund dikerjakan manual dengan langkah di bawah. Aturan kapan refund diberikan ada di halaman `/kebijakan-pengembalian-dana` (`resources/views/legal/refund.blade.php`, nilai dari `config/legal.php` bagian `refund`).

## 1. Terima dan cek permintaan

1. Permintaan masuk lewat email bantuan (`LEGAL_CONTACT_EMAIL`) atau tiket di *Pemantauan Mentee → Tiket bantuan*.
2. Cari pesanan di *Pembayaran → Pesanan* dengan nomor `SKL-...` dan cocokkan email akun.
3. Cek status `paid`, tanggal bayar, dan notifikasi terkait di *Pembayaran → Notifikasi Midtrans*.
4. Untuk refund "berubah pikiran" dalam jendela waktu: pastikan Pro belum dipakai sejak tanggal bayar (tidak ada `vault_documents` baru dan tidak ada `checklist_items` yang dibuat/diubah sesudah tanggal bayar).
5. Balas mentee dalam batas `legal.refund.review_days` hari kerja.

## 2. Kembalikan dana

- **Kartu kredit, e-wallet (GoPay, ShopeePay, DANA, OVO), QRIS, Kredivo, Akulaku** dengan status Settlement: Midtrans Dashboard → Transactions → detail transaksi → tombol *Refund*, isi nominal dan alasan. Batas waktu berbeda per metode (contoh dari dokumentasi Midtrans: QRIS off-us 7 hari, GoPay QRIS on-us 45 hari, DANA 45 hari, OVO 7 hari). ShopeePay dan OVO tidak mendukung refund sebagian. Bila tombol refund belum aktif, ajukan aktivasi lewat form prosedur refund di situs Midtrans.
- **Virtual account, transfer bank, gerai retail**, atau metode di atas yang sudah lewat batas: transfer manual dari rekening bisnis ke rekening yang diberikan mentee. Simpan bukti transfer.

Sumber: docs.midtrans.com/reference/refund-transaction dan docs.midtrans.com/docs/how-can-i-refund-transaction (dibaca 16 Nov 2026).

## 3. Sesuaikan akses Pro

Tidak ada aksi otomatis. Di *Pembayaran → Langganan*, ubah `active_until` mentee:

- refund penuh: kurangi `active_until` sebanyak `duration_days` produk pesanan itu (bila hasilnya sudah lewat, Pro berakhir);
- refund sebagian: kurangi sebanding nominal yang dikembalikan.

Jangan menghapus pesanan, langganan, atau data belajar mentee. Catat nomor pesanan, nominal, tanggal, dan cara refund di catatan admin tiket.

## 4. Pekerjaan lanjutan (belum dibangun)

Status `refunded` di `OrderStatus`, aksi refund di Filament yang memanggil Refund API Midtrans, penanganan notifikasi refund Midtrans (pakai notifikasi yang memuat `bank_confirmed_at`), dan penyesuaian `subscriptions` otomatis.
