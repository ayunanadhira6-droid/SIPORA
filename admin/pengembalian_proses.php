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
header("Location: pengembalian.php");
exit;

/** @var mysqli $koneksi */

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location:pengembalian.php");
    exit;
}

$id_peminjaman = (int) ($_POST['id_peminjaman'] ?? 0);
$id_petugas = (int) $_SESSION['id_user'];
$tanggal_kembali = $_POST['tanggal_kembali'] ?? '';
$catatan = trim($_POST['catatan'] ?? '');

if (
    $id_peminjaman <= 0 ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_kembali)
) {
    die("Data pengembalian tidak valid.");
}

// Pastikan tanggal benar-benar valid
$tanggal_obj = DateTime::createFromFormat('!Y-m-d', $tanggal_kembali);

if (
    !$tanggal_obj ||
    $tanggal_obj->format('Y-m-d') != $tanggal_kembali
) {
    die("Tanggal kembali tidak valid.");
}

if ($tanggal_kembali > date('Y-m-d')) {
    die("Tanggal kembali tidak boleh melebihi tanggal hari ini.");
}

// Ambil data dari form
$jumlah_kembali = $_POST['jumlah_kembali'] ?? [];
$kondisi = $_POST['kondisi'] ?? [];
$catatan_alat = $_POST['catatan_alat'] ?? [];

mysqli_begin_transaction($koneksi);

