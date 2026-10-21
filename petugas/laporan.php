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

// Validasi status
if (!is_string($status) || !in_array($status, $status_valid, true)) {
    $status = '';
}

// Validasi format tanggal
foreach (['tanggal_mulai', 'tanggal_akhir'] as $nama_tanggal) {
    if (!is_string($$nama_tanggal)) {
        die("Format tanggal filter tidak valid.");
    }

    if ($$nama_tanggal !== '') {
        $tanggal_obj = DateTime::createFromFormat(
            '!Y-m-d',
            $$nama_tanggal
        );

        if (
            !$tanggal_obj ||
            $tanggal_obj->format('Y-m-d') != $$nama_tanggal
        ) {
            die("Format tanggal filter tidak valid.");
        }
    }
}

// Validasi rentang tanggal
if (
    $tanggal_mulai !== '' &&
    $tanggal_akhir !== '' &&
    $tanggal_mulai > $tanggal_akhir
) {
    die("Tanggal mulai tidak boleh melebihi tanggal akhir.");
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

if (!$stmt) {
    die("Query laporan gagal disiapkan.");
}

if ($tipe !== '') {
    mysqli_stmt_bind_param($stmt, $tipe, ...$parameter);
}

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    die("Laporan gagal ditampilkan.");
}

$data = mysqli_stmt_get_result($stmt);
$total_laporan = mysqli_num_rows($data);

include 'header.php';
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4>Laporan Peminjaman</h4>
            <p class="text-secondary mb-0">
                Lihat dan cetak laporan peminjaman alat olahraga.
            </p>
        </div>

        <button type="button"
                class="btn btn-primary"
                onclick="window.print()">
            Cetak Laporan
        </button>
    </div>

    <!-- Form Filter -->
    <div class="card mb-4 no-print">
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
                            <option value="<?= htmlspecialchars($pilihan) ?>"
                                <?= $status === $pilihan ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pilihan) ?>
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
                                    <span class="badge bg-<?= $warna ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    Rp <?= number_format(
                                        (float) ($row['total_denda'] ?? 0),
                                        0,
                                        ',',
                                        '.'
                                    ) ?>
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
    .no-print,
    button {
        display: none !important;
    }

    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
    }

    .content {
        padding: 0 !important;
    }

    body {
        background: white !important;
        font-size: 12px;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    table {
        width: 100% !important;
    }
}
</style>

<?php
mysqli_stmt_close($stmt);
include 'footer.php';
?>