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
                Periksa pengajuan, setujui atau tolak peminjaman,
                dan catat penyerahan alat olahraga.
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
                            <th>Opsi</th>
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

                            <td>
                                <div class="d-flex flex-column gap-1">

                                <?php if ($d['status'] == 'Menunggu'): ?>

                                    <form action="peminjaman_status.php"
                                          method="POST"
                                          onsubmit="return confirm('Setujui pengajuan ini?')">
                                        <input type="hidden" name="id_peminjaman"
                                               value="<?= $d['id_peminjaman']; ?>">
                                        <input type="hidden" name="aksi"
                                               value="setujui">
                                        <button type="submit"
                                                class="btn btn-sm btn-success">
                                            Setujui
                                        </button>
                                    </form>

                                    <form action="peminjaman_status.php"
                                          method="POST"
                                          onsubmit="return confirm('Tolak pengajuan ini?')">
                                        <input type="hidden" name="id_peminjaman"
                                               value="<?= $d['id_peminjaman']; ?>">
                                        <input type="hidden" name="aksi"
                                               value="tolak">
                                        <button type="submit"
                                                class="btn btn-sm btn-danger">
                                            Tolak
                                        </button>
                                    </form>

                                <?php elseif ($d['status'] == 'Disetujui'): ?>

                                    <form action="peminjaman_status.php"
                                          method="POST"
                                          onsubmit="return confirm('Catat bahwa alat sudah diserahkan? Stok akan berkurang.')">
                                        <input type="hidden" name="id_peminjaman"
                                               value="<?= $d['id_peminjaman']; ?>">
                                        <input type="hidden" name="aksi"
                                               value="serahkan">
                                        <button type="submit"
                                                class="btn btn-sm btn-primary">
                                            Serahkan Alat
                                        </button>
                                    </form>

                                <?php elseif ($d['status'] == 'Dipinjam'): ?>

                                    <span class="text-secondary">
                                        Menunggu pengembalian
                                    </span>

                                <?php elseif ($d['status'] == 'Dikembalikan'): ?>

                                    <span class="text-success">
                                        Selesai
                                    </span>

                                <?php else: ?>

                                    <span class="text-secondary">
                                        Tidak ada tindakan
                                    </span>

                                <?php endif; ?>

                                </div>
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
