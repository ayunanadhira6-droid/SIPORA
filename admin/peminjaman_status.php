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

// Admin hanya memiliki akses melihat data, bukan memproses transaksi.
header("Location: peminjaman.php");
exit;

/** @var mysqli $koneksi */

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location:peminjaman.php");
    exit;
}

$id_peminjaman = (int) ($_POST['id_peminjaman'] ?? 0);
$aksi = $_POST['aksi'] ?? '';
$id_petugas = (int) $_SESSION['id_user'];

if ($id_peminjaman <= 0) {
    die("ID peminjaman tidak valid.");
}

mysqli_begin_transaction($koneksi);

try {
    // Kunci data peminjaman agar tidak diproses bersamaan
    $stmt = mysqli_prepare($koneksi, "
        SELECT status
        FROM peminjaman
        WHERE id_peminjaman = ?
        FOR UPDATE
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $peminjaman = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($stmt);

    if (!$peminjaman) {
        throw new Exception("Data peminjaman tidak ditemukan.");
    }

    $status_lama = $peminjaman['status'];

    if ($aksi == 'setujui' && $status_lama == 'Menunggu') {
        $status_baru = 'Disetujui';
        $pesan = 'Menyetujui peminjaman ID ' . $id_peminjaman;

    } elseif ($aksi == 'tolak' && $status_lama == 'Menunggu') {
        $status_baru = 'Ditolak';
        $pesan = 'Menolak peminjaman ID ' . $id_peminjaman;

    } elseif ($aksi == 'serahkan' && $status_lama == 'Disetujui') {

        // Ambil semua alat yang ada dalam peminjaman
        $stmt = mysqli_prepare($koneksi, "
            SELECT id_alat, jumlah_pinjam
            FROM detail_peminjaman
            WHERE id_peminjaman = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);

        $daftar_alat = [];

        while ($baris = mysqli_fetch_assoc($hasil)) {
            $daftar_alat[] = $baris;
        }
        mysqli_stmt_close($stmt);

        if (count($daftar_alat) == 0) {
            throw new Exception("Detail alat peminjaman belum tersedia.");
        }

        // Kurangi stok hanya saat alat diserahkan
        foreach ($daftar_alat as $alat) {
            $id_alat = (int) $alat['id_alat'];
            $jumlah = (int) $alat['jumlah_pinjam'];

            $stmt = mysqli_prepare($koneksi, "
                UPDATE alat
                SET jumlah_stok = jumlah_stok - ?
                WHERE id_alat = ?
                  AND kondisi = 'Baik'
                  AND jumlah_stok >= ?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iii",
                $jumlah,
                $id_alat,
                $jumlah
            );

            mysqli_stmt_execute($stmt);
            $berhasil = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($berhasil != 1) {
                throw new Exception(
                    "Stok alat tidak cukup atau alat tidak dalam kondisi baik."
                );
            }
        }

        $status_baru = 'Dipinjam';
        $pesan = 'Menyerahkan alat untuk peminjaman ID ' . $id_peminjaman;

    } else {
        throw new Exception("Tindakan tidak sesuai dengan status peminjaman.");
    }

    // Simpan status dan petugas yang memproses
    $stmt = mysqli_prepare($koneksi, "
        UPDATE peminjaman
        SET status = ?, id_petugas = ?
        WHERE id_peminjaman = ?
    ");
    mysqli_stmt_bind_param(
        $stmt,
        "sii",
        $status_baru,
        $id_petugas,
        $id_peminjaman
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Catat aktivitas admin
    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO log_aktivitas (id_user, aktivitas, keterangan)
        VALUES (?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iss", $id_petugas, $pesan, $status_baru);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($koneksi);

    echo "<script>
        alert('Data peminjaman berhasil diproses.');
        window.location='peminjaman.php';
    </script>";

} catch (Throwable $e) {
    mysqli_rollback($koneksi);

    $pesan_error = $e->getMessage();

    echo "<script>
        alert(" . json_encode($pesan_error) . ");
        window.location='peminjaman.php';
    </script>";
}
?>