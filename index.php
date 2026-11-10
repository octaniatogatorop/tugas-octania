<?php
// ==========================================================
// FILE: index.php
// Deskripsi: Halaman Utama POS "Hobas Coffee & Resto"
//            Dilengkapi Penggunaan Stok Produk
//            Memiliki Sidebar Buka-Tutup dengan 4 Menu:
//            1) ?menu=dashboard : Selamat Datang, Total Harian/Mingguan/Bulanan, Penjualan Terakhir
//            2) ?menu=produk    : Tambah/Kelola Produk (Nama Produk, Harga, Stok, Gambar)
//            3) ?menu=kasir     : Search Produk, Kartu Produk + Stok, Detail Pesanan & Checkout Struk Roll
//            4) ?menu=laporan   : Laporan Penjualan (Harian / Mingguan / Bulanan) + Export PDF
// ==========================================================

session_start();
include 'koneksi.php';

// Kompatibilitas session otomatis
if (!isset($_SESSION['pos_login']) && isset($_SESSION['udah_login'])) {
    $_SESSION['pos_login']  = true;
    $_SESSION['id_kasir']   = 1;
    $_SESSION['nama_kasir'] = 'Kasir Hobas';
    $_SESSION['username']   = 'kasir';
}

// Proteksi Login: Jika belum login, arahkan ke login.php
if (!isset($_SESSION['pos_login'])) {
    header("Location: login.php");
    exit;
}

// Menentukan menu yang sedang aktif (Default: dashboard)
$menu_aktif = isset($_GET['menu']) ? $_GET['menu'] : 'dashboard';
if (!in_array($menu_aktif, ['dashboard', 'produk', 'kasir', 'laporan'])) {
    $menu_aktif = 'dashboard';
}

// Menghitung Total Penjualan Harian, Mingguan, dan Bulanan
$q_harian = mysqli_query($koneksi, "SELECT SUM(total_harga) AS total FROM transaksi WHERE DATE(tanggal) = CURDATE()");
$d_harian = mysqli_fetch_assoc($q_harian);
$total_harian = (int) $d_harian['total'];

