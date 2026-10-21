<?php
session_name('SIPORA_PETUGAS');
session_start();

include '../koneksi.php';

/** @var mysqli $koneksi */

// Cek login Petugas
if (
    !isset($_SESSION['id_user'], $_SESSION['role']) ||
    $_SESSION['role'] != 'Petugas'
) {
    header("Location: ../index.php");
    exit;
}

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

<div class="mb-3">
    <h4 class="mb-1">Data Alat Olahraga</h4>
    <p class="text-secondary mb-0">
        Lihat daftar alat, stok, kategori, dan kondisi alat olahraga.
    </p>
</div>

<div class="card">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">

                <thead class="table-primary">
                    <tr>
                        <th>No.</th>
                        <th>Nama Alat</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Kondisi</th>
                        <th>Keterangan</th>
                        <th>Tanggal Dibuat</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($data && mysqli_num_rows($data) > 0): ?>
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
                                    <?php if ($d['kondisi'] == 'Baik'): ?>
                                        <span class="badge bg-success">
                                            Baik
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            Rusak
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['keterangan'] ?? '') ?>
                                </td>

                                <td>
                                    <?= !empty($d['created_at'])
                                        ? date('d-m-Y', strtotime($d['created_at']))
                                        : '-' ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
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