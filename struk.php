<?php
// ==========================================================
// FILE: struk.php
// Deskripsi: 1) Mencetak Struk Kertas Roll Thermal (Setelah Checkout)
//            2) Export PDF Laporan Penjualan (Harian / Mingguan / Bulanan)
// ==========================================================

session_start();
include 'koneksi.php';

if (!isset($_SESSION['pos_login'])) {
    header("Location: login.php");
    exit;
}

// ==========================================================
// MODE 1: EXPORT PDF LAPORAN (HARIAN / MINGGUAN / BULANAN)
// ==========================================================
if (isset($_GET['export_pdf'])) {
    $periode = isset($_GET['periode']) ? $_GET['periode'] : 'harian';

    if ($periode == 'harian') {
        $judul_periode = "LAPORAN PENJUALAN HARIAN (" . date('d M Y') . ")";
        $where_sql     = "WHERE DATE(tanggal) = CURDATE()";
    } elseif ($periode == 'mingguan') {
        $judul_periode = "LAPORAN PENJUALAN MINGGUAN (7 HARI TERAKHIR)";
        $where_sql     = "WHERE DATE(tanggal) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } else {
        $judul_periode = "LAPORAN PENJUALAN BULANAN (" . date('F Y') . ")";
        $where_sql     = "WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
    }

    $q_lap = mysqli_query($koneksi, "SELECT * FROM transaksi $where_sql ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $judul_periode; ?> - Hobas Coffee & Resto</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; padding: 30px; }
        .kop { text-align: center; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 20px; }
        .kop h2 { margin: 0 0 5px 0; }
        .kop p { margin: 0; font-size: 13px; color: #444; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 9px 12px; text-align: left; }
        th { background: #f3f4f6; }
        .total-box { width: 320px; margin-left: auto; border: 2px solid #111; padding: 12px 16px; font-size: 15px; font-weight: bold; display: flex; justify-content: space-between; }
        .no-print { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn { padding: 10px 16px; background: #b45309; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; border: none; cursor: pointer; font-size: 13px; }
        .btn-back { background: #1c1917; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">

<div class="no-print">
    <button onclick="window.print()" class="btn">📄 Simpan sebagai PDF / Cetak</button>
    <a href="index.php?menu=laporan&periode=<?php echo $periode; ?>" class="btn btn-back">← Kembali ke Laporan</a>
</div>

<div class="kop">
    <h2>HOBAS COFFEE & RESTO</h2>
    <p><strong><?php echo $judul_periode; ?></strong></p>
    <p>Dicetak pada: <?php echo date('d/m/Y H:i'); ?> | Kasir: <?php echo $_SESSION['nama_kasir']; ?></p>
</div>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>No. Nota</th>
            <th>Tanggal & Waktu</th>
            <th>Rincian Menu Terjual</th>
            <th>Uang Bayar</th>
            <th>Kembalian</th>
            <th>Total Harga</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        $grand_total = 0;
        while ($r = mysqli_fetch_assoc($q_lap)) {
            $grand_total += $r['total_harga'];
            $id_t = $r['id'];
            $q_d  = mysqli_query($koneksi, "SELECT * FROM transaksi_detail WHERE id_transaksi = '$id_t'");
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><strong><?php echo $r['no_nota']; ?></strong></td>
            <td><?php echo date('d/m/Y H:i', strtotime($r['tanggal'])); ?></td>
            <td>
                <?php while ($d = mysqli_fetch_assoc($q_d)) { ?>
                    • <?php echo $d['qty']; ?>x <?php echo $d['nama_produk']; ?> (Rp <?php echo number_format($d['subtotal'], 0, ',', '.'); ?>)<br>
                <?php } ?>
            </td>
            <td>Rp <?php echo number_format($r['uang_bayar'], 0, ',', '.'); ?></td>
            <td>Rp <?php echo number_format($r['kembalian'], 0, ',', '.'); ?></td>
            <td><strong>Rp <?php echo number_format($r['total_harga'], 0, ',', '.'); ?></strong></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<div class="total-box">
    <span>TOTAL PENDAPATAN:</span>
    <span>Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></span>
</div>

</body>
</html>
<?php
    exit;
}

// ==========================================================
// MODE 2: STRUK KERTAS ROLL THERMAL KASIR (?id=...)
// ==========================================================
$id_transaksi = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$q_tx = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE id = '$id_transaksi'");

if (mysqli_num_rows($q_tx) == 0) {
    header("Location: index.php?menu=kasir");
    exit;
}

$tx = mysqli_fetch_assoc($q_tx);
$q_detail = mysqli_query($koneksi, "SELECT * FROM transaksi_detail WHERE id_transaksi = '$id_transaksi'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Roll #<?php echo $tx['no_nota']; ?> - Hobas Coffee & Resto</title>
    <style>
        body {
            background-color: #d6d3d1;
            font-family: 'Courier New', Courier, monospace;
            padding: 30px 15px;
            color: #111;
        }
        .top-buttons {
            width: 310px;
            margin: 0 auto 14px auto;
            display: flex;
            gap: 8px;
        }
        .btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            font-family: 'Segoe UI', sans-serif;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }
        .btn-print { background: #b45309; color: #fff; }
        .btn-back  { background: #1c1917; color: #fff; }

        /* Efek Kertas Roll Thermal 80mm */
        .paper-roll {
            width: 310px;
            margin: 0 auto;
            background: #fffdf9;
            padding: 20px 18px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
            position: relative;
            border-top: 5px dotted #d6d3d1;
            border-bottom: 5px dotted #d6d3d1;
        }
        .center { text-align: center; }
        .center h2 { margin: 0 0 3px 0; font-size: 18px; letter-spacing: 0.5px; }
        .center p { margin: 2px 0; font-size: 11.5px; }
        .line {
            border-top: 1px dashed #222;
            margin: 10px 0;
        }
        .row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin: 3px 0;
        }
        .item {
            margin: 7px 0;
            font-size: 12px;
        }
        .item-title {
            font-weight: bold;
        }
        .row-grand {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
            margin: 6px 0;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .top-buttons { display: none; }
            .paper-roll {
                box-shadow: none;
                border: none;
                width: 100%;
                max-width: 300px;
                padding: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="top-buttons">
    <button onclick="window.print()" class="btn btn-print">🖨️ Print Kertas Roll</button>
    <a href="index.php?menu=kasir" class="btn btn-back">+ Transaksi Baru</a>
</div>

<!-- STRUK KERTAS ROLL KASIR -->
<div class="paper-roll">
    <div class="center">
        <h2>HOBAS COFFEE & RESTO</h2>
        <p>Jl. Kampus Raya No. 88</p>
        <p>Telp: 0812-3456-7890</p>
    </div>

    <div class="line"></div>

    <div class="row"><span>No. Struk</span> <span><strong><?php echo $tx['no_nota']; ?></strong></span></div>
    <div class="row"><span>Tanggal</span> <span><?php echo date('d/m/Y H:i', strtotime($tx['tanggal'])); ?></span></div>
    <div class="row"><span>Kasir</span> <span><?php echo $_SESSION['nama_kasir']; ?></span></div>

    <div class="line"></div>

    <?php while ($it = mysqli_fetch_assoc($q_detail)) { ?>
    <div class="item">
        <div class="item-title"><?php echo $it['nama_produk']; ?></div>
        <div class="row">
            <span><?php echo $it['qty']; ?> x Rp <?php echo number_format($it['harga'], 0, ',', '.'); ?></span>
            <span>Rp <?php echo number_format($it['subtotal'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <?php } ?>

    <div class="line"></div>

    <div class="row-grand">
        <span>TOTAL</span>
        <span>Rp <?php echo number_format($tx['total_harga'], 0, ',', '.'); ?></span>
    </div>
    <div class="row">
        <span>TUNAI</span>
        <span>Rp <?php echo number_format($tx['uang_bayar'], 0, ',', '.'); ?></span>
    </div>
    <div class="row">
        <span>KEMBALI</span>
        <span>Rp <?php echo number_format($tx['kembalian'], 0, ',', '.'); ?></span>
    </div>

    <div class="line"></div>

    <div class="center" style="margin-top: 8px;">
        <p>*** TERIMA KASIH ***</p>
        <p>Selamat Menikmati Hidangan Kami</p>
    </div>
</div>

</body>
</html>
