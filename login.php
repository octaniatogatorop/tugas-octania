<?php
// ==========================================================
// FILE: login.php
// Deskripsi: Halaman Login Kasir POS "Hobas Coffee & Resto" (1 User Kasir)
// ==========================================================

session_start();
include 'koneksi.php';

// Jika kasir sudah login, langsung arahkan ke halaman utama POS (index.php)
if (isset($_SESSION['pos_login'])) {
    header("Location: index.php");
    exit;
}

$pesan_error = "";

// LOGIKA PROSES LOGIN KASIR
if (isset($_POST['tombol_login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Mencari akun kasir di tabel 'users'
    $query_login = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $hasil_login = mysqli_query($koneksi, $query_login);

    if (mysqli_num_rows($hasil_login) > 0) {
        $data_kasir = mysqli_fetch_assoc($hasil_login);

        // Simpan data kasir ke dalam SESSION
        $_SESSION['pos_login'] = true;
        $_SESSION['id_kasir'] = $data_kasir['id'];
        $_SESSION['nama_kasir'] = $data_kasir['nama_kasir'];
        $_SESSION['username'] = $data_kasir['username'];

        header("Location: index.php?menu=dashboard");
        exit;
    } else {
        $pesan_error = "Username atau Password Kasir salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Kasir - Hobas Coffee & Resto POS</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #fafaf9;
            color: #1c1917;
            min-height: 100vh;
            display: flex;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Kolom Kiri: Branding Hobas Coffee & Resto */
        .left-brand {
            flex: 1.15;
            background: linear-gradient(135deg, #1c1917 0%, #451a03 60%, #78350f 100%);
            color: #ffffff;
            padding: 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .badge-cafe {
            display: inline-block;
            background: rgba(245, 158, 11, 0.25);
            border: 1px solid #f59e0b;
            color: #fde68a;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.8px;
            margin-bottom: 18px;
            width: fit-content;
        }

        .left-brand h1 {
            font-size: 40px;
            line-height: 1.2;
            margin-bottom: 14px;
            font-weight: 800;
            color: #fef3c7;
        }

        .left-brand p {
            font-size: 15.5px;
            line-height: 1.65;
            color: #e7e5e4;
            margin-bottom: 32px;
            max-width: 540px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            max-width: 580px;
        }

        .feature-item {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            padding: 15px;
            border-radius: 10px;
        }

        .feature-item h4 {
            font-size: 14px;
            color: #fbbf24;
            margin-bottom: 5px;
        }

        .feature-item p {
            font-size: 12.5px;
            color: #d6d3d1;
            margin: 0;
            line-height: 1.4;
        }

        /* Kolom Kanan: Form Login Kasir */
        .right-form {
            flex: 1;
            background: #ffffff;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-box {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .form-box h2 {
            font-size: 26px;
            color: #1c1917;
            margin-bottom: 6px;
            font-weight: 800;
        }

        .form-box p.sub {
            font-size: 14px;
            color: #78716c;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 13.5px;
            color: #44403c;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #d6d3d1;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.15);
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(90deg, #b45309 0%, #d97706 100%);
            color: white;
            border: none;
            padding: 12px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 6px;
        }

        .btn-login:hover {
            background: #92400e;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .info-kasir {
            background: #fffbeb;
            border: 1px solid #fde68a;
            padding: 14px;
            border-radius: 9px;
            margin-top: 22px;
            font-size: 13.5px;
            color: #78350f;
            text-align: center;
        }

        .info-kasir strong {
            display: block;
            margin-bottom: 4px;
            color: #92400e;
        }

        @media (max-width: 850px) {
            .login-wrapper {
                flex-direction: column;
            }

            .left-brand,
            .right-form {
                padding: 32px 20px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">
        <!-- KOLOM KIRI: INFORMASI BRAND HOBAS COFFEE & RESTO -->
        <div class="left-brand">
            <span class="badge-cafe">☕ POINT OF SALE SYSTEM</span>
            <h1>Hobas Coffee & Resto</h1>
            <p>Sistem Kasir Terpadu untuk mengelola transaksi pesanan pelanggan, antrean meja & dapur, stok menu, promo
                voucher, kas operasional, hingga laporan laba bersih.</p>
        </div>

        <!-- KOLOM KANAN: FORM LOGIN TUNGGAL KASIR -->
        <div class="right-form">
            <div class="form-box">
                <h2>Login Kasir POS</h2>
                <p class="sub">Masuk ke Mesin Kasir Hobas Coffee & Resto</p>

                <?php if ($pesan_error != "") { ?>
                    <div class="alert-error"><?php echo $pesan_error; ?></div>
                <?php } ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label>Username Kasir</label>
                        <input type="text" name="username" placeholder="Masukkan username: kasir" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Masukkan password: 12345" required>
                    </div>

                    <button type="submit" name="tombol_login" class="btn-login">Masuk ke Mesin Kasir</button>
                </form>

                <div class="info-kasir">
                    <strong>Akun Kasir Hobas Coffee & Resto:</strong>
                    Username: <code>kasir</code> &nbsp;|&nbsp; Password: <code>12345</code>
                </div>
            </div>
        </div>
    </div>

</body>

</html>