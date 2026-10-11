<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$is_admin = $_SESSION['user']['role'] === 'admin';
$error = '';

// Hapus supplier (khusus admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!$is_admin) {
        http_response_code(403);
        exit('Akses ditolak. Hanya admin yang boleh menghapus supplier.');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['delete_id']]);
        header('Location: supplier.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Supplier masih terhubung ke produk, tidak bisa dihapus.';
    }
}

// Tambah supplier (khusus admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    if (!$is_admin) {
        http_response_code(403);
        exit('Akses ditolak. Hanya admin yang boleh menambah supplier.');
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') {
        $error = 'Nama supplier wajib diisi.';
    } elseif (mb_strlen($name) > 100) {
        $error = 'Nama supplier maksimal 100 karakter.';
    } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s]{1,20}$/', $phone)) {
        $error = 'Nomor telepon hanya boleh berisi angka, spasi, tanda + dan -, maksimal 20 karakter.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO suppliers (name, phone) VALUES (:name, :phone)');
        $stmt->execute([
            'name' => $name,
            'phone' => $phone === '' ? null : $phone,
        ]);
        header('Location: supplier.php');
        exit;
    }
}

$suppliers = $pdo->query(
    'SELECT s.id, s.name, s.phone, COUNT(ps.product_id) AS total_products
     FROM suppliers s
     LEFT JOIN product_suppliers ps ON ps.supplier_id = s.id
     GROUP BY s.id, s.name, s.phone
     ORDER BY s.name'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Supplier</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Supplier</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($is_admin): ?>
    <h2>Tambah Supplier</h2>
    <form method="post">
        <p>
            <label>Nama</label><br>
            <input type="text" name="name" required>
        </p>
        <p>
            <label>Telepon</label><br>
            <input type="text" name="phone">
        </p>
        <button type="submit">Simpan</button>
    </form>
    <?php endif; ?>

    <h2>Daftar Supplier</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Telepon</th>
            <th>Jumlah Produk</th>
            <?php if ($is_admin): ?><th>Aksi</th><?php endif; ?>
        </tr>
        <?php foreach ($suppliers as $i => $supplier): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($supplier['name']) ?></td>
            <td><?= htmlspecialchars($supplier['phone'] ?? '-') ?></td>
            <td><?= (int) $supplier['total_products'] ?></td>
            <?php if ($is_admin): ?>
            <td>
                <a href="supplier-edit.php?id=<?= $supplier['id'] ?>">Edit</a>
                <form method="post" onsubmit="return confirm('Hapus supplier ini?')">
                    <input type="hidden" name="delete_id" value="<?= $supplier['id'] ?>">
                    <button type="submit">Hapus</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