$q_mingguan = mysqli_query($koneksi, "SELECT SUM(total_harga) AS total FROM transaksi WHERE DATE(tanggal) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$d_mingguan = mysqli_fetch_assoc($q_mingguan);
$total_mingguan = (int) $d_mingguan['total'];

$q_bulanan = mysqli_query($koneksi, "SELECT SUM(total_harga) AS total FROM transaksi WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())");
$d_bulanan = mysqli_fetch_assoc($q_bulanan);
$total_bulanan = (int) $d_bulanan['total'];

// Menghitung jumlah item di keranjang kasir
$jml_keranjang = 0;
if (!empty($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $k) {
        $jml_keranjang += $k['qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hobas Coffee & Resto - Sistem POS Kasir</title>
    <style>
        /* ==========================================================
           CSS SEDERHANA, BERSIH, & FULL-WIDTH RESPONSIVE
           ========================================================== */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f4;
            color: #1c1917;
            min-height: 100vh;
        }
        .app-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* 1. SIDEBAR BUKA-TUTUP (COLLAPSIBLE) */
        .sidebar {
            width: 255px;
            min-width: 255px;
            background: linear-gradient(180deg, #1c1917 0%, #451a03 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            overflow: hidden;
            z-index: 1000;
        }
        .sidebar.closed {
            width: 0;
            min-width: 0;
        }
        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            white-space: nowrap;
        }
        .sidebar-header h2 {
            font-size: 17px;
            color: #fef3c7;
        }
        .btn-close-side {
            background: rgba(255, 255, 255, 0.12);
            border: none;
            color: #fff;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            cursor: pointer;
        }
        .sidebar-nav {
            padding: 16px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
            white-space: nowrap;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: #e7e5e4;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .nav-link.active {
            background: #d97706;
            color: #ffffff;
        }
        .sidebar-footer {
            padding: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            white-space: nowrap;
        }
        .btn-logout {
            display: block;
            text-align: center;
            padding: 10px;
            background: #dc2626;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: bold;
        }

        /* 2. AREA UTAMA KANAN */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
        }
        .topbar {
            background: #ffffff;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e7e5e4;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-toggle {
            background: #f5f5f4;
            border: 1px solid #d6d3d1;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
        }
        .content-area {
            padding: 24px;
            width: 100%;
        }

        /* 3. KOMPONEN BANNER, KARTU, & GRID */
        .welcome-box {
            background: linear-gradient(120deg, #451a03 0%, #b45309 100%);
            color: #ffffff;
            padding: 26px 30px;
            border-radius: 12px;
            margin-bottom: 22px;
        }
        .welcome-box h2 {
            font-size: 24px;
            margin-bottom: 6px;
            color: #fef3c7;
        }
        .welcome-box p {
            font-size: 14px;
            color: #ffedd5;
        }

        /* 3 Kartu Penjualan (Harian, Mingguan, Bulanan) */
        .stats-3-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }
        .stat-card {
            background: #ffffff;
            padding: 20px 22px;
            border-radius: 10px;
            border: 1px solid #e7e5e4;
            border-left: 5px solid #d97706;
        }
        .stat-card.hijau { border-left-color: #16a34a; }
        .stat-card.biru  { border-left-color: #2563eb; }
        .stat-card span {
            display: block;
            font-size: 12px;
            font-weight: bold;
            color: #78716c;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .stat-card h3 {
            font-size: 24px;
            color: #1c1917;
        }

        .card {
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 10px;
            border: 1px solid #e7e5e4;
            margin-bottom: 20px;
        }
        .card h3 {
            font-size: 17px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f5f5f4;
        }

        /* Grid Halaman Menu & Kasir */
        .grid-menu-page {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            align-items: start;
        }
        .grid-kasir-page {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 20px;
            align-items: start;
        }

        /* Kartu Produk Bergambar */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
            gap: 14px;
        }
        .product-card {
            border: 1px solid #e7e5e4;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .product-card img {
            width: 100%;
            height: 135px;
            object-fit: cover;
            background: #fef3c7;
            border-bottom: 1px solid #f5f5f4;
        }
        .product-body {
            padding: 12px;
        }
        .product-title {
            font-size: 14.5px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .product-price {
            font-size: 14px;
            font-weight: bold;
            color: #b45309;
            margin-bottom: 4px;
        }
        .product-stock {
            font-size: 12px;
            color: #57534e;
            margin-bottom: 10px;
        }

        /* Badge Stok & Status */
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 12px;
            font-size: 11.5px;
            font-weight: bold;
        }
        .badge-hijau  { background: #dcfce7; color: #15803d; }
        .badge-merah  { background: #fee2e2; color: #b91c1c; }
        .badge-kuning { background: #fef3c7; color: #b45309; }

        .notif {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13.5px;
            font-weight: bold;
        }
        .notif-hijau  { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .notif-kuning { background: #fef3c7; color: #b45309; border: 1px solid #fde047; }

        /* Form & Tombol */
        .form-group {
            margin-bottom: 14px;
        }
        label {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="number"], input[type="file"] {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #d6d3d1;
            border-radius: 7px;
            font-size: 14px;
        }
        .btn {
            background: #d97706;
            color: #ffffff;
            border: none;
            padding: 9px 16px;
            border-radius: 7px;
            font-size: 13.5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn:hover { background: #b45309; }
        .btn-hijau { background: #16a34a; }
        .btn-hijau:hover { background: #15803d; }
        .btn-merah { background: #dc2626; }
        .btn-gelap { background: #1c1917; }
        .btn-full  { width: 100%; }
        .btn-sm    { padding: 6px 10px; font-size: 12px; }

        /* Tabel */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        th, td {
            border-bottom: 1px solid #e7e5e4;
            padding: 11px 12px;
            text-align: left;
        }
        th {
            background: #fafaf9;
            font-weight: bold;
            border-top: 1px solid #e7e5e4;
        }
        .thumb-img {
            width: 55px;
            height: 42px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e7e5e4;
        }

        /* Tab Periode Laporan */
        .tab-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 9px 16px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: bold;
            background: #e7e5e4;
            color: #1c1917;
        }
        .tab-btn.active {
            background: #d97706;
            color: #ffffff;
        }

        @media (max-width: 950px) {
            .stats-3-grid { grid-template-columns: 1fr; }
            .grid-menu-page, .grid-kasir-page { grid-template-columns: 1fr; }
            .sidebar { position: fixed; top: 0; left: 0; bottom: 0; height: 100vh; }
            .sidebar.closed { transform: translateX(-100%); width: 255px; min-width: 255px; }
        }
    </style>
</head>
<body>

<div class="app-layout">

    <!-- ==========================================================
         SIDEBAR NAVIGASI (4 MENU SEDERHANA)
         ========================================================== -->
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-header">
            <h2>☕ Hobas Coffee</h2>
            <button class="btn-close-side" onclick="toggleSidebar()">✕</button>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php?menu=dashboard" class="nav-link <?php echo ($menu_aktif == 'dashboard') ? 'active' : ''; ?>">
                <span>📊</span>
                <span>Dashboard</span>
            </a>

            <a href="index.php?menu=produk" class="nav-link <?php echo ($menu_aktif == 'produk') ? 'active' : ''; ?>">
                <span>🍔</span>
                <span>Menu Produk</span>
            </a>

            <a href="index.php?menu=kasir" class="nav-link <?php echo ($menu_aktif == 'kasir') ? 'active' : ''; ?>">
                <span>🛒</span>
                <span>Kasir (<?php echo $jml_keranjang; ?>)</span>
            </a>

            <a href="index.php?menu=laporan" class="nav-link <?php echo ($menu_aktif == 'laporan') ? 'active' : ''; ?>">
                <span>📄</span>
                <span>Laporan</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar?')">Logout Kasir</a>
        </div>
    </aside>

    <!-- ==========================================================
         AREA KONTEN UTAMA
         ========================================================== -->
    <div class="main-wrapper">

        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="btn-toggle" onclick="toggleSidebar()" title="Buka / Tutup Sidebar">☰</button>
                <strong>Sistem POS Kasir — Hobas Coffee & Resto</strong>
            </div>
            <div>
                <span>Kasir: <strong><?php echo htmlspecialchars($_SESSION['nama_kasir']); ?></strong></span>
            </div>
        </header>

        <main class="content-area">

            <?php
            // =========================================================================
            // 1. MENU DASHBOARD (?menu=dashboard)
            // =========================================================================
            if ($menu_aktif == 'dashboard') {
            ?>
                <!-- Ucapan Selamat Datang -->
                <div class="welcome-box">
                    <h2>Selamat Datang di Hobas Coffee & Resto! ☕</h2>
                    <p>Kelola menu makanan & minuman, stok produk, transaksi kasir, dan laporan penjualan dengan mudah.</p>
                </div>

                <!-- Total Penjualan Harian, Mingguan, dan Bulanan -->
                <div class="stats-3-grid">
                    <div class="stat-card hijau">
                        <span>Total Penjualan Harian (Hari Ini)</span>
                        <h3>Rp <?php echo number_format($total_harian, 0, ',', '.'); ?></h3>
                    </div>

                    <div class="stat-card biru">
                        <span>Total Penjualan Mingguan (7 Hari)</span>
                        <h3>Rp <?php echo number_format($total_mingguan, 0, ',', '.'); ?></h3>
                    </div>

                    <div class="stat-card">
                        <span>Total Penjualan Bulanan (Bulan Ini)</span>
                        <h3>Rp <?php echo number_format($total_bulanan, 0, ',', '.'); ?></h3>
                    </div>
                </div>

                <!-- Daftar Penjualan Terakhir -->
                <div class="card">
                    <h3>🧾 Daftar Penjualan Terakhir</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Struk</th>
                                    <th>Tanggal & Waktu</th>
                                    <th>Total Harga</th>
                                    <th>Uang Bayar</th>
                                    <th>Kembalian</th>
                                    <th>Struk</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q_terakhir = mysqli_query($koneksi, "SELECT * FROM transaksi ORDER BY id DESC LIMIT 10");
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($q_terakhir)) {
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo $row['no_nota']; ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    <td><strong>Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></strong></td>
                                    <td>Rp <?php echo number_format($row['uang_bayar'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($row['kembalian'], 0, ',', '.'); ?></td>
                                    <td>
                                        <a href="struk.php?id=<?php echo $row['id']; ?>" class="btn btn-sm">🖨️ Lihat Struk</a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php
            // =========================================================================
            // 2. MENU "MENU PRODUK" (?menu=produk)
            // =========================================================================
            } elseif ($menu_aktif == 'produk') {
                $edit_prd = null;
                if (isset($_GET['edit_id'])) {
                    $id_e = (int) $_GET['edit_id'];
                    $q_e  = mysqli_query($koneksi, "SELECT * FROM produk WHERE id = '$id_e'");
                    if (mysqli_num_rows($q_e) > 0) {
                        $edit_prd = mysqli_fetch_assoc($q_e);
                    }
                }
            ?>
                <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'sukses') { ?>
                    <div class="notif notif-hijau">✓ Data produk dan stok berhasil disimpan!</div>
                <?php } ?>

                <div class="grid-menu-page">
                    <!-- Kiri: Form Tambah / Edit Produk + Stok -->
                    <div class="card">
                        <h3><?php echo $edit_prd ? "✏️ Edit Produk & Stok" : "➕ Tambah Produk Baru"; ?></h3>
                        <form action="proses_daftar.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_edit" value="<?php echo $edit_prd ? $edit_prd['id'] : ''; ?>">
                            <input type="hidden" name="gambar_lama" value="<?php echo $edit_prd ? htmlspecialchars($edit_prd['gambar']) : ''; ?>">

                            <div class="form-group">
                                <label>Nama Produk</label>
                                <input type="text" name="nama_produk" placeholder="Contoh: Es Kopi Susu" value="<?php echo $edit_prd ? htmlspecialchars($edit_prd['nama_produk']) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Harga Produk (Rp)</label>
                                <input type="number" name="harga" placeholder="Contoh: 18000" value="<?php echo $edit_prd ? $edit_prd['harga'] : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Stok Produk (Porsi / Cup)</label>
                                <input type="number" name="stok" placeholder="Contoh: 25" value="<?php echo $edit_prd ? $edit_prd['stok'] : '25'; ?>" min="0" required>
                            </div>

                            <div class="form-group">
                                <label>Gambar Produk <?php echo $edit_prd ? '<small>(Kosongkan jika tidak diganti)</small>' : ''; ?></label>
                                <input type="file" name="gambar" accept="image/*">
                            </div>

                            <button type="submit" name="simpan_produk" class="btn btn-full">
                                <?php echo $edit_prd ? "Simpan Perubahan" : "Simpan Produk"; ?>
                            </button>
                            <?php if ($edit_prd) { ?>
                                <a href="index.php?menu=produk" class="btn btn-gelap btn-full" style="margin-top: 8px;">Batal</a>
                            <?php } ?>
                        </form>
                    </div>

                    <!-- Kanan: Tabel Daftar Produk + Stok -->
                    <div class="card">
                        <h3>📋 Daftar Menu & Stok Hobas Coffee & Resto</h3>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Gambar</th>
                                        <th>Nama Produk</th>
                                        <th>Harga</th>
                                        <th>Stok Tersedia</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $q_prd = mysqli_query($koneksi, "SELECT * FROM produk ORDER BY id DESC");
                                    $no = 1;
                                    while ($p = mysqli_fetch_assoc($q_prd)) {
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><img src="<?php echo htmlspecialchars($p['gambar']); ?>" class="thumb-img" alt="Menu"></td>
                                        <td><strong><?php echo htmlspecialchars($p['nama_produk']); ?></strong></td>
                                        <td>Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($p['stok'] > 5) { ?>
                                                <span class="badge badge-hijau"><?php echo $p['stok']; ?> Tersedia</span>
                                            <?php } elseif ($p['stok'] > 0) { ?>
                                                <span class="badge badge-kuning">Sisa <?php echo $p['stok']; ?></span>
                                            <?php } else { ?>
                                                <span class="badge badge-merah">Stok Habis (0)</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <a href="index.php?menu=produk&edit_id=<?php echo $p['id']; ?>" class="btn btn-sm">Edit / Isi Stok</a>
                                            <a href="aksi.php?aksi=hapus_produk&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-merah" onclick="return confirm('Hapus produk ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php
            // =========================================================================
            // 3. MENU KASIR (?menu=kasir)
            // =========================================================================
            } elseif ($menu_aktif == 'kasir') {
                $cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
            ?>
                <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'batas_stok') { ?>
                    <div class="notif notif-kuning">⚠️ Jumlah di keranjang sudah mencapai batas maksimal stok yang tersedia!</div>
                <?php } elseif (isset($_GET['pesan']) && $_GET['pesan'] == 'stok_habis') { ?>
                    <div class="notif notif-kuning">⚠️ Maaf, stok produk tersebut sedang habis!</div>
                <?php } ?>

                <div class="grid-kasir-page">
                    <!-- KIRI: SEARCH PRODUK & DAFTAR PRODUK BERGAMBAR + STOK -->
                    <div class="card">
                        <h3>☕ Pilih Menu Produk</h3>

                        <!-- Form Search Produk -->
                        <form action="index.php" method="GET" style="display: flex; gap: 8px; margin-bottom: 16px;">
                            <input type="hidden" name="menu" value="kasir">
                            <input type="text" name="cari" placeholder="Cari nama produk..." value="<?php echo htmlspecialchars($cari); ?>">
                            <button type="submit" class="btn btn-gelap">Cari</button>
                            <?php if ($cari != '') { ?>
                                <a href="index.php?menu=kasir" class="btn btn-merah">Reset</a>
                            <?php } ?>
                        </form>

                        <!-- Grid Kartu Produk -->
                        <div class="product-grid">
                            <?php
                            $sql_cari = ($cari != '') ? "WHERE nama_produk LIKE '%$cari%'" : "";
                            $q_katalog = mysqli_query($koneksi, "SELECT * FROM produk $sql_cari ORDER BY id DESC");
                            while ($item = mysqli_fetch_assoc($q_katalog)) {
                            ?>
                            <div class="product-card">
                                <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_produk']); ?>">
                                <div class="product-body">
                                    <div class="product-title"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                                    <div class="product-price">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></div>
                                    <div class="product-stock">
                                        Stok: <strong><?php echo $item['stok']; ?></strong>
                                    </div>

                                    <?php if ($item['stok'] > 0) { ?>
                                        <a href="aksi.php?aksi=tambah_item&id=<?php echo $item['id']; ?>&cari=<?php echo urlencode($cari); ?>" class="btn btn-full btn-sm">
                                            + Tambah
                                        </a>
                                    <?php } else { ?>
                                        <button class="btn btn-merah btn-full btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">
                                            Stok Habis
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- KANAN: DETAIL PESANAN & CHECKOUT STRUK ROLL -->
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <h3 style="margin:0; border:none; padding:0;">🛒 Detail Pesanan</h3>
                            <?php if (!empty($_SESSION['keranjang'])) { ?>
                                <a href="aksi.php?aksi=kosongkan" class="btn btn-sm btn-merah">Kosongkan</a>
                            <?php } ?>
                        </div>

                        <?php
                        $total_tagihan = 0;
                        if (empty($_SESSION['keranjang'])) {
                            echo "<p style='padding: 30px 0; text-align: center; color: #78716c;'>Belum ada produk dipilih.<br>Klik tombol <b>+ Tambah</b> pada produk di sebelah kiri.</p>";
                        } else {
                        ?>
                        <div class="table-wrap" style="margin-bottom: 16px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($_SESSION['keranjang'] as $id_k => $k) {
                                        $sub = $k['harga'] * $k['qty'];
                                        $total_tagihan += $sub;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($k['nama_produk']); ?></strong><br>
                                            <small>Rp <?php echo number_format($k['harga'], 0, ',', '.'); ?></small>
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <a href="aksi.php?aksi=minus_item&id=<?php echo $id_k; ?>" class="btn btn-sm btn-gelap">-</a>
                                            <strong style="margin: 0 6px;"><?php echo $k['qty']; ?></strong>
                                            <a href="aksi.php?aksi=tambah_item&id=<?php echo $id_k; ?>" class="btn btn-sm btn-gelap">+</a>
                                        </td>
                                        <td><strong>Rp <?php echo number_format($sub, 0, ',', '.'); ?></strong></td>
                                        <td>
                                            <a href="aksi.php?aksi=hapus_item&id=<?php echo $id_k; ?>" style="color:#dc2626; font-weight:bold; text-decoration:none;">✕</a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Form Checkout -->
                        <form action="proses_daftar.php" method="POST">
                            <div style="background:#fffbeb; border:1px solid #fde68a; padding:12px 14px; border-radius:8px; margin-bottom:14px; display:flex; justify-content:space-between; font-size:17px; font-weight:bold; color:#b45309;">
                                <span>Total Harga:</span>
                                <span>Rp <?php echo number_format($total_tagihan, 0, ',', '.'); ?></span>
                            </div>

                            <div class="form-group">
                                <label>Uang Bayar (Rp)</label>
                                <input type="number" name="uang_bayar" id="uangBayar" value="<?php echo $total_tagihan; ?>" min="<?php echo $total_tagihan; ?>" oninput="hitungKembali()" required>
                            </div>

                            <div style="background:#f0fdf4; border:1px solid #86efac; padding:10px 14px; border-radius:8px; margin-bottom:14px; display:flex; justify-content:space-between; font-weight:bold; color:#15803d;">
                                <span>Kembalian:</span>
                                <span id="teksKembali">Rp 0</span>
                            </div>

                            <button type="submit" name="checkout_bayar" class="btn btn-hijau btn-full" style="padding: 12px; font-size: 15px;">
                                🖨️ Checkout & Terbitkan Struk
                            </button>
                        </form>
                        <?php } ?>
                    </div>
                </div>

            <?php
            // =========================================================================
            // 4. MENU LAPORAN (HARIAN / MINGGUAN / BULANAN + EXPORT PDF)
            // =========================================================================
            } elseif ($menu_aktif == 'laporan') {
                $periode = isset($_GET['periode']) ? $_GET['periode'] : 'harian';

                if ($periode == 'harian') {
                    $label_periode = "Laporan Harian (Hari Ini)";
                    $where_lap     = "WHERE DATE(tanggal) = CURDATE()";
                } elseif ($periode == 'mingguan') {
                    $label_periode = "Laporan Mingguan (7 Hari Terakhir)";
                    $where_lap     = "WHERE DATE(tanggal) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                } else {
                    $label_periode = "Laporan Bulanan (Bulan Ini)";
                    $where_lap     = "WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
                }
            ?>
                <div class="card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div class="tab-bar">
                        <a href="index.php?menu=laporan&periode=harian" class="tab-btn <?php echo ($periode == 'harian') ? 'active' : ''; ?>">Harian</a>
                        <a href="index.php?menu=laporan&periode=mingguan" class="tab-btn <?php echo ($periode == 'mingguan') ? 'active' : ''; ?>">Mingguan</a>
                        <a href="index.php?menu=laporan&periode=bulanan" class="tab-btn <?php echo ($periode == 'bulanan') ? 'active' : ''; ?>">Bulanan</a>
                    </div>

                    <a href="struk.php?export_pdf=1&periode=<?php echo $periode; ?>" class="btn btn-hijau">
                        📄 Export PDF / Cetak Laporan
                    </a>
                </div>

                <div class="card">
                    <h3>📊 <?php echo $label_periode; ?></h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Struk</th>
                                    <th>Tanggal & Waktu</th>
                                    <th>Detail Produk Terjual</th>
                                    <th>Total Harga</th>
                                    <th>Uang Bayar</th>
                                    <th>Kembalian</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q_lap = mysqli_query($koneksi, "SELECT * FROM transaksi $where_lap ORDER BY id DESC");
                                $no = 1;
                                $total_periode = 0;
                                while ($tr = mysqli_fetch_assoc($q_lap)) {
                                    $total_periode += $tr['total_harga'];
                                    $id_t = $tr['id'];
                                    $q_dt = mysqli_query($koneksi, "SELECT * FROM transaksi_detail WHERE id_transaksi = '$id_t'");
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo $tr['no_nota']; ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($tr['tanggal'])); ?></td>
                                    <td>
                                        <?php while ($dt = mysqli_fetch_assoc($q_dt)) { ?>
                                            • <?php echo $dt['qty']; ?>x <?php echo htmlspecialchars($dt['nama_produk']); ?><br>
                                        <?php } ?>
                                    </td>
                                    <td><strong>Rp <?php echo number_format($tr['total_harga'], 0, ',', '.'); ?></strong></td>
                                    <td>Rp <?php echo number_format($tr['uang_bayar'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($tr['kembalian'], 0, ',', '.'); ?></td>
                                    <td>
                                        <a href="struk.php?id=<?php echo $tr['id']; ?>" class="btn btn-sm">🖨️ Struk</a>
                                        <a href="aksi.php?aksi=hapus_transaksi&id=<?php echo $tr['id']; ?>" class="btn btn-sm btn-merah" onclick="return confirm('Hapus transaksi ini?')">Hapus</a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 16px; text-align: right; font-size: 18px; font-weight: bold; color: #15803d;">
                        Total Pendapatan: Rp <?php echo number_format($total_periode, 0, ',', '.'); ?>
                    </div>
                </div>
            <?php } ?>

        </main>
    </div>
</div>

<script>
    const sidebar = document.getElementById('appSidebar');
    const totalTagihan = <?php echo isset($total_tagihan) ? (int) $total_tagihan : 0; ?>;

    function toggleSidebar() {
        sidebar.classList.toggle('closed');
    }

    function hitungKembali() {
        const inputBayar = document.getElementById('uangBayar');
        const teksKembali = document.getElementById('teksKembali');
        if (!inputBayar || !teksKembali) return;

        let bayar = parseInt(inputBayar.value) || 0;
        let kembali = bayar - totalTagihan;
        teksKembali.innerText = 'Rp ' + (kembali > 0 ? kembali.toLocaleString('id-ID') : '0');
    }
</script>

</body>
</html>
