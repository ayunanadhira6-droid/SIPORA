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

$judul = "Tambah Pengguna";
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $role_valid = ['Admin', 'Petugas', 'Peminjam'];

    if ($username == '' || $password == '' || $role == '') {
        $error = "Semua kolom wajib diisi.";
    } elseif (!in_array($role, $role_valid, true)) {
        $error = "Role yang dipilih tidak valid.";
    } else {
        // Cek apakah username sudah digunakan
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_user FROM users WHERE username = ?"
        );

        mysqli_stmt_bind_param($cek, "s", $username);
        mysqli_stmt_execute($cek);
        $hasil_cek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil_cek) > 0) {
            $error = "Username sudah digunakan. Silakan pilih username lain.";
        } else {
            // Simpan pengguna baru
            $password_md5 = md5($password);

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO users (username, password, role)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $username,
                $password_md5,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {
                $id_baru = mysqli_insert_id($koneksi);

                // Catat aktivitas admin
                $aktivitas = "Menambahkan pengguna";
                $keterangan = "Admin menambahkan pengguna: " . $username
                            . " dengan role " . $role;

                $log = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO log_aktivitas
                     (id_user, aktivitas, keterangan)
                     VALUES (?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $log,
                    "iss",
                    $_SESSION['id_user'],
                    $aktivitas,
                    $keterangan
                );

                mysqli_stmt_execute($log);
                mysqli_stmt_close($log);
                mysqli_stmt_close($stmt);
                mysqli_stmt_close($cek);

                header("Location: user.php?pesan=tambah");
                exit;
            } else {
                $error = "Pengguna gagal ditambahkan. Silakan coba lagi.";
            }

            if (isset($stmt)) {
                mysqli_stmt_close($stmt);
            }
        }

        mysqli_stmt_close($cek);
    }
}

include 'header.php';
?>

<div class="container-fluid">

    <div class="mb-3">
        <h4>Tambah Pengguna</h4>
        <p class="text-secondary">
            Tambahkan akun baru untuk menggunakan sistem SIPORA.
        </p>
    </div>

    <div class="card">
        <div class="card-body">

            <?php if ($error != ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label for="username" class="form-label">
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        id="username"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">
                        Role Pengguna
                    </label>

                    <select name="role" id="role" class="form-select" required>
                        <option value="">-- Pilih Role --</option>

                        <option value="Admin"
                            <?= ($_POST['role'] ?? '') == 'Admin' ? 'selected' : '' ?>>
                            Admin
                        </option>

                        <option value="Petugas"
                            <?= ($_POST['role'] ?? '') == 'Petugas' ? 'selected' : '' ?>>
                            Petugas
                        </option>

                        <option value="Peminjam"
                            <?= ($_POST['role'] ?? '') == 'Peminjam' ? 'selected' : '' ?>>
                            Peminjam
                        </option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan
                    </button>

                    <a href="user.php" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
