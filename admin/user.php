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

$judul = "Data Pengguna";

include 'header.php';

$data = mysqli_query($koneksi, "
    SELECT id_user, username, role, created_at
    FROM users
    ORDER BY id_user DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Data Pengguna</h4>

    <a href="user_tambah.php" class="btn btn-primary">
        + Tambah pengguna
    </a>
</div>

<div class="card">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>No.</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Tanggal Dibuat</th>
                        <th>Opsi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($data) > 0): ?>
                        <?php $no = 1; ?>

                        <?php while ($d = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= htmlspecialchars($d['username']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['role']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($d['created_at'])
                                    ) ?>
                                </td>
                                <td><a href="user_edit.php?id=<?= $d['id_user'] ?>"
                                       class="btn btn-info btn-sm">
                                        Edit
                                    </a>
                                    <a href="user_hapus.php?id=<?= $d['id_user'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin ingin menghapus user ini?')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                Belum ada data pengguna.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>