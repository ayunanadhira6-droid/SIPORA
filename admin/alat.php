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

$judul = "Data Alat";

include 'header.php';

// Mengambil data alat beserta nama kategori
$data = mysqli_query($koneksi, "
    SELECT alat.*, kategori.nama_kategori
    FROM alat
    JOIN kategori ON alat.id_kategori = kategori.id_kategori
    ORDER BY alat.id_alat DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Data Alat Olahraga</h4>

    <a href="alat_tambah.php" class="btn btn-primary">
        + Tambah Alat
    </a>
</div>

<div class="card">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>No.</th>
                        <th>Nama Alat</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Kondisi</th>
                        <th>Keterangan</th>
                        <th>Tanggal Dibuat</th>
                        <th>Opsi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($data) > 0): ?>
                        <?php $no = 1; ?>

                        <?php while ($d = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= htmlspecialchars($d['nama_alat']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['nama_kategori']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['jumlah_stok']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['kondisi']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['keterangan'] ?? '') ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($d['created_at'])
                                    ) ?>
                                </td>

                                <td>
                                    <a href="alat_edit.php?id=<?= $d['id_alat'] ?>"
                                       class="btn btn-info btn-sm">
                                        Edit
                                    </a>

                                    <a href="alat_hapus.php?id=<?= $d['id_alat'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin ingin menghapus alat ini?')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                Belum ada data alat.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>
