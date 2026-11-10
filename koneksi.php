<?php
// ==========================================================
// FILE: koneksi.php
// Deskripsi: Koneksi PHP ke Database MySQL (db_hobas)
// ==========================================================

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_hobas";

// 1. Koneksi ke server MySQL
$koneksi = mysqli_connect($host, $user, $pass);

if (!$koneksi) {
    die("Koneksi MySQL gagal: " . mysqli_connect_error());
}

// 2. Buat database db_hobas jika belum ada
mysqli_query($koneksi, "CREATE DATABASE IF NOT EXISTS $db");
mysqli_select_db($koneksi, $db);

// 3. Auto-Setup: Jika tabel produk belum memiliki kolom 'gambar', jalankan database.sql otomatis
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM produk LIKE 'gambar'");
if (!$cek_kolom || mysqli_num_rows($cek_kolom) == 0) {
    if (file_exists(__DIR__ . '/database.sql')) {
        $sql_awal = file_get_contents(__DIR__ . '/database.sql');
        if (mysqli_multi_query($koneksi, $sql_awal)) {
            do {
                if ($hasil = mysqli_store_result($koneksi)) {
                    mysqli_free_result($hasil);
                }
            } while (mysqli_more_results($koneksi) && mysqli_next_result($koneksi));
        }
    }
}

// 4. Pastikan kolom 'stok' ada di tabel produk
$cek_stok = mysqli_query($koneksi, "SHOW COLUMNS FROM produk LIKE 'stok'");
if ($cek_stok && mysqli_num_rows($cek_stok) == 0) {
    mysqli_query($koneksi, "ALTER TABLE produk ADD COLUMN stok INT(11) NOT NULL DEFAULT 25 AFTER harga");
}

// 5. Bersihkan kolom 'bukti_bayar' dari tabel transaksi jika masih ada sisa sebelumnya
$cek_bukti = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi LIKE 'bukti_bayar'");
if ($cek_bukti && mysqli_num_rows($cek_bukti) > 0) {
    mysqli_query($koneksi, "ALTER TABLE transaksi DROP COLUMN bukti_bayar");
}
?>
