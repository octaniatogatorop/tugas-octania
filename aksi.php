<?php
// ==========================================================
// FILE: aksi.php
// Deskripsi: Menangani tombol Tambah Produk ke Keranjang Kasir
//            (dengan validasi stok), Ubah Qty (+ / -), dan Hapus Data
// ==========================================================

session_start();
include 'koneksi.php';

if (!isset($_SESSION['pos_login'])) {
    header("Location: login.php");
    exit;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// 1. KLIK TOMBOL "+ TAMBAH" PADA PRODUK DI HALAMAN KASIR (CEK STOK)
if ($aksi == 'tambah_item' && $id > 0) {
    $q_prd = mysqli_query($koneksi, "SELECT * FROM produk WHERE id = '$id'");
    $pesan_stok = '';

    if ($prd = mysqli_fetch_assoc($q_prd)) {
        $stok_tersedia = (int) $prd['stok'];

        if ($stok_tersedia > 0) {
            if (!isset($_SESSION['keranjang'])) {
                $_SESSION['keranjang'] = [];
            }

            // Jika produk sudah ada di keranjang, pastikan qty tidak melebihi stok
            if (isset($_SESSION['keranjang'][$id])) {
                if ($_SESSION['keranjang'][$id]['qty'] < $stok_tersedia) {
                    $_SESSION['keranjang'][$id]['qty']++;
                } else {
                    $pesan_stok = '&pesan=batas_stok';
                }
            } else {
                $_SESSION['keranjang'][$id] = [
                    'id'          => $prd['id'],
                    'nama_produk' => $prd['nama_produk'],
                    'harga'       => (int) $prd['harga'],
                    'stok'        => $stok_tersedia,
                    'gambar'      => $prd['gambar'],
                    'qty'         => 1
                ];
            }
        } else {
            $pesan_stok = '&pesan=stok_habis';
        }
    }

    $cari_balik = isset($_GET['cari']) && $_GET['cari'] != '' ? '&cari=' . urlencode($_GET['cari']) : '';
    header("Location: index.php?menu=kasir" . $cari_balik . $pesan_stok);
    exit;
}

// 2. KURANGI JUMLAH ITEM DI KERANJANG (-)
if ($aksi == 'minus_item' && $id > 0) {
    if (isset($_SESSION['keranjang'][$id])) {
        $_SESSION['keranjang'][$id]['qty']--;
        if ($_SESSION['keranjang'][$id]['qty'] <= 0) {
            unset($_SESSION['keranjang'][$id]);
        }
    }
    header("Location: index.php?menu=kasir");
    exit;
}

// 3. HAPUS 1 ITEM DARI KERANJANG (X)
if ($aksi == 'hapus_item' && $id > 0) {
    unset($_SESSION['keranjang'][$id]);
    header("Location: index.php?menu=kasir");
    exit;
}

// 4. KOSONGKAN SELURUH KERANJANG
if ($aksi == 'kosongkan') {
    $_SESSION['keranjang'] = [];
    header("Location: index.php?menu=kasir");
    exit;
}

// 5. HAPUS PRODUK DI MENU "MENU PRODUK"
if ($aksi == 'hapus_produk' && $id > 0) {
    mysqli_query($koneksi, "DELETE FROM produk WHERE id = '$id'");
    header("Location: index.php?menu=produk&pesan=hapus");
    exit;
}

// 6. HAPUS TRANSAKSI DI MENU "LAPORAN"
if ($aksi == 'hapus_transaksi' && $id > 0) {
    mysqli_query($koneksi, "DELETE FROM transaksi_detail WHERE id_transaksi = '$id'");
    mysqli_query($koneksi, "DELETE FROM transaksi WHERE id = '$id'");
    header("Location: index.php?menu=laporan");
    exit;
}

header("Location: index.php");
exit;
?>
