-- ==========================================================
-- FILE: database.sql
-- Deskripsi: Database Sederhana Sistem POS Kasir "Hobas Coffee & Resto"
--            Lengkap dengan Penggunaan Stok Produk
-- ==========================================================

CREATE DATABASE IF NOT EXISTS db_hobas;
USE db_hobas;

-- 1. Tabel users (Hanya 1 User: Kasir)
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nama_kasir VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL
);

INSERT INTO users (id, nama_kasir, username, password) VALUES
(1, 'Kasir Hobas', 'kasir', '12345');

-- 2. Tabel produk (Nama Produk, Harga, Stok, dan Gambar Produk)
DROP TABLE IF EXISTS produk;
CREATE TABLE produk (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(100) NOT NULL,
    harga INT(11) NOT NULL DEFAULT 0,
    stok INT(11) NOT NULL DEFAULT 20,
    gambar VARCHAR(255) NOT NULL DEFAULT 'uploads/kopi_susu.svg'
);

INSERT INTO produk (id, nama_produk, harga, stok, gambar) VALUES
(1, 'Es Kopi Susu Hobas', 18000, 35, 'uploads/kopi_susu.svg'),
(2, 'Iced Americano', 15000, 30, 'uploads/americano.svg'),
(3, 'Matcha Latte', 22000, 25, 'uploads/matcha.svg'),
(4, 'Nasi Goreng Hobas', 28000, 20, 'uploads/nasigoreng.svg'),
(5, 'Chicken Katsu Rice', 30000, 18, 'uploads/katsu.svg'),
(6, 'French Fries Original', 16000, 40, 'uploads/kentang.svg');

-- 3. Tabel transaksi (Menyimpan Nota Penjualan)
DROP TABLE IF EXISTS transaksi;
CREATE TABLE transaksi (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    no_nota VARCHAR(30) NOT NULL,
    tanggal DATETIME NOT NULL,
    total_harga INT(11) NOT NULL DEFAULT 0,
    uang_bayar INT(11) NOT NULL DEFAULT 0,
    kembalian INT(11) NOT NULL DEFAULT 0
);

INSERT INTO transaksi (id, no_nota, tanggal, total_harga, uang_bayar, kembalian) VALUES
(1, 'HBS-001', NOW(), 64000, 100000, 36000),
(2, 'HBS-002', DATE_SUB(NOW(), INTERVAL 2 DAY), 45000, 50000, 5000),
(3, 'HBS-003', DATE_SUB(NOW(), INTERVAL 10 DAY), 58000, 60000, 2000);

-- 4. Tabel transaksi_detail (Menyimpan Rincian Barang per Nota)
DROP TABLE IF EXISTS transaksi_detail;
CREATE TABLE transaksi_detail (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT(11) NOT NULL,
    id_produk INT(11) NOT NULL,
    nama_produk VARCHAR(100) NOT NULL,
    harga INT(11) NOT NULL DEFAULT 0,
    qty INT(11) NOT NULL DEFAULT 1,
    subtotal INT(11) NOT NULL DEFAULT 0
);

INSERT INTO transaksi_detail (id_transaksi, id_produk, nama_produk, harga, qty, subtotal) VALUES
(1, 1, 'Es Kopi Susu Hobas', 18000, 2, 36000),
(1, 4, 'Nasi Goreng Hobas', 28000, 1, 28000),
(2, 2, 'Iced Americano', 15000, 1, 15000),
(2, 5, 'Chicken Katsu Rice', 30000, 1, 30000),
(3, 3, 'Matcha Latte', 22000, 1, 22000),
(3, 1, 'Es Kopi Susu Hobas', 18000, 2, 36000);
