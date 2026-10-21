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


$judul = "Log Aktivitas";

// Ambil input pencarian dan filter
$cari = trim($_GET['cari'] ?? '');
$role = $_GET['role'] ?? '';

$role_valid = ['Admin', 'Petugas', 'Peminjam'];

if (!in_array($role, $role_valid, true)) {
    $role = '';
}

// Query utama
$sql = "
    SELECT
        l.id_log,
        l.id_user,
        l.aktivitas,
        l.waktu_aktivitas,
        l.keterangan,
        u.username,
        u.role
    FROM log_aktivitas l
    LEFT JOIN users u ON l.id_user = u.id_user
    WHERE 1 = 1
";

$tipe = '';
$parameter = [];

// Pencarian
if ($cari !== '') {
    $sql .= "
        AND (
            u.username LIKE ?
            OR l.aktivitas LIKE ?
            OR l.keterangan LIKE ?
        )
    ";

    $kata = '%' . $cari . '%';

    $tipe .= 'sss';
    $parameter[] = $kata;
    $parameter[] = $kata;
    $parameter[] = $kata;
}

// Filter role
if ($role !== '') {
    $sql .= " AND u.role = ? ";
    $tipe .= 's';
    $parameter[] = $role;
}

$sql .= " ORDER BY l.waktu_aktivitas DESC, l.id_log DESC";

$stmt = mysqli_prepare($koneksi, $sql);

if ($tipe !== '') {
    mysqli_stmt_bind_param($stmt, $tipe, ...$parameter);
}

mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

include 'header.php';
?>

<div class="container-fluid">

    <div class="mb-3">
        <h4>Log Aktivitas</h4>
        <p class="text-secondary">
            Riwayat aktivitas pengguna dalam sistem SIPORA.
        </p>
    </div>

    <!-- Form pencarian dan filter -->
    <div class="card mb-3">
        <div class="card-body">

            <form method="GET" action="log_aktivitas.php">
                <div class="row g-2">

                    <div class="col-md-6">
                        <label class="form-label">Cari Aktivitas</label>
                        <input
                            type="text"
                            name="cari"
                            class="form-control"
                            placeholder="Nama pengguna, aktivitas, atau keterangan"
                            value="<?= htmlspecialchars($cari); ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Filter Role</label>
                        <select name="role" class="form-select">
                            <option value="">Semua Role</option>

                            <?php foreach ($role_valid as $pilihan): ?>
                                <option
                                    value="<?= htmlspecialchars($pilihan); ?>"
                                    <?= $role === $pilihan ? 'selected' : ''; ?>
                                >
                                    <?= htmlspecialchars($pilihan); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            Cari
                        </button>

                        <a href="log_aktivitas.php"
                           class="btn btn-secondary">
                            Reset
                        </a>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <!-- Tabel log aktivitas -->
    <div class="card">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Riwayat Aktivitas</h5>
                <span class="badge bg-primary">
                    <?= mysqli_num_rows($data); ?> aktivitas
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">

                    <thead class="table-primary">
                        <tr>
                            <th>No.</th>
                            <th>Nama Pengguna</th>
                            <th>Role</th>
                            <th>Aktivitas</th>
                            <th>Keterangan</th>
                            <th>Waktu Aktivitas</th>
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
                                <?= htmlspecialchars(
                                    $d['username'] ?? 'Pengguna dihapus'
                                ); ?>
                            </td>

                            <td>
                                <?php if ($d['role']): ?>
                                    <?= htmlspecialchars($d['role']); ?>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($d['aktivitas']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($d['keterangan'] ?? '-'); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($d['waktu_aktivitas']); ?>
                            </td>
                        </tr>

                    <?php
                        endwhile;
                    else:
                    ?>

                        <tr>
                            <td colspan="6" class="text-center">
                                Tidak ada aktivitas yang ditemukan.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>

                </table>
            </div>

            <small class="text-secondary">
                Aktivitas terbaru ditampilkan terlebih dahulu.
            </small>

        </div>
    </div>

</div>

<?php
mysqli_stmt_close($stmt);
include 'footer.php';
?>