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

$judul = "Data Peminjaman";

// Ambil data peminjaman beserta peminjam dan petugas
$data = mysqli_query($koneksi, "
    SELECT
        p.id_peminjaman,
        p.tanggal_pinjam,
        p.tanggal_rencana_kembali,
        p.keperluan,
        p.status,
        p.catatan,
        u.username AS nama_peminjam,
        petugas.username AS nama_petugas,
        GROUP_CONCAT(
            CONCAT(a.nama_alat, ' (', dp.jumlah_pinjam, ')')
            SEPARATOR ', '
        ) AS daftar_alat
    FROM peminjaman p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN users petugas ON p.id_petugas = petugas.id_user
    LEFT JOIN detail_peminjaman dp
        ON p.id_peminjaman = dp.id_peminjaman
    LEFT JOIN alat a ON dp.id_alat = a.id_alat
    GROUP BY
        p.id_peminjaman,
        p.tanggal_pinjam,
        p.tanggal_rencana_kembali,
        p.keperluan,
        p.status,
        p.catatan,
        u.username,
        petugas.username
    ORDER BY p.id_peminjaman DESC
");

include 'header.php';
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4>Data Peminjaman</h4>
            <p class="text-secondary mb-0">
                Halaman ini hanya untuk melihat data dan status peminjaman.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>No.</th>
                            <th>Peminjam</th>
                            <th>Daftar Alat</th>
                            <th>Tanggal Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Petugas</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php
                    $no = 1;

                    if (mysqli_num_rows($data) > 0):
                        while ($d = mysqli_fetch_assoc($data)):
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>

                            <td>
                                <?= htmlspecialchars($d['nama_peminjam']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($d['daftar_alat'] ?? '-'); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($d['tanggal_pinjam']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($d['tanggal_rencana_kembali']); ?>

                                <?php if (
                                    $d['status'] == 'Dipinjam' &&
                                    $d['tanggal_rencana_kembali'] < date('Y-m-d')
                                ): ?>
                                    <br>
                                    <span class="badge bg-danger">
                                        Terlambat
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($d['nama_petugas'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php
                                $warna = 'secondary';

                                if ($d['status'] == 'Menunggu') {
                                    $warna = 'warning';
                                } elseif ($d['status'] == 'Disetujui') {
                                    $warna = 'info';
                                } elseif ($d['status'] == 'Dipinjam') {
                                    $warna = 'primary';
                                } elseif ($d['status'] == 'Dikembalikan') {
                                    $warna = 'success';
                                } elseif ($d['status'] == 'Ditolak') {
                                    $warna = 'danger';
                                }
                                ?>
                                <span class="badge bg-<?= $warna; ?>">
                                    <?= htmlspecialchars($d['status']); ?>
                                </span>
                            </td>

                            <td>
                                <strong>Keperluan:</strong>
                                <?= htmlspecialchars($d['keperluan']); ?>
                                <br>

                                <strong>Catatan:</strong>
                                <?= htmlspecialchars($d['catatan'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="9" class="text-center">
                                Belum ada data peminjaman.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <small class="text-secondary">
                Catatan: jumlah alat dalam daftar ditampilkan dalam format
                nama alat (jumlah). Contoh: Bola (3), Raket (2).
            </small>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
