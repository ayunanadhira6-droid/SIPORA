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

$judul = "Tambah Kategori";
$error = '';

if (isset($_POST['simpan'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    $keterangan = trim($_POST['keterangan']);

    if ($nama_kategori == '') {
        $error = "Nama kategori wajib diisi.";
    } else {
        // Cek nama kategori
        $nama_aman = mysqli_real_escape_string(
            $koneksi,
            $nama_kategori
        );

        $cek = mysqli_query($koneksi, "
            SELECT id_kategori
            FROM kategori
            WHERE nama_kategori = '$nama_aman'
        ");

        if (mysqli_num_rows($cek) > 0) {
            $error = "Nama kategori sudah tersedia.";
        } else {
            $keterangan_aman = mysqli_real_escape_string(
                $koneksi,
                $keterangan
            );

            $simpan = mysqli_query($koneksi, "
                INSERT INTO kategori (nama_kategori, keterangan)
                VALUES ('$nama_aman', '$keterangan_aman')
            ");

            if ($simpan) {
                $id_user = (int) $_SESSION['id_user'];
                $aktivitas = "Menambah kategori";
                $keterangan = "Menambahkan kategori: " . $nama_kategori;

                mysqli_query($koneksi, "
                    INSERT INTO log_aktivitas (id_user, aktivitas, keterangan)
                    VALUES (
                        $id_user,
                        '$aktivitas',
                        '" . mysqli_real_escape_string($koneksi, $keterangan) . "'
                    )
                ");

                header("Location: kategori.php");
                exit;

            } else {
                $error = "Kategori gagal ditambahkan.";
            }
        }
    }
}

include 'header.php';
?>

<div class="container-fluid">

    <div class="mb-3">
        <h4>Tambah Kategori</h4>
        <p class="text-secondary">
            Tambahkan kategori alat olahraga baru.
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
                        value="<?= htmlspecialchars($_POST['nama_kategori'] ?? ''); ?>"
                        placeholder="Contoh: Bola"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="3"
                        placeholder="Keterangan kategori (opsional)"
                    ><?= htmlspecialchars($_POST['keterangan'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">
                    Simpan
                </button>

                <a href="kategori.php" class="btn btn-secondary">
                    Kembali
                </a>

            </form>

        </div>
    </div>

</div>

<?php include 'footer.php'; ?>