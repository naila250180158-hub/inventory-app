<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini hanya untuk admin.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Kelola User</h1>
    <p>Halaman ini hanya bisa dibuka oleh admin.</p>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>
</body>
</html>