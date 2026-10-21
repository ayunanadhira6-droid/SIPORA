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

$judul = "Tambah Alat";
$error = "";

// Nilai awal form
$id_kategori = "";
$nama_alat = "";
$jumlah_stok = "";
$kondisi = "Baik";
$keterangan = "";

// Proses tambah alat
if (isset($_POST['simpan'])) {
    $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $jumlah_stok = $_POST['jumlah_stok'] ?? '';
    $kondisi = $_POST['kondisi'] ?? 'Baik';
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Validasi input
    if (
        $id_kategori < 1 ||
        $nama_alat == '' ||
        $jumlah_stok === '' ||
        !ctype_digit((string) $jumlah_stok) ||
        !in_array($kondisi, ['Baik', 'Rusak'], true)
    ) {
        $error = "Lengkapi data dengan benar.";
    } else {
        $jumlah_stok = (int) $jumlah_stok;

        // Pastikan kategori tersedia
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_kategori FROM kategori WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param($cek, "i", $id_kategori);
        mysqli_stmt_execute($cek);
        $hasil_kategori = mysqli_stmt_get_result($cek);
        $kategori_ada = mysqli_num_rows($hasil_kategori) > 0;
        mysqli_stmt_close($cek);

        if (!$kategori_ada) {
            $error = "Kategori tidak ditemukan. Pilih kategori yang tersedia.";
        } else {
            // Gunakan transaksi agar data alat dan log tercatat bersama
            mysqli_begin_transaction($koneksi);

            try {
                // 1. Simpan data alat
                $query_alat = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO alat
                    (id_kategori, nama_alat, jumlah_stok, kondisi, keterangan)
                    VALUES (?, ?, ?, ?, ?)"
                );

                if (!$query_alat) {
                    throw new Exception("Gagal menyiapkan data alat.");
                }

                mysqli_stmt_bind_param(
                    $query_alat,
                    "isiss",
                    $id_kategori,
                    $nama_alat,
                    $jumlah_stok,
                    $kondisi,
                    $keterangan
                );

                if (!mysqli_stmt_execute($query_alat)) {
                    throw new Exception("Data alat gagal disimpan.");
                }

                mysqli_stmt_close($query_alat);

                // 2. Catat aktivitas admin ke log_aktivitas
                $id_user = (int) $_SESSION['id_user'];
                $aktivitas = "Menambah alat";
                $keterangan_log = "Menambahkan alat: " . $nama_alat
                    . ", stok: " . $jumlah_stok
                    . ", kondisi: " . $kondisi;

                $query_log = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO log_aktivitas
                    (id_user, aktivitas, keterangan)
                    VALUES (?, ?, ?)"
                );

                if (!$query_log) {
                    throw new Exception("Gagal menyiapkan log aktivitas.");
                }

                mysqli_stmt_bind_param(
                    $query_log,
                    "iss",
                    $id_user,
                    $aktivitas,
                    $keterangan_log
                );

                if (!mysqli_stmt_execute($query_log)) {
                    throw new Exception("Log aktivitas gagal disimpan.");
                }

                mysqli_stmt_close($query_log);

                // 3. Simpan keduanya jika semua berhasil
                mysqli_commit($koneksi);

                header("Location: alat.php");
                exit;

            } catch (Throwable $e) {
                mysqli_rollback($koneksi);
                error_log("Tambah alat SIPORA: " . $e->getMessage());

                $error = "Alat gagal ditambahkan. Data tidak disimpan "
                    . "karena proses pencatatan tidak berhasil.";
            }
        }
    }
}

// Ambil daftar kategori untuk dropdown
$kategori = mysqli_query(
    $koneksi,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);

include 'header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Tambah Alat</h5>
        </div>

        <div class="card-body">

            <?php if ($error != '') { ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php } ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="id_kategori" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>

                        <?php while ($k = mysqli_fetch_assoc($kategori)) { ?>
                            <option
                                value="<?= $k['id_kategori'] ?>"
                                <?= $id_kategori == $k['id_kategori'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($k['nama_kategori']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Alat</label>
                    <input
                        type="text"
                        name="nama_alat"
                        class="form-control"
                        value="<?= htmlspecialchars($nama_alat) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah Stok</label>
                    <input
                        type="number"
                        name="jumlah_stok"
                        class="form-control"
                        min="0"
                        step="1"
                        value="<?= htmlspecialchars((string) $jumlah_stok) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Kondisi</label>
                    <select name="kondisi" class="form-select" required>
                        <option value="Baik" <?= $kondisi == 'Baik' ? 'selected' : '' ?>>
                            Baik
                        </option>
                        <option value="Rusak" <?= $kondisi == 'Rusak' ? 'selected' : '' ?>>
                            Rusak
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="3"
                    ><?= htmlspecialchars($keterangan) ?></textarea>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">
                    Simpan
                </button>

                <a href="alat.php" class="btn btn-secondary">
                    Kembali
                </a>

            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
