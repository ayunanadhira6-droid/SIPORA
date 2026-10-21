<?php
if (
    !isset($_SESSION['id_user'], $_SESSION['role']) ||
    $_SESSION['role'] != 'Admin'
) {
    header("Location: ../index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($judul) ? $judul : 'Admin SIPORA' ?></title>

    <link rel="stylesheet" href="../assets/bootstrap.min.css">

    <style>
        body {
            background-color: #f4f7fb;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            background-color: #1769aa;
            padding: 20px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px;
        }

        .main-content {
            margin-left: 260px;
        }

        .topbar {
            background-color: white;
            padding: 15px;
        }

        .content {
            padding: 20px;
        }
    </style>
</head>

<body>

<div class="sidebar">
    <h4 class="text-white text-center">SIPORA</h4>
    <p class="text-white text-center">Admin Panel</p>

    <a href="admin_dashboard.php">Dashboard</a>
    <a href="user.php">Data Pengguna</a>
    <a href="kategori.php">Data Kategori</a>
    <a href="alat.php">Data Alat</a>
    <a href="peminjaman.php">Lihat Peminjaman</a>
    <a href="pengembalian.php">Lihat Pengembalian</a>
    <a href="laporan.php">Laporan</a>
    <a href="log_aktivitas.php">Log Aktivitas</a>

    <hr class="text-white">

    <a href="../logout.php?role=Admin" onclick="return confirm('Yakin ingin logout?')">
        Logout
    </a>
</div>

<div class="main-content">

    <div class="topbar d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><?= isset($judul) ? $judul : 'Dashboard Admin' ?></h5>
            <small>Sistem Informasi Peminjaman Alat Olahraga</small>
        </div>

        <div>
            <?= htmlspecialchars($_SESSION['username']) ?>
        </div>
    </div>

    <div class="content">