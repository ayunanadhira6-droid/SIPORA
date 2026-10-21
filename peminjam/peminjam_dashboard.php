<?php
session_name('SIPORA_PEMINJAM');
session_start();

include '../koneksi.php';

// Cek login Peminjam
if (
    !isset($_SESSION['id_user'], $_SESSION['role']) ||
    $_SESSION['role'] != 'Peminjam'
) {
    header("Location: ../index.php");
    exit;
}

/** @var mysqli $koneksi */

// Menghitung jumlah jenis alat
$alat = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM alat
");
$total_alat = mysqli_fetch_assoc($alat)['total'];

// Menghitung pengajuan yang menunggu milik peminjam
$id_user = (int) $_SESSION['id_user'];

$menunggu = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM peminjaman
    WHERE id_user = $id_user
    AND status = 'Menunggu'
");
$total_menunggu = mysqli_fetch_assoc($menunggu)['total'];

// Menghitung peminjaman yang sedang berlangsung milik peminjam
$dipinjam = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM peminjaman
    WHERE id_user = $id_user
    AND status = 'Dipinjam'
");
$total_dipinjam = mysqli_fetch_assoc($dipinjam)['total'];

// Mengambil 5 riwayat peminjaman terbaru milik peminjam
$data = mysqli_query($koneksi, "
    SELECT *
    FROM peminjaman
    WHERE id_user = $id_user
    ORDER BY id_peminjaman DESC
    LIMIT 5
");

// Judul halaman
$judul = "Dashboard Peminjam";

// Memanggil header
include 'header.php';
?>

<h4 class="fw-bold mb-1">Dashboard Peminjam</h4>

<p class="text-secondary">
    Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!
    Berikut ringkasan peminjaman alat olahraga milikmu.
</p>

<!-- Ringkasan Data -->
<div class="row g-3 mb-4">

    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Total Jenis Alat</p>
                <h2 class="text-primary"><?= $total_alat ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Menunggu Persetujuan</p>
                <h2 class="text-warning"><?= $total_menunggu ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Sedang Dipinjam</p>
                <h2 class="text-success"><?= $total_dipinjam ?></h2>
            </div>
        </div>
    </div>

</div>

<!-- Riwayat Peminjaman -->
<div class="card">
    <div class="card-body">

        <h5 class="fw-bold mb-3">Riwayat Peminjaman Terbaru</h5>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>No.</th>
                        <th>Tanggal Pinjam</th>
                        <th>Rencana Kembali</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($data) > 0): ?>
                        <?php $no = 1; ?>

                        <?php while ($d = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($d['tanggal_pinjam'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($d['tanggal_rencana_kembali'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $d['keperluan'] ?? '-'
                                    ) ?>
                                </td>

                                <td>
                                    <?php
                                    if ($d['status'] == 'Menunggu') {
                                        $badge = 'bg-warning text-dark';
                                    } elseif ($d['status'] == 'Disetujui') {
                                        $badge = 'bg-info text-dark';
                                    } elseif ($d['status'] == 'Dipinjam') {
                                        $badge = 'bg-primary';
                                    } elseif ($d['status'] == 'Ditolak') {
                                        $badge = 'bg-danger';
                                    } else {
                                        $badge = 'bg-success';
                                    }
                                    ?>

                                    <span class="badge <?= $badge ?>">
                                        <?= htmlspecialchars($d['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                Kamu belum memiliki riwayat peminjaman.
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