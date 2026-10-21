<?php
// Ambil role dari URL
$role = $_GET['role'] ?? '';

// Tentukan nama session berdasarkan role
if ($role == 'Admin') {
    $nama_session = 'SIPORA_ADMIN';
} elseif ($role == 'Petugas') {
    $nama_session = 'SIPORA_PETUGAS';
} elseif ($role == 'Peminjam') {
    $nama_session = 'SIPORA_PEMINJAM';
} else {
    // Jika role tidak valid, kembali ke login
    header("Location: index.php");
    exit;
}

// Mulai session yang sesuai
session_name($nama_session);
session_start();

// Hapus data session yang sedang aktif
$_SESSION = [];

// Hapus cookie session yang sesuai
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Kembali ke halaman login
header("Location: index.php");
exit;
?>