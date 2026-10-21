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

$judul = "Data Pengembalian";

$data = mysqli_query($koneksi, "
    SELECT
        pg.id_pengembalian,
        pg.tanggal_kembali,
        pg.total_denda,
        pg.catatan,
        p.id_peminjaman,
        p.tanggal_rencana_kembali,
        u.username AS nama_peminjam,
        GROUP_CONCAT(
            CONCAT(a.nama_alat, ' (', dp.jumlah_pinjam, ')')
            SEPARATOR ', '
        ) AS daftar_alat
    FROM pengembalian pg
    JOIN peminjaman p
        ON pg.id_peminjaman = p.id_peminjaman
    JOIN users u
        ON p.id_user = u.id_user
    LEFT JOIN detail_peminjaman dp
        ON p.id_peminjaman = dp.id_peminjaman
    LEFT JOIN alat a
        ON dp.id_alat = a.id_alat
    GROUP BY
        pg.id_pengembalian,
        pg.tanggal_kembali,
        pg.total_denda,
        pg.catatan,
        p.id_peminjaman,
        p.tanggal_rencana_kembali,
        u.username
    ORDER BY pg.id_pengembalian DESC
");

$belum_kembali = mysqli_query($koneksi, "
    SELECT
        p.id_peminjaman,
        p.tanggal_pinjam,
        p.tanggal_rencana_kembali,
        u.username AS nama_peminjam
    FROM peminjaman p
    JOIN users u ON p.id_user = u.id_user
    WHERE p.status = 'Dipinjam'
    ORDER BY p.tanggal_rencana_kembali ASC
");

include 'header.php';
?>

<div class="container-fluid">

    <div class="mb-3">
        <h4>Data Pengembalian</h4>
        <p class="text-secondary">
            Kelola pengembalian alat olahraga dan pantau keterlambatan.
        </p>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5>Peminjaman yang Belum Dikembalikan</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>No.</th>
                            <th>ID Peminjaman</th>
                            <th>Peminjam</th>
                            <th>Tanggal Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Status Waktu</th>
                            <th>Opsi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;

                    if (mysqli_num_rows($belum_kembali) > 0):
                        while ($b = mysqli_fetch_assoc($belum_kembali)):
                            $terlambat =
                                $b['tanggal_rencana_kembali'] < date('Y-m-d');
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= (int) $b['id_peminjaman']; ?></td>
                            <td><?= htmlspecialchars($b['nama_peminjam']); ?></td>
                            <td><?= htmlspecialchars($b['tanggal_pinjam']); ?></td>
                            <td><?= htmlspecialchars($b['tanggal_rencana_kembali']); ?></td>
                            <td>
                                <?php if ($terlambat): ?>
                                    <span class="badge bg-danger">Terlambat</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Belum Terlambat</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-secondary">Hanya dapat dilihat</span>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                Tidak ada peminjaman yang menunggu pengembalian.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5>Riwayat Pengembalian</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>No.</th>
                            <th>ID Peminjaman</th>
                            <th>Peminjam</th>
                            <th>Daftar Alat</th>
                            <th>Rencana Kembali</th>
                            <th>Tanggal Kembali</th>
                            <th>Denda</th>
                            <th>Catatan</th>
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
                            <td><?= (int) $d['id_peminjaman']; ?></td>
                            <td><?= htmlspecialchars($d['nama_peminjam']); ?></td>
                            <td><?= htmlspecialchars($d['daftar_alat'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($d['tanggal_rencana_kembali']); ?></td>
                            <td><?= htmlspecialchars($d['tanggal_kembali']); ?></td>
                            <td>
                                Rp <?= number_format((float) $d['total_denda'], 0, ',', '.'); ?>
                            </td>
                            <td><?= htmlspecialchars($d['catatan'] ?? '-'); ?></td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                Belum ada riwayat pengembalian.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
