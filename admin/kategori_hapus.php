<?php
session_name('SIPORA_ADMIN');
session_start();

include '../koneksi.php';

// Cek login Admin
if (
    !isset($_SESSION['id_user'], $_SESSION['role']) ||
    $_SESSION['role'] != 'Admin'
) {
    header("Location: ../index.php");
    exit;
}

/** @var mysqli $koneksi */

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Ambil nama kategori sebelum dihapus
    $data = mysqli_query($koneksi, "
        SELECT nama_kategori
        FROM kategori
        WHERE id_kategori = $id
    ");

    if (mysqli_num_rows($data) > 0) {
        $kategori = mysqli_fetch_assoc($data);
        $nama = $kategori['nama_kategori'];

        // Hapus kategori
        $hapus = mysqli_query($koneksi, "
            DELETE FROM kategori
            WHERE id_kategori = $id
        ");

        if ($hapus) {
            // Catat aktivitas jika penghapusan berhasil
            $id_user = (int) $_SESSION['id_user'];
            $aktivitas = "Menghapus kategori";

            $keterangan_log = "Menghapus kategori: " . $nama;
            $keterangan_log = mysqli_real_escape_string(
                $koneksi,
                $keterangan_log
            );

            mysqli_query($koneksi, "
                INSERT INTO log_aktivitas (id_user, aktivitas, keterangan)
                VALUES (
                    $id_user,
                    '$aktivitas',
                    '$keterangan_log'
                )
            ");

            echo "<script>
                alert('Kategori berhasil dihapus!');
                window.location='kategori.php';
            </script>";
        } else {
            echo "<script>
                alert('Kategori gagal dihapus! Mungkin masih digunakan oleh data alat.');
                window.location='kategori.php';
            </script>";
        }
    } else {
        header("Location: kategori.php");
    }
} else {
    header("Location: kategori.php");
}

exit;
?>