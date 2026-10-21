<?php
if (
    !isset($_SESSION['id_user'], $_SESSION['role']) ||
    $_SESSION['role'] != 'Petugas'
) {
    header("Location: ../index.php");
    exit;
}
?>
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= isset($judul) ? htmlspecialchars($judul) : 'Petugas SIPORA' ?></title>

    <link rel="stylesheet" href="../assets/bootstrap.min.css">

    <style>
        body {
            background-color: #f4f7fb;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #1769aa;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 4px;
        }

        .sidebar a:hover {
            background-color: #0d528b;
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }

        .topbar {
            background-color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .content {
            padding: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
            }

            .content {
                padding: 12px;
            }
        }
    </style>
</head>

<body>

<!-- Sidebar Petugas -->
<div class="sidebar">

    <h4 class="text-white text-center fw-bold">SIPORA</h4>
    <p class="text-white text-center">Panel Petugas</p>

    <hr class="text-white">

    <a href="petugas_dashboard.php">Dashboard</a>
    <a href="alat.php">Data Alat</a>
    <a href="peminjaman.php">Data Peminjaman</a>
    <a href="pengembalian.php">Data Pengembalian</a>
    <a href="laporan.php">Laporan</a>

    <hr class="text-white">

    <a href="../logout.php?role=Petugas"
        onclick="return confirm('Yakin ingin logout?')">
        Logout
    </a>

</div>

<!-- Konten Utama -->
<div class="main-content">

    <!-- Header Atas -->
    <div class="topbar d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-0">
                <?= isset($judul) ? htmlspecialchars($judul) : 'Dashboard Petugas' ?>
            </h5>

            <small class="text-secondary">
                Sistem Informasi Peminjaman Alat Olahraga
            </small>
        </div>

        <div class="text-end">
            <span class="fw-semibold">
                <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <br>
            <small class="text-secondary">Petugas</small>
        </div>

    </div>

    <!-- Isi Halaman -->
    <div class="content">