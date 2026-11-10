<?php
// ==========================================================
// FILE: proses_daftar.php
// Deskripsi: 1) Simpan / Edit Produk (Nama, Harga, Stok, Gambar)
//            2) Proses Checkout Kasir (Simpan Transaksi, Potong Stok & Struk Roll)
// ==========================================================

session_start();
include 'koneksi.php';

if (!isset($_SESSION['pos_login'])) {
    header("Location: login.php");
    exit;
}

// ==========================================================
// 1. PROSES TAMBAH / EDIT PRODUK (NAMA, HARGA, STOK, GAMBAR)
// ==========================================================
if (isset($_POST['simpan_produk'])) {
    $id_edit     = $_POST['id_edit'];
    $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $harga       = (int) $_POST['harga'];
    $stok        = (int) $_POST['stok'];
    $gambar_lama = mysqli_real_escape_string($koneksi, $_POST['gambar_lama']);

    // Default gunakan gambar lama (jika sedang edit) atau gambar kopi default
    $path_gambar = ($gambar_lama != '') ? $gambar_lama : 'uploads/kopi_susu.svg';

    // Cek apakah kasir mengupload file gambar produk baru
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        $nama_file_asli = basename($_FILES['gambar']['name']);
        $nama_file_baru = 'uploads/produk_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $nama_file_asli);

        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $nama_file_baru)) {
            $path_gambar = $nama_file_baru;
        }
    }

    if ($id_edit != '') {
        // Update produk yang diedit
        mysqli_query($koneksi, "UPDATE produk SET 
            nama_produk = '$nama_produk',
            harga       = '$harga',
            stok        = '$stok',
            gambar      = '$path_gambar'
            WHERE id = '$id_edit'");
    } else {
        // Tambah produk baru
        mysqli_query($koneksi, "INSERT INTO produk (nama_produk, harga, stok, gambar) 
            VALUES ('$nama_produk', '$harga', '$stok', '$path_gambar')");
    }

    header("Location: index.php?menu=produk&pesan=sukses");
    exit;
}

// ==========================================================
// 2. PROSES CHECKOUT KASIR -> POTONG STOK & TERBITKAN STRUK ROLL
// ==========================================================
if (isset($_POST['checkout_bayar'])) {
    if (empty($_SESSION['keranjang'])) {
        header("Location: index.php?menu=kasir");
        exit;
    }

    $uang_bayar = (int) $_POST['uang_bayar'];

    // Hitung total harga dari keranjang
    $total_harga = 0;
    foreach ($_SESSION['keranjang'] as $item) {
        $total_harga += ($item['harga'] * $item['qty']);
    }

    if ($uang_bayar < $total_harga) {
        $uang_bayar = $total_harga;
    }
    $kembalian = $uang_bayar - $total_harga;

    // Buat Nomor Nota otomatis (Contoh: HBS-004)
    $q_jml   = mysqli_query($koneksi, "SELECT COUNT(id) AS jml FROM transaksi");
    $d_jml   = mysqli_fetch_assoc($q_jml);
    $no_nota = "HBS-" . str_pad($d_jml['jml'] + 1, 3, '0', STR_PAD_LEFT);

    // 1. Simpan ke tabel transaksi
    $simpan_tx = mysqli_query($koneksi, "INSERT INTO transaksi (no_nota, tanggal, total_harga, uang_bayar, kembalian)
                                         VALUES ('$no_nota', NOW(), '$total_harga', '$uang_bayar', '$kembalian')");

    if ($simpan_tx) {
        $id_transaksi_baru = mysqli_insert_id($koneksi);

        // 2. Simpan rincian barang ke transaksi_detail & kurangi stok produk
        foreach ($_SESSION['keranjang'] as $item) {
            $id_p   = (int) $item['id'];
            $nama_p = mysqli_real_escape_string($koneksi, $item['nama_produk']);
            $hrg_p  = (int) $item['harga'];
            $qty_p  = (int) $item['qty'];
            $sub_p  = $hrg_p * $qty_p;

            mysqli_query($koneksi, "INSERT INTO transaksi_detail (id_transaksi, id_produk, nama_produk, harga, qty, subtotal)
                                    VALUES ('$id_transaksi_baru', '$id_p', '$nama_p', '$hrg_p', '$qty_p', '$sub_p')");

            // Kurangi stok produk sesuai jumlah yang terjual
            mysqli_query($koneksi, "UPDATE produk SET stok = GREATEST(0, stok - $qty_p) WHERE id = '$id_p'");
        }

        // Kosongkan keranjang
        $_SESSION['keranjang'] = [];

        // Langsung terbitkan struk kertas roll
        header("Location: struk.php?id=$id_transaksi_baru");
        exit;
    }
}

header("Location: index.php");
exit;
?>
