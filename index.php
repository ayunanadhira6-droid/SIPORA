<?php
include 'koneksi.php';

/** @var mysqli $koneksi */

$error = "";

if (isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = md5($_POST['password']);

    $query = mysqli_prepare(
        $koneksi,
        "SELECT id_user, username, password, role
         FROM users
         WHERE username = ? AND password = ?"
    );

    mysqli_stmt_bind_param($query, "ss", $username, $password);
    mysqli_stmt_execute($query);

    $hasil = mysqli_stmt_get_result($query);
    $user = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($query);

    if ($user) {
        // Tentukan nama session berdasarkan role
        if ($user['role'] == 'Admin') {
            $nama_session = 'SIPORA_ADMIN';
            $halaman = 'admin/admin_dashboard.php';
        } elseif ($user['role'] == 'Petugas') {
            $nama_session = 'SIPORA_PETUGAS';
            $halaman = 'petugas/petugas_dashboard.php';
        } elseif ($user['role'] == 'Peminjam') {
            $nama_session = 'SIPORA_PEMINJAM';
            $halaman = 'peminjam/peminjam_dashboard.php';
        } else {
            $error = "Role akun tidak valid.";
        }

        if ($error == "") {
            // Mulai session khusus role yang berhasil login
            session_name($nama_session);
            session_start();
            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: " . $halaman);
            exit;
        }
    } else {
        $error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIPORA</title>

    <link rel="stylesheet" href="assets/bootstrap.min.css">
</head>

<body>
    <div
        class="min-vh-100 d-flex justify-content-center align-items-center py-4"
        style="background: linear-gradient(#0006, #0006), url('assets/ball.jpeg') center/cover no-repeat;"
    >
        <div class="container">
            <div class="card shadow-lg border-0 rounded-4 mx-auto"
                 style="width: 100%; max-width: 420px;">

                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">SIPORA</h2>

                        <p class="text-secondary mb-0">
                            Sistem Informasi Peminjaman Alat Olahraga
                        </p>

                        <hr>

                        <h5 class="mt-3">Login Akun</h5>

                        <small class="text-secondary">
                            Silakan masuk untuk melanjutkan
                        </small>
                    </div>

                    <?php if ($error != ""): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                Username
                            </label>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="Masukkan username"
                                required
                                autofocus
                            >
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Masukkan password"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Login
                        </button>
                    </form>

                    <p class="text-center text-secondary small mt-4 mb-0">
                        SIPORA &copy; 2026
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>