try {
    // Ambil data peminjaman
    $stmt = mysqli_prepare($koneksi, "
        SELECT status, tanggal_rencana_kembali
        FROM peminjaman
        WHERE id_peminjaman = ?
        FOR UPDATE
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $peminjaman = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($stmt);

    if (!$peminjaman || $peminjaman['status'] != 'Dipinjam') {
        throw new Exception(
            "Peminjaman tidak ditemukan atau sudah diproses."
        );
    }

    // Hitung denda otomatis Rp5.000 per hari
    $tanggal_rencana = new DateTime(
        $peminjaman['tanggal_rencana_kembali']
    );
    $selisih = $tanggal_rencana->diff($tanggal_obj);
    $hari_terlambat = 0;

    if ($tanggal_obj > $tanggal_rencana) {
        $hari_terlambat = (int) $selisih->days;
    }

    $denda_per_hari = 5000;
    $denda = $hari_terlambat * $denda_per_hari;

    // Ambil detail alat dari database
    $stmt = mysqli_prepare($koneksi, "
        SELECT id_detail_peminjaman, id_alat, jumlah_pinjam
        FROM detail_peminjaman
        WHERE id_peminjaman = ?
        FOR UPDATE
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);

    $detail_asli = [];

    while ($baris = mysqli_fetch_assoc($hasil)) {
        $detail_asli[(int) $baris['id_detail_peminjaman']] = $baris;
    }

    mysqli_stmt_close($stmt);

    if (count($detail_asli) == 0) {
        throw new Exception("Detail alat peminjaman tidak ditemukan.");
    }

    // Validasi data pengembalian
    if (
        !is_array($jumlah_kembali) ||
        !is_array($kondisi) ||
        count($jumlah_kembali) != count($detail_asli) ||
        count($kondisi) != count($detail_asli)
    ) {
        throw new Exception("Data pengembalian alat tidak lengkap.");
    }

    foreach ($detail_asli as $id_detail => $detail) {
        if (
            !isset($jumlah_kembali[$id_detail]) ||
            !isset($kondisi[$id_detail]) ||
            !is_scalar($jumlah_kembali[$id_detail]) ||
            !is_scalar($kondisi[$id_detail])
        ) {
            throw new Exception("Data alat tidak lengkap.");
        }

        $jumlah = filter_var(
            $jumlah_kembali[$id_detail],
            FILTER_VALIDATE_INT
        );

        if (
            $jumlah === false ||
            $jumlah != (int) $detail['jumlah_pinjam']
        ) {
            throw new Exception(
                "Jumlah kembali harus sesuai jumlah yang dipinjam."
            );
        }

        if (!in_array($kondisi[$id_detail], ['Baik', 'Rusak'], true)) {
            throw new Exception("Kondisi alat tidak valid.");
        }
    }

    // Simpan data pengembalian beserta denda otomatis
    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO pengembalian
            (id_peminjaman, tanggal_kembali, total_denda, catatan)
        VALUES (?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "isis",
        $id_peminjaman,
        $tanggal_kembali,
        $denda,
        $catatan
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Data pengembalian gagal disimpan.");
    }

    $id_pengembalian = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);

    // Simpan detail pengembalian dan perbarui stok
    foreach ($detail_asli as $id_detail => $detail) {
        $id_detail = (int) $id_detail;
        $id_alat = (int) $detail['id_alat'];
        $jumlah = (int) $jumlah_kembali[$id_detail];
        $kondisi_alat = $kondisi[$id_detail];
        $catatan_item = trim(
            (string) ($catatan_alat[$id_detail] ?? '')
        );

        $stmt = mysqli_prepare($koneksi, "
            INSERT INTO detail_pengembalian
                (
                    id_pengembalian,
                    id_detail_peminjaman,
                    jumlah_kembali,
                    kondisi_kembali,
                    catatan
                )
            VALUES (?, ?, ?, ?, ?)
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "iiiss",
            $id_pengembalian,
            $id_detail,
            $jumlah,
            $kondisi_alat,
            $catatan_item
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Detail pengembalian gagal disimpan.");
        }

        mysqli_stmt_close($stmt);

        // Tambahkan stok hanya jika alat kembali dalam kondisi baik
        if ($kondisi_alat == 'Baik') {
            $stmt = mysqli_prepare($koneksi, "
                UPDATE alat
                SET jumlah_stok = jumlah_stok + ?
                WHERE id_alat = ?
            ");

            mysqli_stmt_bind_param($stmt, "ii", $jumlah, $id_alat);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) != 1) {
                mysqli_stmt_close($stmt);
                throw new Exception("Gagal memperbarui stok alat.");
            }

            mysqli_stmt_close($stmt);
        }
    }

    // Ubah status peminjaman
    $stmt = mysqli_prepare($koneksi, "
        UPDATE peminjaman
        SET status = 'Dikembalikan'
        WHERE id_peminjaman = ?
          AND status = 'Dipinjam'
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) != 1) {
        mysqli_stmt_close($stmt);
        throw new Exception("Status peminjaman gagal diperbarui.");
    }

    mysqli_stmt_close($stmt);

    // Catat aktivitas ke log
    $aktivitas = "Memproses pengembalian peminjaman ID "
        . $id_peminjaman;

    $keterangan = "Pengembalian ID " . $id_pengembalian
        . ", tanggal " . $tanggal_kembali
        . ", terlambat " . $hari_terlambat . " hari"
        . ", denda Rp " . number_format($denda, 0, ',', '.');

    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO log_aktivitas
            (id_user, aktivitas, keterangan)
        VALUES (?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $id_petugas,
        $aktivitas,
        $keterangan
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Log aktivitas gagal disimpan.");
    }

    mysqli_stmt_close($stmt);

    // Simpan seluruh perubahan
    mysqli_commit($koneksi);

    echo "<script>
        alert(" . json_encode(
            "Pengembalian berhasil! Terlambat "
            . $hari_terlambat . " hari. Denda Rp"
            . number_format($denda, 0, ',', '.')
        ) . ");
        window.location='pengembalian.php';
    </script>";
    exit;

} catch (Throwable $e) {
    mysqli_rollback($koneksi);

    error_log("Proses pengembalian SIPORA: " . $e->getMessage());

    echo "<script>
        alert(" . json_encode(
            "Pengembalian gagal: " . $e->getMessage()
        ) . ");
        window.location='pengembalian.php';
    </script>";
    exit;
}
?>