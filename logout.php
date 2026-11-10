<?php
// ==========================================================
// FILE: logout.php
// Deskripsi: Menghapus session login dan kembali ke halaman login.php
// ==========================================================

session_start();

// Menghapus seluruh data session yang aktif
session_unset();
session_destroy();

// Mengarahkan kembali ke halaman login
header("Location: login.php");
exit;
?>
