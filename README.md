# Inventory Management System

Aplikasi web untuk mengelola stok barang di sebuah toko. Dibuat untuk Mini Project 3 mata kuliah Pemrograman Web.

Dibuat oleh: Naila Amelia

## Teknologi

- PHP (PDO)
- MySQL (XAMPP)
- HTML dan CSS

## Cara Menjalankan

1. Salin folder `inventory-app` ke `htdocs` di XAMPP.
2. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
3. Buka phpMyAdmin, buat database bernama `inventory_db`.
4. Jalankan isi file `database/schema.sql` di tab SQL.
5. Buat satu akun admin di tabel `users` (password disimpan dalam bentuk hash).
6. Buka `http://localhost/inventory-app/public/login.php`.

## Business Rule

1. SKU produk harus unik. Kalau SKU sudah dipakai, produk ditolak.
2. Harga dan jumlah barang tidak boleh negatif.
3. Barang keluar tidak boleh melebihi stok yang tersedia.
4. Stok tidak boleh menjadi negatif.
5. Setiap barang masuk dan keluar dicatat di tabel `stock_movements`, lengkap dengan waktu dan user yang mencatat.
6. Perubahan stok dan pencatatan riwayat dilakukan dalam satu transaction. Kalau salah satu gagal, semuanya dibatalkan (rollback).
7. Produk yang sudah punya riwayat barang masuk atau keluar tidak bisa dihapus.
8. Kategori yang masih dipakai produk tidak bisa dihapus.
9. Semua halaman internal membutuhkan login.
10. Hanya admin yang boleh menghapus kategori dan produk, serta membuka halaman Kelola User. Staff ditolak dengan pesan "Akses ditolak".
11. Pengurutan daftar produk hanya memakai pilihan yang diizinkan (nama, harga, stok).

## Fitur

- **Login dan logout** dengan password yang di-hash.
- **Role admin dan staff**, dengan akses yang berbeda.
- **Kategori**: lihat, tambah, ubah, hapus.
- **Produk**: lihat, tambah, ubah, hapus.
- **Pencarian** produk berdasarkan nama atau SKU.
- **Filter** produk berdasarkan kategori.
- **Pengurutan** produk berdasarkan nama, harga, atau stok.
- **Pagination** daftar produk.
- **Barang masuk dan keluar** dengan transaction.
- **Dashboard** berisi ringkasan jumlah produk, jumlah kategori, total stok, dan daftar produk yang stoknya menipis.

## Screenshot

### Login
![Login](screenshots/01-login.png)

### Dashboard
![Dashboard](screenshots/02-dashboard.png)

### Kategori
![Kategori](screenshots/03-kategori.png)

### Produk (pencarian, filter, sorting, pagination)
![Produk](screenshots/04-produk.png)

### Barang Masuk dan Keluar
![Stok](screenshots/05-stok.png)

### Validation Error (stok tidak cukup)
![Validation Error](screenshots/06-validation-error.png)

### Akses Terlarang (staff membuka halaman admin)
![Akses Terlarang](screenshots/07-akses-terlarang.png)