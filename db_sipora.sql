-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 18, 2026 at 04:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sipora`
--

-- --------------------------------------------------------

--
-- Table structure for table `alat`
--

CREATE TABLE `alat` (
  `id_alat` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `nama_alat` varchar(100) NOT NULL,
  `jumlah_stok` int(11) NOT NULL DEFAULT 0,
  `kondisi` enum('Baik','Rusak') NOT NULL DEFAULT 'Baik',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alat`
--

INSERT INTO `alat` (`id_alat`, `id_kategori`, `nama_alat`, `jumlah_stok`, `kondisi`, `keterangan`, `created_at`) VALUES
(1, 1, 'Bola Voli', 5, 'Baik', 'Bola untuk latihan voli', '2026-10-14 05:52:44'),
(2, 1, 'Bola Basket', 3, 'Baik', 'Bola untuk latihan basket', '2026-10-14 05:52:44'),
(3, 1, 'Bola Sepak', 4, 'Baik', 'Bola untuk latihan sepak bola', '2026-10-14 05:52:44'),
(4, 2, 'Raket Bulu Tangkis', 4, 'Baik', 'Raket untuk latihan', '2026-10-14 05:52:44'),
(5, 2, 'Net Bulu Tangkis', 2, 'Baik', 'Net untuk permainan bulu tangkis', '2026-10-14 05:52:44'),
(6, 3, 'Stopwatch', 4, 'Baik', 'Alat pengukur waktu', '2026-10-14 05:52:44'),
(7, 3, 'Peluit', 5, 'Baik', 'Peluit untuk kegiatan olahraga', '2026-10-14 05:52:44'),
(8, 5, 'Matras olahraga', 4, 'Baik', 'Alas untuk senam dan latihan fisik.', '2026-10-16 05:58:28');

-- --------------------------------------------------------

--
-- Table structure for table `detail_peminjaman`
--

