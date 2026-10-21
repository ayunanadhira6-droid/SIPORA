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

$judul = "Edit Kategori";
$error = '';

$id = (int) ($_GET['id'] ?? 0);

// Ambil data kategori
$data = mysqli_query($koneksi,
    "SELECT * FROM kategori WHERE id_kategori = $id"
);

if ($id < 1 || mysqli_num_rows($data) == 0) {
    header("Location: kategori.php");
    exit;
}

$kategori = mysqli_fetch_assoc($data);

// Proses edit
if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_kategori']);
    $keterangan = trim($_POST['keterangan']);

    $nama_aman = mysqli_real_escape_string($koneksi, $nama);
    $keterangan_aman = mysqli_real_escape_string($koneksi, $keterangan);

    if ($nama == '') {
        $error = "Nama kategori wajib diisi.";
    } else {
        // Cek nama kategori yang sama, selain kategori yang sedang diedit
        $cek = mysqli_query($koneksi, "
            SELECT id_kategori
            FROM kategori
            WHERE nama_kategori = '$nama_aman'
            AND id_kategori != $id
        ");

        if (mysqli_num_rows($cek) > 0) {
            $error = "Nama kategori sudah digunakan.";
        } else {
            
$ubah = mysqli_query($koneksi, "
    UPDATE kategori
    SET nama_kategori='$nama_aman',
        keterangan='$keterangan_aman'
    WHERE id_kategori=$id
");

if ($ubah) {
    $id_user = (int) $_SESSION['id_user'];
    $aktivitas = "Mengedit kategori";

    $keterangan_log = "Mengubah kategori menjadi: " . $nama;
    $keterangan_log = mysqli_real_escape_string($koneksi, $keterangan_log);

    mysqli_query($koneksi, "
        INSERT INTO log_aktivitas (id_user, aktivitas, keterangan)
        VALUES (
            $id_user,
            '$aktivitas',
            '$keterangan_log'
        )
    ");

    header("Location: kategori.php");
    exit;
} else {
    $error = "Kategori gagal diperbarui.";
}

        }
    }

    // Tampilkan kembali input jika terjadi kesalahan
    $kategori['nama_kategori'] = $nama;
    $kategori['keterangan'] = $keterangan;
}

include 'header.php';
?>

<div class="container-fluid">

    <div class="mb-3">
        <h4>Edit Kategori</h4>
        <p class="text-secondary">
            Ubah nama atau keterangan kategori alat olahraga.
        </p>
    </div>

    <div class="card">
        <div class="card-body">

            <?php if ($error != ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input
                        type="text"
                        name="nama_kategori"
                        class="form-control"
                        value="<?= htmlspecialchars($kategori['nama_kategori']); ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="3"
                    ><?= htmlspecialchars($kategori['keterangan'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">
                    Simpan Perubahan
                </button>

                <a href="kategori.php" class="btn btn-secondary">
                    Kembali
                </a>

            </form>

        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
