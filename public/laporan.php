<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/database.php';

// Laporan per kategori: COUNT, SUM, AVG dengan GROUP BY
$by_category = $pdo->query(
    'SELECT c.name,
            COUNT(p.id) AS total_products,
            COALESCE(SUM(p.stock), 0) AS total_stock,
            COALESCE(AVG(p.price), 0) AS average_price
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY c.name'
)->fetchAll(PDO::FETCH_ASSOC);

// Laporan per produk: total masuk dan total keluar dari stock_movements
$by_product = $pdo->query(
    "SELECT p.sku, p.name, c.name AS category_name, p.price, p.stock,
            COALESCE(SUM(CASE WHEN sm.type = 'IN' THEN sm.quantity ELSE 0 END), 0) AS total_in,
            COALESCE(SUM(CASE WHEN sm.type = 'OUT' THEN sm.quantity ELSE 0 END), 0) AS total_out
     FROM products p
     JOIN categories c ON c.id = p.category_id
     LEFT JOIN stock_movements sm ON sm.product_id = p.id
     GROUP BY p.id, p.sku, p.name, c.name, p.price, p.stock
     ORDER BY p.name"
)->fetchAll(PDO::FETCH_ASSOC);

$grand_in = 0;
$grand_out = 0;
$grand_value = 0;
foreach ($by_product as $row) {
    $grand_in += (int) $row['total_in'];
    $grand_out += (int) $row['total_out'];
    $grand_value += $row['stock'] * $row['price'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Laporan Inventory</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <h2>Per Kategori</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Kategori</th>
            <th>Jumlah Produk</th>
            <th>Total Stok</th>
            <th>Rata-rata Harga</th>
        </tr>
        <?php foreach ($by_category as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= (int) $row['total_products'] ?></td>
            <td><?= (int) $row['total_stock'] ?></td>
            <td>Rp <?= number_format($row['average_price'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Per Produk</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>SKU</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Total Masuk</th>
            <th>Total Keluar</th>
            <th>Stok Saat Ini</th>
            <th>Nilai Stok</th>
        </tr>
        <?php foreach ($by_product as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['sku']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['category_name']) ?></td>
            <td><?= (int) $row['total_in'] ?></td>
            <td><?= (int) $row['total_out'] ?></td>
            <td><?= (int) $row['stock'] ?></td>
            <td>Rp <?= number_format($row['stock'] * $row['price'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <th colspan="3">Total</th>
            <th><?= $grand_in ?></th>
            <th><?= $grand_out ?></th>
            <th></th>
            <th>Rp <?= number_format($grand_value, 0, ',', '.') ?></th>
        </tr>
    </table>
</body>
</html>
