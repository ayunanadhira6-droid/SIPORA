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

    // Tidak boleh menghapus akun sendiri
    if ($id == $_SESSION['id_user']) {
        echo "<script>
            alert('Akun sendiri tidak bisa dihapus!');
            window.location='user.php';
        </script>";
        exit;
    }

    // Ambil data pengguna
    $user = mysqli_query($koneksi,
        "SELECT username, role FROM users WHERE id_user = $id"
    );

    if (mysqli_num_rows($user) > 0) {
        $data = mysqli_fetch_assoc($user);

        // Catat aktivitas
        $username = mysqli_real_escape_string(
            $koneksi, $data['username']
        );
        $role = mysqli_real_escape_string(
            $koneksi, $data['role']
        );

        $aktivitas = "Menghapus pengguna";
        $keterangan = "Menghapus pengguna: $username dengan role $role";

        mysqli_query($koneksi, "
            INSERT INTO log_aktivitas (id_user, aktivitas, keterangan)
            VALUES (
                " . (int) $_SESSION['id_user'] . ",
                '$aktivitas',
                '$keterangan'
            )
        ");

        // Hapus pengguna
        $hapus = mysqli_query(
            $koneksi,
            "DELETE FROM users WHERE id_user = $id"
        );

        if ($hapus) {
            echo "<script>
                alert('Pengguna berhasil dihapus!');
                window.location='user.php';
            </script>";
        } else {
            echo "<script>
                alert('Pengguna gagal dihapus! Mungkin masih memiliki riwayat peminjaman.');
                window.location='user.php';
            </script>";
        }
    } else {
        header("Location: user.php");
    }
} else {
    header("Location: user.php");
}

exit;
?>