<?php
session_name('SIPORA_PEMINJAM');
session_start();

include '../koneksi.php';

/** @var mysqli $koneksi */

// Cek login Peminjam
if (
    !isset($_SESSION['id_user'], $_SESSION['role']) ||
    $_SESSION['role'] != 'Peminjam'
) {
    header("Location: ../index.php");
    exit;
}

$judul = "Daftar Alat";

include 'header.php';

// Mengambil data alat beserta kategori
$data = mysqli_query($koneksi, "
    SELECT alat.*, kategori.nama_kategori
    FROM alat
    JOIN kategori ON alat.id_kategori = kategori.id_kategori
    ORDER BY alat.nama_alat ASC
");
?>

<div class="mb-3">
    <h4 class="mb-1">Daftar Alat Olahraga</h4>
    <p class="text-secondary mb-0">
        Lihat daftar alat olahraga dan jumlah stok yang tersedia.
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
                        <th>Ketersediaan</th>
                        <th>Keterangan</th>
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
                                    <?= (int) $d['jumlah_stok'] ?>
                                </td>

                                <td>
                                    <?php if (
                                        $d['kondisi'] == 'Baik' &&
                                        (int) $d['jumlah_stok'] > 0
                                    ): ?>
                                        <span class="badge bg-success">
                                            Tersedia
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            Tidak Tersedia
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $d['keterangan'] ?? ''
                                    ) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                Belum ada data alat olahraga.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>