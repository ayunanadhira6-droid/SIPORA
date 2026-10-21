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

$judul = "Edit Alat";
$error = "";

// Ambil ID alat
$id = (int) ($_GET['id'] ?? 0);

if ($id < 1) {
    header("Location: alat.php");
    exit;
}

// Ambil data alat
$query = mysqli_prepare(
    $koneksi,
    "SELECT * FROM alat WHERE id_alat = ?"
);

mysqli_stmt_bind_param($query, "i", $id);
mysqli_stmt_execute($query);
$hasil = mysqli_stmt_get_result($query);
$alat = mysqli_fetch_assoc($hasil);
mysqli_stmt_close($query);

if (!$alat) {
    header("Location: alat.php");
    exit;
}

// Nilai awal form
$id_kategori = $alat['id_kategori'];
$nama_alat = $alat['nama_alat'];
$jumlah_stok = $alat['jumlah_stok'];
$kondisi = $alat['kondisi'];
$keterangan = $alat['keterangan'];

// Proses edit alat
if (isset($_POST['simpan'])) {
    $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $stok_input = $_POST['jumlah_stok'] ?? '';
    $kondisi = $_POST['kondisi'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Validasi input
    if (
        $id_kategori < 1 ||
        $nama_alat == '' ||
        $stok_input === '' ||
        !ctype_digit((string) $stok_input) ||
        !in_array($kondisi, ['Baik', 'Rusak'], true)
    ) {
        $error = "Lengkapi data dengan benar.";
    } else {
        $jumlah_stok = (int) $stok_input;

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
            $error = "Kategori tidak ditemukan.";
        } else {
            // Simpan perubahan alat dan log dalam satu transaksi
            mysqli_begin_transaction($koneksi);

            try {
                // 1. Perbarui data alat
                $ubah = mysqli_prepare(
                    $koneksi,
                    "UPDATE alat
                     SET id_kategori = ?,
                         nama_alat = ?,
                         jumlah_stok = ?,
                         kondisi = ?,
                         keterangan = ?
                     WHERE id_alat = ?"
                );

                if (!$ubah) {
                    throw new Exception("Gagal menyiapkan perubahan alat.");
                }

                mysqli_stmt_bind_param(
                    $ubah,
                    "isissi",
                    $id_kategori,
                    $nama_alat,
                    $jumlah_stok,
                    $kondisi,
                    $keterangan,
                    $id
                );

                if (!mysqli_stmt_execute($ubah)) {
                    throw new Exception("Data alat gagal diperbarui.");
                }

                mysqli_stmt_close($ubah);

                // 2. Catat aktivitas edit ke log
                $id_user = (int) $_SESSION['id_user'];
                $aktivitas = "Mengedit alat";

                $keterangan_log = "Mengubah data alat: " . $nama_alat
                    . ", stok: " . $jumlah_stok
                    . ", kondisi: " . $kondisi;

                $log = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO log_aktivitas
                     (id_user, aktivitas, keterangan)
                     VALUES (?, ?, ?)"
                );

                if (!$log) {
                    throw new Exception("Gagal menyiapkan log aktivitas.");
                }

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

                // 3. Simpan perubahan jika semuanya berhasil
                mysqli_commit($koneksi);

                header("Location: alat.php");
                exit;

            } catch (Throwable $e) {
                mysqli_rollback($koneksi);
                error_log("Edit alat SIPORA: " . $e->getMessage());

                $error = "Data alat gagal diperbarui. "
                    . "Periksa kembali database dan log aktivitas.";
            }
        }
    }
}

// Ambil daftar kategori
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
            <h5 class="mb-0">Form Edit Alat</h5>
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
                    ><?= htmlspecialchars($keterangan ?? '') ?></textarea>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">
                    Simpan Perubahan
                </button>

                <a href="alat.php" class="btn btn-secondary">
                    Kembali
                </a>

            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
