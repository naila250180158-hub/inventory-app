<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$total_products = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$total_categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$total_suppliers = (int) $pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();
$total_stock = (int) $pdo->query('SELECT COALESCE(SUM(stock), 0) FROM products')->fetchColumn();
$inventory_value = (float) $pdo->query('SELECT COALESCE(SUM(stock * price), 0) FROM products')->fetchColumn();
$total_in = (int) $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE type = 'IN'")->fetchColumn();
$total_out = (int) $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE type = 'OUT'")->fetchColumn();

$low_stock = $pdo->query(
    'SELECT sku, name, stock, minimum_stock
     FROM products
     WHERE stock <= minimum_stock
     ORDER BY stock ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$is_admin = $_SESSION['user']['role'] === 'admin';
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
        <a href="supplier.php">Supplier</a> |
        <a href="produk-supplier.php">Produk dan Supplier</a> |
        <a href="stok.php">Barang Masuk/Keluar</a> |
        <a href="laporan.php">Laporan</a>
    </p>
    <p>Selamat datang! Role kamu: <?= htmlspecialchars($_SESSION['user']['role']) ?></p>

    <h2>Ringkasan</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Total Produk</th>
            <th>Total Kategori</th>
            <th>Total Supplier</th>
            <th>Total Stok</th>
        </tr>
        <tr>
            <td><?= $total_products ?></td>
            <td><?= $total_categories ?></td>
            <td><?= $total_suppliers ?></td>
            <td><?= $total_stock ?></td>
        </tr>
    </table>

    <table border="1" cellpadding="6">
        <tr>
            <th>Nilai Inventory</th>
            <th>Total Barang Masuk</th>
            <th>Total Barang Keluar</th>
        </tr>
        <tr>
            <td>Rp <?= number_format($inventory_value, 0, ',', '.') ?></td>
            <td><?= $total_in ?></td>
            <td><?= $total_out ?></td>
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
        <?php if ($is_admin): ?>
            <a href="kelola-user.php">Kelola User</a> |
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </p>
</body>
</html>
