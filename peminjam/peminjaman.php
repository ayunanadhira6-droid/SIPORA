
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

$id_user = (int) $_SESSION['id_user'];
$judul = "Ajukan Peminjaman";

// Ambil alat yang layak dipinjam dan stok tersedia
$alat = mysqli_query($koneksi, "
    SELECT id_alat, nama_alat, jumlah_stok
    FROM alat
    WHERE kondisi = 'Baik'
      AND jumlah_stok > 0
    ORDER BY nama_alat ASC
");

// Ambil riwayat peminjaman beserta catatan dan denda
$data = mysqli_query($koneksi, "
    SELECT
        p.id_peminjaman,
        p.tanggal_pinjam,
        p.tanggal_rencana_kembali,
        p.keperluan,
        p.status,
        p.catatan,
        pg.total_denda,
        GROUP_CONCAT(
            CONCAT(a.nama_alat, ' (', dp.jumlah_pinjam, ')')
            SEPARATOR ', '
        ) AS daftar_alat
    FROM peminjaman p
    LEFT JOIN detail_peminjaman dp
        ON p.id_peminjaman = dp.id_peminjaman
    LEFT JOIN alat a
        ON dp.id_alat = a.id_alat
    LEFT JOIN pengembalian pg
        ON p.id_peminjaman = pg.id_peminjaman
    WHERE p.id_user = $id_user
    GROUP BY
        p.id_peminjaman,
        p.tanggal_pinjam,
        p.tanggal_rencana_kembali,
        p.keperluan,
        p.status,
        p.catatan,
        pg.total_denda
    ORDER BY p.id_peminjaman DESC
");

include 'header.php';
?>

<div class="mb-3">
    <h4 class="fw-bold mb-1">Ajukan Peminjaman</h4>
    <p class="text-secondary mb-0">
        Pilih alat olahraga yang ingin dipinjam dan lengkapi formulir berikut.
    </p>
</div>

<!-- Form Pengajuan -->
<div class="card mb-4">
    <div class="card-body">

        <h5 class="fw-bold mb-3">Formulir Peminjaman</h5>

        <?php if ($alat && mysqli_num_rows($alat) > 0): ?>

            <form action="peminjaman_tambah.php" method="POST">

                <div class="mb-3">
                    <label class="form-label">Tanggal Pinjam</label>
                    <input
                        type="date"
                        name="tanggal_pinjam"
                        class="form-control"
                        value="<?= date('Y-m-d') ?>"
                        min="<?= date('Y-m-d') ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Rencana Tanggal Kembali</label>
                    <input
                        type="date"
                        name="tanggal_rencana_kembali"
                        class="form-control"
                        min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Keperluan</label>
                    <textarea
                        name="keperluan"
                        class="form-control"
                        rows="3"
                        placeholder="Contoh: Kegiatan olahraga sekolah"
                        required
                    ></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Pilih Alat dan Jumlah</label>

                    <p class="text-secondary small">
                        Isi jumlah alat yang ingin dipinjam. Isi 0 jika tidak ingin memilih alat tersebut.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>Nama Alat</th>
                                    <th>Stok Tersedia</th>
                                    <th style="width: 150px;">Jumlah Pinjam</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($a = mysqli_fetch_assoc($alat)): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($a['nama_alat']) ?>
                                        </td>

                                        <td>
                                            <?= (int) $a['jumlah_stok'] ?>
                                        </td>

                                        <td>
                                            <input
                                                type="number"
                                                name="jumlah[<?= (int) $a['id_alat'] ?>]"
                                                class="form-control"
                                                min="0"
                                                max="<?= (int) $a['jumlah_stok'] ?>"
                                                value="0"
                                            >
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea
                        name="catatan"
                        class="form-control"
                        rows="2"
                        placeholder="Tambahkan catatan jika diperlukan"
                    ></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    Ajukan Peminjaman
                </button>

            </form>

        <?php else: ?>

            <div class="alert alert-info mb-0">
                Belum ada alat yang tersedia untuk dipinjam.
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- Riwayat Peminjaman -->
<div class="card">
    <div class="card-body">

        <h5 class="fw-bold mb-3">Riwayat Peminjaman Saya</h5>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">

                <thead class="table-primary">
                    <tr>
                        <th>No.</th>
                        <th>Daftar Alat</th>
                        <th>Tanggal Pinjam</th>
                        <th>Rencana Kembali</th>
                        <th>Keperluan</th>
                        <th>Catatan</th>
                        <th>Denda</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($data && mysqli_num_rows($data) > 0): ?>
                        <?php $no = 1; ?>

                        <?php while ($d = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= htmlspecialchars($d['daftar_alat'] ?? '-') ?>
                                </td>

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
                                    <?= htmlspecialchars($d['keperluan']) ?>
                                </td>

                                <td>
                                    <?= !empty($d['catatan'])
                                        ? nl2br(htmlspecialchars($d['catatan']))
                                        : '-' ?>
                                </td>

                                <td>
                                    <?php if (
                                        $d['status'] == 'Dikembalikan'
                                        && $d['total_denda'] !== null
                                    ): ?>

                                        <span class="<?= (float) $d['total_denda'] > 0
                                            ? 'text-danger fw-bold'
                                            : 'text-success' ?>">
                                            Rp <?= number_format(
                                                (float) $d['total_denda'],
                                                0,
                                                ',',
                                                '.'
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="text-secondary">
                                            Belum dihitung
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php
                                    $warna = 'secondary';

                                    if ($d['status'] == 'Menunggu') {
                                        $warna = 'warning text-dark';
                                    } elseif ($d['status'] == 'Disetujui') {
                                        $warna = 'info text-dark';
                                    } elseif ($d['status'] == 'Dipinjam') {
                                        $warna = 'primary';
                                    } elseif ($d['status'] == 'Dikembalikan') {
                                        $warna = 'success';
                                    } elseif ($d['status'] == 'Ditolak') {
                                        $warna = 'danger';
                                    }
                                    ?>

                                    <span class="badge bg-<?= $warna ?>">
                                        <?= htmlspecialchars($d['status']) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center">
                                Kamu belum memiliki riwayat peminjaman.
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>
