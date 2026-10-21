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

$judul = "Data Kategori";

include 'header.php';

// Mengambil data kategori
$data = mysqli_query($koneksi, "
    SELECT id_kategori, nama_kategori, keterangan, created_at
    FROM kategori
    ORDER BY id_kategori DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Data Kategori</h4>

    <a href="kategori_tambah.php" class="btn btn-primary">
        + Tambah Kategori
    </a>
</div>

<div class="card">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>No.</th>
                        <th>Nama Kategori</th>
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
                                    <?= htmlspecialchars($d['nama_kategori']) ?>
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
                                    <a href="kategori_edit.php?id=<?= $d['id_kategori'] ?>"
                                       class="btn btn-info btn-sm">
                                        Edit
                                    </a>

                                    <a href="kategori_hapus.php?id=<?= $d['id_kategori'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                Belum ada data kategori.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>
