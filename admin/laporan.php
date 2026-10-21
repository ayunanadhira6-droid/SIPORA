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

$judul = "Laporan Peminjaman";

// Mengambil filter
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$status = $_GET['status'] ?? '';

$status_valid = [
    'Menunggu',
    'Disetujui',
    'Ditolak',
    'Dipinjam',
    'Dikembalikan'
];

if (!in_array($status, $status_valid, true)) {
    $status = '';
}

// Query laporan
$sql = "
    SELECT
        p.id_peminjaman,
        u.username,
        p.tanggal_pinjam,
        p.tanggal_rencana_kembali,
        p.keperluan,
        p.status,
        GROUP_CONCAT(
            CONCAT(a.nama_alat, ' (', dp.jumlah_pinjam, ')')
            SEPARATOR ', '
        ) AS daftar_alat,
        pg.tanggal_kembali,
        pg.total_denda
    FROM peminjaman p
    JOIN users u
        ON p.id_user = u.id_user
    LEFT JOIN detail_peminjaman dp
        ON p.id_peminjaman = dp.id_peminjaman
    LEFT JOIN alat a
        ON dp.id_alat = a.id_alat
    LEFT JOIN pengembalian pg
        ON p.id_peminjaman = pg.id_peminjaman
    WHERE 1=1
";

$tipe = '';
$parameter = [];

// Filter tanggal mulai
if ($tanggal_mulai !== '') {
    $sql .= " AND p.tanggal_pinjam >= ?";
    $tipe .= 's';
    $parameter[] = $tanggal_mulai;
}

// Filter tanggal akhir
if ($tanggal_akhir !== '') {
    $sql .= " AND p.tanggal_pinjam <= ?";
    $tipe .= 's';
    $parameter[] = $tanggal_akhir;
}

// Filter status
if ($status !== '') {
    $sql .= " AND p.status = ?";
    $tipe .= 's';
    $parameter[] = $status;
}

$sql .= "
    GROUP BY
        p.id_peminjaman,
        u.username,
        p.tanggal_pinjam,
        p.tanggal_rencana_kembali,
        p.keperluan,
        p.status,
        pg.tanggal_kembali,
        pg.total_denda
    ORDER BY p.tanggal_pinjam DESC, p.id_peminjaman DESC
";

$stmt = mysqli_prepare($koneksi, $sql);

if ($tipe !== '') {
    mysqli_stmt_bind_param($stmt, $tipe, ...$parameter);
}

mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

// Menghitung jumlah laporan
$total_laporan = mysqli_num_rows($data);

include 'header.php';
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Laporan Peminjaman</h4>

        <button type="button"
                class="btn btn-primary"
                onclick="window.print()">
            Cetak Laporan
        </button>
    </div>

    <!-- Form Filter -->
    <div class="card mb-4">
        <div class="card-body">

            <form method="GET" class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date"
                           name="tanggal_mulai"
                           class="form-control"
                           value="<?= htmlspecialchars($tanggal_mulai) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date"
                           name="tanggal_akhir"
                           class="form-control"
                           value="<?= htmlspecialchars($tanggal_akhir) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status Peminjaman</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>

                        <?php foreach ($status_valid as $pilihan): ?>
                            <option value="<?= $pilihan ?>"
                                <?= $status === $pilihan ? 'selected' : '' ?>>
                                <?= $pilihan ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        Tampilkan
                    </button>

                    <a href="laporan.php" class="btn btn-secondary">
                        Reset
                    </a>
                </div>

            </form>

        </div>
    </div>

    <!-- Ringkasan -->
    <div class="card mb-3">
        <div class="card-body">
            <strong>Total Data Laporan:</strong>
            <?= $total_laporan ?> peminjaman
        </div>
    </div>

    <!-- Tabel Laporan -->
    <div class="card">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">

                    <thead class="table-primary">
                        <tr>
                            <th>No.</th>
                            <th>Peminjam</th>
                            <th>Daftar Alat (Jumlah)</th>
                            <th>Tanggal Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Tanggal Kembali</th>
                            <th>Keperluan</th>
                            <th>Status</th>
                            <th>Denda</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($total_laporan > 0): ?>
                            <?php $no = 1; ?>

                            <?php while ($row = mysqli_fetch_assoc($data)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>

                                    <td>
                                        <?= htmlspecialchars($row['username']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['daftar_alat'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['tanggal_pinjam']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['tanggal_rencana_kembali']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['tanggal_kembali'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['keperluan'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?php
                                        $warna = 'secondary';

                                        if ($row['status'] == 'Menunggu') {
                                            $warna = 'warning';
                                        } elseif ($row['status'] == 'Disetujui') {
                                            $warna = 'info';
                                        } elseif ($row['status'] == 'Dipinjam') {
                                            $warna = 'primary';
                                        } elseif ($row['status'] == 'Dikembalikan') {
                                            $warna = 'success';
                                        } elseif ($row['status'] == 'Ditolak') {
                                            $warna = 'danger';
                                        }
                                        ?>

                                        <span class="badge bg-<?= $warna ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        Rp <?= number_format((float)($row['total_denda'] ?? 0), 0, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    Tidak ada data laporan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<style>
@media print {
    .sidebar,
    .topbar,
    footer,
    form,
    button {
        display: none !important;
    }

    .main-content {
        margin-left: 0 !important;
    }

    .content {
        padding: 0 !important;
    }

    body {
        background: white !important;
    }

    .card {
        border: none !important;
    }
}
</style>

<?php
mysqli_stmt_close($stmt);
include 'footer.php';
?>
