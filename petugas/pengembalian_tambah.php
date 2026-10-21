<?php
session_name('SIPORA_PETUGAS');
session_start();

include '../koneksi.php';

// Cek login Petugas
if (
    !isset($_SESSION['id_user'], $_SESSION['role']) ||
    $_SESSION['role'] != 'Petugas'
) {
    header("Location: ../index.php");
    exit;
}

/** @var mysqli $koneksi */

$id_peminjaman = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($koneksi, "
    SELECT p.*, u.username AS nama_peminjam
    FROM peminjaman p
    JOIN users u ON p.id_user = u.id_user
    WHERE p.id_peminjaman = ?
      AND p.status = 'Dipinjam'
");
mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$peminjaman = mysqli_fetch_assoc($hasil);
mysqli_stmt_close($stmt);

if (!$peminjaman) {
    echo "<script>
        alert('Peminjaman tidak ditemukan atau sudah tidak berstatus Dipinjam.');
        window.location='pengembalian.php';
    </script>";
    exit;
}

$stmt = mysqli_prepare($koneksi, "
    SELECT dp.id_detail_peminjaman, dp.jumlah_pinjam,
           a.nama_alat
    FROM detail_peminjaman dp
    JOIN alat a ON dp.id_alat = a.id_alat
    WHERE dp.id_peminjaman = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
mysqli_stmt_execute($stmt);
$hasil_alat = mysqli_stmt_get_result($stmt);

$daftar_alat = [];
while ($baris = mysqli_fetch_assoc($hasil_alat)) {
    $daftar_alat[] = $baris;
}
mysqli_stmt_close($stmt);

if (count($daftar_alat) == 0) {
    die("Detail alat peminjaman tidak ditemukan.");
}

$judul = "Proses Pengembalian";
include 'header.php';
?>

<div class="container-fluid">
    <div class="card mb-3">
        <div class="card-body">
            <h4>Form Pengembalian Alat</h4>

            <p class="mb-1">
                <strong>ID Peminjaman:</strong>
                <?= (int) $peminjaman['id_peminjaman']; ?>
            </p>

            <p class="mb-1">
                <strong>Peminjam:</strong>
                <?= htmlspecialchars($peminjaman['nama_peminjam']); ?>
            </p>

            <p class="mb-0">
                <strong>Rencana Kembali:</strong>
                <?= htmlspecialchars($peminjaman['tanggal_rencana_kembali']); ?>
            </p>
        </div>
    </div>

    <form action="pengembalian_proses.php" method="POST">
        <input type="hidden" name="id_peminjaman"
               value="<?= (int) $peminjaman['id_peminjaman']; ?>">

        <div class="card mb-3">
            <div class="card-body">
                <h5>Rincian Alat</h5>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>Nama Alat</th>
                                <th>Jumlah Dipinjam</th>
                                <th>Jumlah Dikembalikan</th>
                                <th>Kondisi Saat Kembali</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($daftar_alat as $a): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($a['nama_alat']); ?>
                                </td>

                                <td>
                                    <?= (int) $a['jumlah_pinjam']; ?>
                                </td>

                                <td>
                                    <input type="number"
                                           class="form-control"
                                           value="<?= (int) $a['jumlah_pinjam']; ?>"
                                           readonly>

                                    <input type="hidden"
                                           name="jumlah_kembali[<?= (int) $a['id_detail_peminjaman']; ?>]"
                                           value="<?= (int) $a['jumlah_pinjam']; ?>">
                                </td>

                                <td>
                                    <select class="form-select"
                                            name="kondisi[<?= (int) $a['id_detail_peminjaman']; ?>]"
                                            required>
                                        <option value="Baik">Baik</option>
                                        <option value="Rusak">Rusak</option>
                                    </select>
                                </td>

                                <td>
                                    <input type="text"
                                           class="form-control"
                                           name="catatan_alat[<?= (int) $a['id_detail_peminjaman']; ?>]"
                                           placeholder="Opsional">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Tanggal Kembali</label>

                    <input type="date"
                           name="tanggal_kembali"
                           class="form-control"
                           value="<?= date('Y-m-d'); ?>"
                           max="<?= date('Y-m-d'); ?>"
                           required>
                </div>

                <div class="alert alert-info">
                    <strong>Informasi Denda</strong>
                    <p class="mb-0">
                        Denda dihitung otomatis sebesar Rp5.000 per hari
                        jika pengembalian melewati tanggal rencana kembali.
                    </p>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan Pengembalian</label>

                    <textarea name="catatan"
                              class="form-control"
                              rows="3"
                              placeholder="Catatan umum pengembalian (opsional)"></textarea>
                </div>

                <button type="submit"
                        class="btn btn-success"
                        onclick="return confirm('Simpan pengembalian? Pastikan kondisi setiap alat sudah benar.')">
                    Simpan Pengembalian
                </button>

                <a href="pengembalian.php" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
