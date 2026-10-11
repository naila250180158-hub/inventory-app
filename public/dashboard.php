<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$total_products = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$total_categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$total_stock = (int) $pdo->query('SELECT COALESCE(SUM(stock), 0) FROM products')->fetchColumn();

$low_stock = $pdo->query(
    'SELECT sku, name, stock, minimum_stock
     FROM products
     WHERE stock <= minimum_stock
     ORDER BY stock ASC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Dashboard Inventory</h1>
    <p>
        <a href="kategori.php">Kategori</a> |
        <a href="produk.php">Produk</a> |
        <a href="stok.php">Barang Masuk/Keluar</a>
    </p>
    <p>Selamat datang! Role kamu: <?= htmlspecialchars($_SESSION['user']['role']) ?></p>

    <h2>Ringkasan</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Jumlah Produk</th>
            <th>Jumlah Kategori</th>
            <th>Total Stok</th>
        </tr>
        <tr>
            <td><?= $total_products ?></td>
            <td><?= $total_categories ?></td>
            <td><?= $total_stock ?></td>
        </tr>
    </table>

    <h2>Stok Menipis</h2>
    <?php if (count($low_stock) === 0): ?>
        <p>Tidak ada produk yang stoknya menipis.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>SKU</th>
                <th>Nama</th>
                <th>Stok</th>
                <th>Batas Minimum</th>
            </tr>
            <?php foreach ($low_stock as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['sku']) ?></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['stock'] ?></td>
                <td><?= $item['minimum_stock'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <p>
        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <a href="kelola-user.php">Kelola User</a> |
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </p>
</body>
</html>