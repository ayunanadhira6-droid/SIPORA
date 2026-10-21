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

$id_user = (int) $_SESSION['id_user'];
$judul = "Ajukan Peminjaman";
$error = "";

// Proses pengajuan peminjaman
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_pinjam = $_POST['tanggal_pinjam'] ?? '';
    $tanggal_kembali = $_POST['tanggal_rencana_kembali'] ?? '';
    $keperluan = trim($_POST['keperluan'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    $jumlah_alat = $_POST['jumlah'] ?? [];
    $detail = [];

    // Validasi pilihan alat dan jumlah
    if (!is_array($jumlah_alat)) {
        $jumlah_alat = [];
        $error = "Data alat tidak valid.";
    }

    foreach ($jumlah_alat as $id => $jumlah) {
        $id = (int) $id;

        if (!is_scalar($jumlah) || !is_numeric($jumlah)) {
            $error = "Jumlah alat tidak valid.";
            break;
        }

        $jumlah = (int) $jumlah;

        if ($jumlah < 0) {
            $error = "Jumlah alat tidak boleh kurang dari nol.";
            break;
        }

        if ($jumlah > 0) {
            if ($id < 1) {
                $error = "Pilihan alat tidak valid.";
                break;
            }

            $detail[$id] = $jumlah;
        }
    }

    // Validasi tanggal
    $tanggal_pinjam_obj = DateTime::createFromFormat('!Y-m-d', $tanggal_pinjam);
    $tanggal_kembali_obj = DateTime::createFromFormat('!Y-m-d', $tanggal_kembali);

    if ($error == '') {
        if (
            !$tanggal_pinjam_obj ||
            $tanggal_pinjam_obj->format('Y-m-d') != $tanggal_pinjam ||
            !$tanggal_kembali_obj ||
            $tanggal_kembali_obj->format('Y-m-d') != $tanggal_kembali
        ) {
            $error = "Tanggal peminjaman tidak valid.";
        } elseif ($tanggal_pinjam < date('Y-m-d')) {
            $error = "Tanggal pinjam tidak boleh sebelum hari ini.";
        } elseif ($tanggal_kembali <= $tanggal_pinjam) {
            $error = "Tanggal kembali harus setelah tanggal pinjam.";
        } elseif ($keperluan == '') {
            $error = "Keperluan peminjaman wajib diisi.";
        } elseif (count($detail) == 0) {
            $error = "Pilih minimal satu jenis alat dan jumlahnya.";
        }
    }

    if ($error == '') {
        mysqli_begin_transaction($koneksi);

        try {
            // Periksa stok terbaru dan kunci data alat
            foreach ($detail as $id => $jumlah) {
                $cek = mysqli_prepare(
                    $koneksi,
                    "SELECT nama_alat, jumlah_stok, kondisi
                     FROM alat
                     WHERE id_alat = ?
                     FOR UPDATE"
                );

                mysqli_stmt_bind_param($cek, "i", $id);
                mysqli_stmt_execute($cek);
                $hasil = mysqli_stmt_get_result($cek);
                $alat_dipilih = mysqli_fetch_assoc($hasil);
                mysqli_stmt_close($cek);

                if (!$alat_dipilih) {
                    throw new Exception("Ada alat yang tidak ditemukan.");
                }

                if ($alat_dipilih['kondisi'] != 'Baik') {
                    throw new Exception(
                        "Alat " . $alat_dipilih['nama_alat'] .
                        " tidak tersedia untuk dipinjam."
                    );
                }

                if ($jumlah > (int) $alat_dipilih['jumlah_stok']) {
                    throw new Exception(
                        "Jumlah " . $alat_dipilih['nama_alat'] .
                        " melebihi stok yang tersedia."
                    );
                }
            }

            // Simpan pengajuan dengan status Menunggu
            $simpan = mysqli_prepare(
                $koneksi,
                "INSERT INTO peminjaman
                (id_user, tanggal_pinjam, tanggal_rencana_kembali,
                 keperluan, status, catatan)
                VALUES (?, ?, ?, ?, 'Menunggu', ?)"
            );

            mysqli_stmt_bind_param(
                $simpan,
                "issss",
                $id_user,
                $tanggal_pinjam,
                $tanggal_kembali,
                $keperluan,
                $catatan
            );

            if (!mysqli_stmt_execute($simpan)) {
                throw new Exception("Pengajuan peminjaman gagal disimpan.");
            }

            $id_peminjaman = mysqli_insert_id($koneksi);
            mysqli_stmt_close($simpan);

            // Simpan rincian alat yang diajukan
            $simpan_detail = mysqli_prepare(
                $koneksi,
                "INSERT INTO detail_peminjaman
                (id_peminjaman, id_alat, jumlah_pinjam)
                VALUES (?, ?, ?)"
            );

            foreach ($detail as $id => $jumlah) {
                mysqli_stmt_bind_param(
                    $simpan_detail,
                    "iii",
                    $id_peminjaman,
                    $id,
                    $jumlah
                );

                if (!mysqli_stmt_execute($simpan_detail)) {
                    throw new Exception("Rincian alat gagal disimpan.");
                }
            }

            mysqli_stmt_close($simpan_detail);

            // Catat aktivitas peminjam
            $aktivitas = "Mengajukan peminjaman";
            $keterangan_log = "Pengajuan peminjaman ID " .
                $id_peminjaman . " berhasil dibuat.";

            $log = mysqli_prepare(
                $koneksi,
                "INSERT INTO log_aktivitas
                (id_user, aktivitas, keterangan)
                VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $log,
                "iss",
                $id_user,
                $aktivitas,
                $keterangan_log
            );

            if (!mysqli_stmt_execute($log)) {
                throw new Exception("Log aktivitas gagal disimpan.");
            }

            mysqli_stmt_close($log);

            mysqli_commit($koneksi);

            echo "<script>
                alert('Pengajuan peminjaman berhasil dikirim!');
                window.location='peminjaman.php';
            </script>";
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            error_log("Pengajuan SIPORA: " . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}

// Ambil daftar alat yang tersedia
$alat = mysqli_query(
    $koneksi,
    "SELECT id_alat, nama_alat, jumlah_stok
     FROM alat
     WHERE kondisi = 'Baik' AND jumlah_stok > 0
     ORDER BY nama_alat ASC"
);

include 'header.php';
?>

<div class="mb-3">
    <h4 class="fw-bold mb-1">Form Pengajuan Peminjaman</h4>
    <p class="text-secondary mb-0">
        Lengkapi data dan pilih alat olahraga yang ingin dipinjam.
    </p>
</div>

<div class="card">
    <div class="card-body">

        <?php if ($error != ''): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($alat && mysqli_num_rows($alat) > 0): ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Nama Peminjam</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars($_SESSION['username']) ?>"
                        readonly
                    >
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Pinjam</label>
                        <input
                            type="date"
                            name="tanggal_pinjam"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $_POST['tanggal_pinjam'] ?? date('Y-m-d')
                            ) ?>"
                            min="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rencana Kembali</label>
                        <input
                            type="date"
                            name="tanggal_rencana_kembali"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $_POST['tanggal_rencana_kembali'] ?? ''
                            ) ?>"
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                            required
                        >
                    </div>
                </div>

                <hr>

                <h6 class="fw-bold">Alat yang Dipinjam</h6>

                <p class="text-secondary small">
                    Masukkan jumlah alat yang diinginkan. Isi 0 jika tidak
                    ingin meminjam alat tersebut.
                </p>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>No.</th>
                                <th>Nama Alat</th>
                                <th>Stok Tersedia</th>
                                <th style="width: 160px;">Jumlah Pinjam</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $no = 1; ?>

                            <?php while ($a = mysqli_fetch_assoc($alat)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>

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
                                            value="<?= htmlspecialchars(
                                                $_POST['jumlah'][$a['id_alat']] ?? '0'
                                            ) ?>"
                                        >
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keperluan</label>
                    <input
                        type="text"
                        name="keperluan"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['keperluan'] ?? '') ?>"
                        placeholder="Contoh: Kegiatan olahraga sekolah"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea
                        name="catatan"
                        class="form-control"
                        rows="3"
                        placeholder="Tambahkan catatan jika diperlukan"
                    ><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    Ajukan Peminjaman
                </button>

                <a href="peminjaman.php" class="btn btn-secondary">
                    Kembali
                </a>

            </form>

        <?php else: ?>

            <div class="alert alert-info">
                Belum ada alat yang tersedia untuk dipinjam.
            </div>

            <a href="peminjaman.php" class="btn btn-secondary">
                Kembali
            </a>

        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>