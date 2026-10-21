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

// Menghitung jumlah alat
$alat = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM alat");
$total_alat = mysqli_fetch_assoc($alat)['total'];

// Menghitung jumlah pengguna
$user = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM users");
$total_user = mysqli_fetch_assoc($user)['total'];

// Menghitung peminjaman yang menunggu
$menunggu = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM peminjaman
    WHERE status = 'Menunggu'
");
$total_menunggu = mysqli_fetch_assoc($menunggu)['total'];

// Menghitung peminjaman yang disetujui atau sedang dipinjam
$dipinjam = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM peminjaman
    WHERE status IN ('Disetujui', 'Dipinjam')
");
$total_dipinjam = mysqli_fetch_assoc($dipinjam)['total'];

// Mengambil 5 peminjaman terbaru
$data = mysqli_query($koneksi, "
    SELECT peminjaman.*, users.username
    FROM peminjaman
    JOIN users ON peminjaman.id_user = users.id_user
    ORDER BY peminjaman.id_peminjaman DESC
    LIMIT 5
");

// Judul halaman
$judul = "Dashboard Administrator";

// Memanggil header
include 'header.php';
?>

<h4 class="fw-bold mb-1">Dashboard</h4>

<p class="text-secondary">
    Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!
    Berikut ringkasan data SIPORA.
</p>

<!-- Ringkasan Data -->
<div class="row g-3 mb-4">

    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Total Alat</p>
                <h2 class="text-primary"><?= $total_alat ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Total Pengguna</p>
                <h2 class="text-success"><?= $total_user ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Menunggu</p>
                <h2 class="text-warning"><?= $total_menunggu ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Sedang Dipinjam</p>
                <h2 class="text-danger"><?= $total_dipinjam ?></h2>
            </div>
        </div>
    </div>

</div>

<!-- Peminjaman Terbaru -->
<div class="card">
    <div class="card-body">

        <h5 class="fw-bold mb-3">Peminjaman Terbaru</h5>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Tanggal Pinjam</th>
                        <th>Rencana Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($data) > 0): ?>
                        <?php $no = 1; ?>

                        <?php while ($d = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($d['username']) ?></td>
                                <td>
                                    <?= date('d-m-Y', strtotime($d['tanggal_pinjam'])) ?>
                                </td>
                                <td>
                                    <?= date('d-m-Y', strtotime($d['tanggal_rencana_kembali'])) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($d['status']) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                Belum ada data peminjaman.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php
// Memanggil footer
include 'footer.php';
?>