CREATE TABLE `detail_peminjaman` (
  `id_detail_peminjaman` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `id_alat` int(11) NOT NULL,
  `jumlah_pinjam` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_peminjaman`
--

INSERT INTO `detail_peminjaman` (`id_detail_peminjaman`, `id_peminjaman`, `id_alat`, `jumlah_pinjam`, `created_at`) VALUES
(1, 1, 6, 1, '2026-10-16 06:19:05'),
(2, 1, 8, 1, '2026-10-16 06:19:05'),
(3, 2, 8, 1, '2026-10-16 06:22:06'),
(4, 2, 6, 1, '2026-10-16 06:22:06'),
(5, 3, 4, 2, '2026-10-16 06:27:48'),
(6, 4, 2, 1, '2026-10-16 06:33:19'),
(7, 5, 4, 2, '2026-10-18 13:25:55'),
(8, 6, 7, 1, '2026-10-18 13:52:47'),
(9, 7, 8, 1, '2026-10-18 13:53:24');

-- --------------------------------------------------------

--
-- Table structure for table `detail_pengembalian`
--

CREATE TABLE `detail_pengembalian` (
  `id_detail_pengembalian` int(11) NOT NULL,
  `id_pengembalian` int(11) NOT NULL,
  `id_detail_peminjaman` int(11) NOT NULL,
  `jumlah_kembali` int(11) NOT NULL,
  `kondisi_kembali` enum('Baik','Rusak') NOT NULL DEFAULT 'Baik',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_pengembalian`
--

INSERT INTO `detail_pengembalian` (`id_detail_pengembalian`, `id_pengembalian`, `id_detail_peminjaman`, `jumlah_kembali`, `kondisi_kembali`, `catatan`, `created_at`) VALUES
(1, 1, 3, 1, 'Baik', '', '2026-10-16 06:25:23'),
(2, 1, 4, 1, 'Baik', '', '2026-10-16 06:25:23'),
(3, 2, 5, 2, 'Baik', '', '2026-10-16 06:28:42'),
(4, 3, 6, 1, 'Baik', '', '2026-10-16 06:35:55'),
(5, 4, 7, 2, 'Rusak', '', '2026-10-18 13:27:31');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `keterangan`, `created_at`) VALUES
(1, 'Bola', 'Peralatan olahraga berbentuk bola', '2026-10-14 05:52:13'),
(2, 'Bulu Tangkis', 'Peralatan olahraga bulu tangkis', '2026-10-14 05:52:13'),
(3, 'Peralatan Pendukung', 'Peralatan pendukung kegiatan olahraga', '2026-10-14 05:52:13'),
(5, 'Kebugaran', 'Peralatan untuk latihan kebugaran jasmani.', '2026-10-16 05:51:37');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `waktu_aktivitas` timestamp NOT NULL DEFAULT current_timestamp(),
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id_log`, `id_user`, `aktivitas`, `waktu_aktivitas`, `keterangan`) VALUES
(1, 1, 'Menambahkan pengguna', '2026-10-16 04:55:01', 'Admin menambahkan pengguna: petugas dengan role Petugas'),
(2, 1, 'Mengubah data pengguna', '2026-10-16 04:59:14', 'Admin mengubah data pengguna ID 1 (admin)'),
(3, 1, 'Mengubah data pengguna', '2026-10-16 05:01:50', 'Admin mengubah data pengguna ID 1 (admin3)'),
(4, 1, 'Mengubah data pengguna', '2026-10-16 05:02:41', 'Admin mengubah data pengguna ID 1 (admin)'),
(5, 1, 'Menambahkan pengguna', '2026-10-16 05:12:45', 'Admin menambahkan pengguna: yuna dengan role Admin'),
(6, 1, 'Menghapus pengguna', '2026-10-16 05:14:27', 'Menghapus pengguna: yuna dengan role Admin'),
(7, 1, 'Mengubah data pengguna', '2026-10-16 05:16:59', 'Admin mengubah data pengguna ID 1 (admin5)'),
(8, 1, 'Mengubah data pengguna', '2026-10-16 05:17:36', 'Admin mengubah data pengguna ID 1 (admin)'),
(9, 1, 'Menghapus kategori', '2026-10-16 05:50:54', 'Menghapus kategori: Kebugaran'),
(10, 1, 'Menambah kategori', '2026-10-16 05:51:37', 'Menambahkan kategori: Kebugaran'),
(11, 1, 'Mengedit kategori', '2026-10-16 05:51:56', 'Mengubah kategori menjadi: Kebugaran jasmani'),
(12, 1, 'Mengedit kategori', '2026-10-16 05:52:22', 'Mengubah kategori menjadi: Kebugara'),
(13, 1, 'Mengedit kategori', '2026-10-16 05:52:42', 'Mengubah kategori menjadi: Kebugaran'),
(14, 1, 'Menambah alat', '2026-10-16 05:58:28', 'Menambahkan alat: Matras, stok: 5, kondisi: Baik'),
(15, 1, 'Mengedit alat', '2026-10-16 06:03:22', 'Mengubah data alat: Matras olahraga, stok: 5, kondisi: Baik'),
(16, 1, 'Menambah alat', '2026-10-16 06:06:40', 'Menambahkan alat: Dumbbell, stok: 5, kondisi: Baik'),
(17, 1, 'Menghapus alat', '2026-10-16 06:06:44', 'Menghapus alat: Dumbbell'),
(18, 1, 'Menambahkan pengguna', '2026-10-16 06:17:26', 'Admin menambahkan pengguna: ayuna dengan role Peminjam'),
(19, 1, 'Menambah peminjaman', '2026-10-16 06:19:05', 'Menambahkan peminjaman ID 1 untuk pengguna ID 4'),
(20, 1, 'Menambah peminjaman', '2026-10-16 06:22:06', 'Menambahkan peminjaman ID 2 untuk pengguna ID 4'),
(21, 1, 'Menolak peminjaman ID 1', '2026-10-16 06:23:03', 'Ditolak'),
(22, 1, 'Menyetujui peminjaman ID 2', '2026-10-16 06:24:02', 'Disetujui'),
(23, 1, 'Menyerahkan alat untuk peminjaman ID 2', '2026-10-16 06:24:16', 'Dipinjam'),
(24, 1, 'Memproses pengembalian peminjaman ID 2', '2026-10-16 06:25:23', 'Pengembalian ID 1, tanggal 2026-10-16, denda Rp 0'),
(25, 1, 'Menambah peminjaman', '2026-10-16 06:27:48', 'Menambahkan peminjaman ID 3 untuk pengguna ID 4'),
(26, 1, 'Menyetujui peminjaman ID 3', '2026-10-16 06:28:03', 'Disetujui'),
(27, 1, 'Menyerahkan alat untuk peminjaman ID 3', '2026-10-16 06:28:08', 'Dipinjam'),
(28, 1, 'Memproses pengembalian peminjaman ID 3', '2026-10-16 06:28:42', 'Pengembalian ID 2, tanggal 2026-10-16, denda Rp 0'),
(29, 1, 'Menambah peminjaman', '2026-10-16 06:33:19', 'Menambahkan peminjaman ID 4 untuk pengguna ID 4'),
(30, 1, 'Menyetujui peminjaman ID 4', '2026-10-16 06:33:28', 'Disetujui'),
(31, 1, 'Menyerahkan alat untuk peminjaman ID 4', '2026-10-16 06:33:32', 'Dipinjam'),
(32, 1, 'Memproses pengembalian peminjaman ID 4', '2026-10-16 06:35:55', 'Pengembalian ID 3, tanggal 2026-10-16, terlambat 1 hari, denda Rp 5.000'),
(33, 1, 'Menambah peminjaman', '2026-10-18 13:25:55', 'Menambahkan peminjaman ID 5 untuk pengguna ID 4'),
(34, 2, 'Menyetujui peminjaman ID 5', '2026-10-18 13:26:20', 'Disetujui'),
(35, 2, 'Menyerahkan alat untuk peminjaman ID 5', '2026-10-18 13:26:30', 'Dipinjam'),
(36, 2, 'Memproses pengembalian peminjaman ID 5', '2026-10-18 13:27:31', 'Pengembalian ID 4, tanggal 2026-10-18, terlambat 3 hari, denda Rp 15.000'),
(37, 4, 'Mengajukan peminjaman', '2026-10-18 13:52:47', 'Pengajuan peminjaman ID 6 berhasil dibuat.'),
(38, 4, 'Mengajukan peminjaman', '2026-10-18 13:53:24', 'Pengajuan peminjaman ID 7 berhasil dibuat.'),
(39, 2, 'Menyetujui peminjaman ID 7', '2026-10-18 13:53:45', 'Disetujui'),
(40, 2, 'Menyerahkan alat untuk peminjaman ID 7', '2026-10-18 13:53:54', 'Dipinjam');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_peminjaman` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_petugas` int(11) DEFAULT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_rencana_kembali` date NOT NULL,
  `keperluan` text NOT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak','Dipinjam','Dikembalikan') NOT NULL DEFAULT 'Menunggu',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id_peminjaman`, `id_user`, `id_petugas`, `tanggal_pinjam`, `tanggal_rencana_kembali`, `keperluan`, `status`, `catatan`, `created_at`) VALUES
