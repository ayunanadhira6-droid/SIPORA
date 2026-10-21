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

// Admin hanya memiliki akses melihat data, bukan memproses transaksi.
header("Location: peminjaman.php");
exit;

/** @var mysqli $koneksi */

$judul = "Tambah Peminjaman";
$error = "";

// Proses simpan peminjaman
if (isset($_POST['simpan'])) {
    $id_user = (int) ($_POST['id_user'] ?? 0);
    $tanggal_pinjam = $_POST['tanggal_pinjam'] ?? '';
    $tanggal_kembali = $_POST['tanggal_rencana_kembali'] ?? '';
    $keperluan = trim($_POST['keperluan'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    $id_alat = $_POST['id_alat'] ?? [];
    $jumlah = $_POST['jumlah_pinjam'] ?? [];

    // Ambil alat yang dipilih
    $detail = [];

    foreach ($id_alat as $i => $id) {
        $id = (int) $id;
        $jml = (int) ($jumlah[$i] ?? 0);

        // Lewati baris yang tidak diisi
        if ($id == 0 && $jml == 0) {
            continue;
        }

        if ($id < 1 || $jml < 1) {
            $error = "Pilih alat dan isi jumlahnya dengan benar.";
            break;
        }

        // Hindari alat yang sama dipilih dua kali
        if (isset($detail[$id])) {
            $error = "Alat yang sama tidak boleh dipilih dua kali.";
            break;
        }

        $detail[$id] = $jml;
    }

    if ($error == '') {
        if ($id_user < 1 || $tanggal_pinjam == '' ||
            $tanggal_kembali == '' || $keperluan == '') {
            $error = "Lengkapi data peminjaman terlebih dahulu.";
        } elseif ($tanggal_kembali < $tanggal_pinjam) {
            $error = "Tanggal kembali tidak boleh sebelum tanggal pinjam.";
        } elseif (count($detail) == 0) {
            $error = "Pilih minimal satu jenis alat.";
        }
    }

    if ($error == '') {
        mysqli_begin_transaction($koneksi);

        try {
            // Pastikan pengguna adalah Peminjam
            $cek_user = mysqli_prepare(
                $koneksi,
                "SELECT id_user FROM users
                 WHERE id_user = ? AND role = 'Peminjam'"
            );
            mysqli_stmt_bind_param($cek_user, "i", $id_user);
            mysqli_stmt_execute($cek_user);
            $hasil_user = mysqli_stmt_get_result($cek_user);
            $user_ada = mysqli_num_rows($hasil_user) > 0;
            mysqli_stmt_close($cek_user);

            if (!$user_ada) {
                throw new Exception("Pengguna peminjam tidak ditemukan.");
            }

            // Periksa stok dan kondisi setiap alat
            foreach ($detail as $id => $jml) {
                $cek_alat = mysqli_prepare(
                    $koneksi,
                    "SELECT nama_alat, jumlah_stok, kondisi
                     FROM alat WHERE id_alat = ?"
                );
                mysqli_stmt_bind_param($cek_alat, "i", $id);
                mysqli_stmt_execute($cek_alat);
                $hasil_alat = mysqli_stmt_get_result($cek_alat);
                $alat = mysqli_fetch_assoc($hasil_alat);
                mysqli_stmt_close($cek_alat);

                if (!$alat) {
                    throw new Exception("Ada alat yang tidak ditemukan.");
                }

                if ($alat['kondisi'] != 'Baik') {
                    throw new Exception(
                        "Alat " . $alat['nama_alat'] . " dalam kondisi rusak."
                    );
                }

                if ($jml > $alat['jumlah_stok']) {
                    throw new Exception(
                        "Jumlah " . $alat['nama_alat'] . " melebihi stok."
                    );
                }
            }

            // Simpan data peminjaman
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
                throw new Exception("Peminjaman gagal disimpan.");
            }

            $id_peminjaman = mysqli_insert_id($koneksi);
            mysqli_stmt_close($simpan);

            // Simpan setiap alat yang dipinjam
            $simpan_detail = mysqli_prepare(
                $koneksi,
                "INSERT INTO detail_peminjaman
                (id_peminjaman, id_alat, jumlah_pinjam)
                VALUES (?, ?, ?)"
            );

            foreach ($detail as $id => $jml) {
                mysqli_stmt_bind_param(
                    $simpan_detail,
                    "iii",
                    $id_peminjaman,
                    $id,
                    $jml
                );

                if (!mysqli_stmt_execute($simpan_detail)) {
                    throw new Exception("Detail alat gagal disimpan.");
                }
            }

            mysqli_stmt_close($simpan_detail);

            // Catat aktivitas ke log
            $admin = (int) $_SESSION['id_user'];
            $aktivitas = "Menambah peminjaman";
            $keterangan_log = "Menambahkan peminjaman ID "
                . $id_peminjaman . " untuk pengguna ID " . $id_user;

            $log = mysqli_prepare(
                $koneksi,
                "INSERT INTO log_aktivitas
                (id_user, aktivitas, keterangan)
                VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $log,
                "iss",
                $admin,
                $aktivitas,
                $keterangan_log
            );

            if (!mysqli_stmt_execute($log)) {
                throw new Exception("Log aktivitas gagal disimpan.");
            }

            mysqli_stmt_close($log);

            // Simpan semua perubahan
            mysqli_commit($koneksi);

            echo "<script>
                alert('Peminjaman berhasil ditambahkan!');
                window.location='peminjaman.php';
            </script>";
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            error_log("Tambah peminjaman SIPORA: " . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}

// Ambil daftar pengguna Peminjam
$users = mysqli_query(
    $koneksi,
    "SELECT id_user, username FROM users
     WHERE role = 'Peminjam' ORDER BY username ASC"
);

// Ambil daftar alat yang kondisinya baik
$alat = mysqli_query(
    $koneksi,
    "SELECT id_alat, nama_alat, jumlah_stok
     FROM alat
     WHERE kondisi = 'Baik' AND jumlah_stok > 0
     ORDER BY nama_alat ASC"
);

include 'header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Tambah Peminjaman</h5>
        </div>

        <div class="card-body">

            <?php if ($error != '') { ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php } ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Nama Peminjam</label>
                    <select name="id_user" class="form-select" required>
                        <option value="">-- Pilih Peminjam --</option>
                        <?php while ($u = mysqli_fetch_assoc($users)) { ?>
                            <option value="<?= $u['id_user'] ?>">
                                <?= htmlspecialchars($u['username']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Pinjam</label>
                        <input type="date" name="tanggal_pinjam"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rencana Kembali</label>
                        <input type="date" name="tanggal_rencana_kembali"
                               class="form-control" required>
                    </div>
                </div>

                <hr>
                <h6>Alat yang Dipinjam</h6>

                <div id="daftar-alat">
                    <div class="row mb-3 baris-alat">
                        <div class="col-md-8">
                            <select name="id_alat[]" class="form-select">
                                <option value="">-- Pilih Alat --</option>
                                <?php
                                mysqli_data_seek($alat, 0);
                                while ($a = mysqli_fetch_assoc($alat)) {
                                ?>
                                    <option value="<?= $a['id_alat'] ?>">
                                        <?= htmlspecialchars($a['nama_alat']) ?>
                                        (Stok: <?= $a['jumlah_stok'] ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="number" name="jumlah_pinjam[]"
                                   class="form-control" min="1"
                                   placeholder="Jumlah">
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-secondary btn-sm mb-3"
                        onclick="tambahAlat()">
                    + Tambah Jenis Alat
                </button>

                <div class="mb-3">
                    <label class="form-label">Keperluan</label>
                    <input type="text" name="keperluan"
                           class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control"
                              rows="3"></textarea>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">
                    Simpan Peminjaman
                </button>

                <a href="peminjaman.php" class="btn btn-secondary">
                    Kembali
                </a>

            </form>
        </div>
    </div>
</div>

<script>
function tambahAlat() {
    const daftar = document.getElementById('daftar-alat');
    const baris = daftar.querySelector('.baris-alat').cloneNode(true);

    baris.querySelector('select').selectedIndex = 0;
    baris.querySelector('input').value = '';

    daftar.appendChild(baris);
}
</script>

<?php include 'footer.php'; ?>
