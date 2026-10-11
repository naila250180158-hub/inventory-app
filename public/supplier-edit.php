<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang boleh mengubah supplier.');
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, name, phone FROM suppliers WHERE id = :id');
$stmt->execute(['id' => $id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$supplier) {
    http_response_code(404);
    exit('Supplier tidak ditemukan.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') {
        $error = 'Nama supplier wajib diisi.';
    } elseif (mb_strlen($name) > 100) {
        $error = 'Nama supplier maksimal 100 karakter.';
    } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s]{1,20}$/', $phone)) {
        $error = 'Nomor telepon hanya boleh berisi angka, spasi, tanda + dan -, maksimal 20 karakter.';
    } else {
        $stmt = $pdo->prepare('UPDATE suppliers SET name = :name, phone = :phone WHERE id = :id');
        $stmt->execute([
            'name' => $name,
            'phone' => $phone === '' ? null : $phone,
            'id' => $id,
        ]);
        header('Location: supplier.php');
        exit;
    }

    // Tampilkan kembali isian terakhir kalau ada error
    $supplier['name'] = $name;
    $supplier['phone'] = $phone;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Supplier</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Edit Supplier</h1>
    <p><a href="supplier.php">Kembali ke Supplier</a></p>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <p>
            <label>Nama</label><br>
            <input type="text" name="name" value="<?= htmlspecialchars($supplier['name']) ?>" required>
        </p>
        <p>
            <label>Telepon</label><br>
            <input type="text" name="phone" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
        </p>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>