(1, 4, 1, '2026-10-16', '2026-10-18', 'Mau Workout', 'Ditolak', 'Saya usahakan kembali tepat waktu', '2026-10-16 06:19:05'),
(2, 4, 1, '2026-10-15', '2026-10-17', 'Mau Workout', 'Dikembalikan', 'Saya usahakan tepat waktu kembaliin nya', '2026-10-16 06:22:06'),
(3, 4, 1, '2026-10-12', '2026-10-14', 'Mau badminton', 'Dikembalikan', '', '2026-10-16 06:27:48'),
(4, 4, 1, '2026-10-11', '2026-10-15', 'Mau latihan basket', 'Dikembalikan', '', '2026-10-16 06:33:19'),
(5, 4, 2, '2026-10-14', '2026-10-15', 'pinjam yah', 'Dikembalikan', '', '2026-10-18 13:25:55'),
(6, 4, NULL, '2026-10-18', '2026-10-19', 'ada', 'Menunggu', 'yaa', '2026-10-18 13:52:47'),
(7, 4, 2, '2026-10-18', '2026-10-19', 'yaa yaa', 'Dipinjam', 'ada', '2026-10-18 13:53:24');

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id_pengembalian` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `total_denda` decimal(12,2) NOT NULL DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengembalian`
--

INSERT INTO `pengembalian` (`id_pengembalian`, `id_peminjaman`, `tanggal_kembali`, `total_denda`, `catatan`, `created_at`) VALUES
(1, 2, '2026-10-16', 0.00, '', '2026-10-16 06:25:23'),
(2, 3, '2026-10-16', 0.00, '', '2026-10-16 06:28:42'),
(3, 4, '2026-10-16', 5000.00, '', '2026-10-16 06:35:55'),
(4, 5, '2026-10-18', 15000.00, 'Karetnya terkelupas', '2026-10-18 13:27:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Petugas','Peminjam') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Admin', '2026-10-14 07:27:03'),
(2, 'petugas', 'afb91ef692fd08c445e8cb1bab2ccf9c', 'Petugas', '2026-10-16 04:55:01'),
(4, 'ayuna', '41ac64af15eb8fb9ac9b33334b25a9a0', 'Peminjam', '2026-10-16 06:17:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`id_alat`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD PRIMARY KEY (`id_detail_peminjaman`),
  ADD KEY `id_peminjaman` (`id_peminjaman`),
  ADD KEY `id_alat` (`id_alat`);

--
-- Indexes for table `detail_pengembalian`
--
ALTER TABLE `detail_pengembalian`
  ADD PRIMARY KEY (`id_detail_pengembalian`),
  ADD UNIQUE KEY `id_detail_peminjaman` (`id_detail_peminjaman`),
  ADD KEY `id_pengembalian` (`id_pengembalian`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_petugas` (`id_petugas`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id_pengembalian`),
  ADD UNIQUE KEY `id_peminjaman` (`id_peminjaman`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alat`
--
ALTER TABLE `alat`
  MODIFY `id_alat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  MODIFY `id_detail_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `detail_pengembalian`
--
ALTER TABLE `detail_pengembalian`
  MODIFY `id_detail_pengembalian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id_pengembalian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alat`
--
ALTER TABLE `alat`
  ADD CONSTRAINT `alat_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE;

--
-- Constraints for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD CONSTRAINT `detail_peminjaman_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON UPDATE CASCADE,
  ADD CONSTRAINT `detail_peminjaman_ibfk_2` FOREIGN KEY (`id_alat`) REFERENCES `alat` (`id_alat`) ON UPDATE CASCADE;

--
-- Constraints for table `detail_pengembalian`
--
ALTER TABLE `detail_pengembalian`
  ADD CONSTRAINT `detail_pengembalian_ibfk_1` FOREIGN KEY (`id_pengembalian`) REFERENCES `pengembalian` (`id_pengembalian`) ON UPDATE CASCADE,
  ADD CONSTRAINT `detail_pengembalian_ibfk_2` FOREIGN KEY (`id_detail_peminjaman`) REFERENCES `detail_peminjaman` (`id_detail_peminjaman`) ON UPDATE CASCADE;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
