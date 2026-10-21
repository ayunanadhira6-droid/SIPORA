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

$judul = "Edit Pengguna";
$error = '';

$id_user = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_user || $id_user < 1) {
    header("Location: user.php");
    exit;
}

// Ambil data pengguna
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id_user, username, role
     FROM users
     WHERE id_user = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($hasil);
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: user.php");
    exit;
}

// Proses perubahan data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $role_valid = ['Admin', 'Petugas', 'Peminjam'];

    if ($username == '' || !in_array($role, $role_valid, true)) {
        $error = "Username dan role wajib diisi dengan benar.";
    } else {
        // Cek username milik pengguna lain
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_user
             FROM users
             WHERE username = ? AND id_user != ?"
        );

        mysqli_stmt_bind_param($cek, "si", $username, $id_user);
        mysqli_stmt_execute($cek);
        $hasil_cek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil_cek) > 0) {
            $error = "Username sudah digunakan oleh pengguna lain.";
        } else {
            if ($password != '') {
                // Password diubah jika kolom diisi
                $password_md5 = md5($password);

                $ubah = mysqli_prepare(
                    $koneksi,
                    "UPDATE users
                     SET username = ?, password = ?, role = ?
                     WHERE id_user = ?"
                );

                mysqli_stmt_bind_param(
                    $ubah,
                    "sssi",
                    $username,
                    $password_md5,
                    $role,
                    $id_user
                );
            } else {
                // Password tetap jika kolom dikosongkan
                $ubah = mysqli_prepare(
                    $koneksi,
                    "UPDATE users
                     SET username = ?, role = ?
                     WHERE id_user = ?"
                );

                mysqli_stmt_bind_param(
                    $ubah,
                    "ssi",
                    $username,
                    $role,
                    $id_user
                );
            }

            if (mysqli_stmt_execute($ubah)) {
                // Catat aktivitas
                $aktivitas = "Mengubah data pengguna";
                $keterangan = "Admin mengubah data pengguna ID "
                            . $id_user . " (" . $username . ")";

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
                mysqli_stmt_close($ubah);
                mysqli_stmt_close($cek);

                // Perbarui username di sesi jika akun sendiri diedit
                if ($id_user == $_SESSION['id_user']) {
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                }

                header("Location: user.php?pesan=edit");
                exit;
            } else {
                $error = "Data pengguna gagal diperbarui.";
            }

            mysqli_stmt_close($ubah);
        }

        mysqli_stmt_close($cek);
    }

    // Tampilkan kembali nilai yang sudah diisi
    $user['username'] = $username;
    $user['role'] = $role;
}

include 'header.php';
?>

<div class="container-fluid">

    <div class="mb-3">
        <h4>Edit Pengguna</h4>
        <p class="text-secondary">
            Ubah informasi akun pengguna SIPORA.
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
                        value="<?= htmlspecialchars($user['username']) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password Baru
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                    >

                    <small class="text-secondary">
                        Kosongkan jika tidak ingin mengganti password.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">
                        Role Pengguna
                    </label>

                    <select
                        name="role"
                        id="role"
                        class="form-select"
                        required
                    >
                        <option value="Admin"
                            <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>
                            Admin
                        </option>

                        <option value="Petugas"
                            <?= $user['role'] == 'Petugas' ? 'selected' : '' ?>>
                            Petugas
                        </option>

                        <option value="Peminjam"
                            <?= $user['role'] == 'Peminjam' ? 'selected' : '' ?>>
                            Peminjam
                        </option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
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
