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

$id = (int) ($_GET['id'] ?? 0);

// Ambil nama alat
$data = mysqli_query($koneksi, "
    SELECT nama_alat FROM alat WHERE id_alat = $id
");

if (mysqli_num_rows($data) > 0) {
    $alat = mysqli_fetch_assoc($data);
    $nama = mysqli_real_escape_string($koneksi, $alat['nama_alat']);

    // Hapus alat
    $hapus = mysqli_query($koneksi, "
        DELETE FROM alat WHERE id_alat = $id
    ");

    if ($hapus) {
        // Catat log aktivitas
        $id_user = (int) $_SESSION['id_user'];

        mysqli_query($koneksi, "
            INSERT INTO log_aktivitas (id_user, aktivitas, keterangan)
            VALUES (
                $id_user,
                'Menghapus alat',
                'Menghapus alat: $nama'
            )
        ");

        echo "<script>
            alert('Alat berhasil dihapus!');
            window.location='alat.php';
        </script>";
    } else {
        echo "<script>
            alert('Alat gagal dihapus! Mungkin masih digunakan dalam riwayat peminjaman.');
            window.location='alat.php';
        </script>";
    }
} else {
    header("Location: alat.php");
}

exit;
?>
