<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$is_admin = $_SESSION['user']['role'] === 'admin';
$error = '';
$product_id = (int) ($_GET['product_id'] ?? 0);

// Hubungkan produk dengan supplier, atau ubah harga supplier (khusus admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlink_supplier_id'])) {
    if (!$is_admin) {
        http_response_code(403);
        exit('Akses ditolak. Hanya admin yang boleh mengubah relasi produk dan supplier.');
    }

    $stmt = $pdo->prepare('DELETE FROM product_suppliers WHERE product_id = :product_id AND supplier_id = :supplier_id');
    $stmt->execute([
        'product_id' => $product_id,
        'supplier_id' => (int) $_POST['unlink_supplier_id'],
    ]);
    header('Location: produk-supplier.php?product_id=' . $product_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supplier_id'])) {
    if (!$is_admin) {
        http_response_code(403);
        exit('Akses ditolak. Hanya admin yang boleh mengubah relasi produk dan supplier.');
    }

    $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
    $price = $_POST['supplier_price'] ?? '';

    if ($product_id === 0 || $supplier_id === 0) {
        $error = 'Produk dan supplier wajib dipilih.';
    } elseif (!is_numeric($price) || $price < 0) {
        $error = 'Harga supplier tidak boleh negatif.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO product_suppliers (product_id, supplier_id, supplier_price)
                 VALUES (:product_id, :supplier_id, :price)
                 ON DUPLICATE KEY UPDATE supplier_price = VALUES(supplier_price)'
            );
            $stmt->execute([
                'product_id' => $product_id,
                'supplier_id' => $supplier_id,
                'price' => $price,
            ]);
            header('Location: produk-supplier.php?product_id=' . $product_id);
            exit;
        } catch (PDOException $e) {
            $error = 'Produk atau supplier tidak valid.';
        }
    }
}

$products = $pdo->query('SELECT id, sku, name FROM products ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$selected = null;
$linked = [];

if ($product_id > 0) {
    $stmt = $pdo->prepare('SELECT id, sku, name FROM products WHERE id = :id');
    $stmt->execute(['id' => $product_id]);
    $selected = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected) {
        $stmt = $pdo->prepare(
            'SELECT s.id, s.name, s.phone, ps.supplier_price
             FROM product_suppliers ps
             JOIN suppliers s ON s.id = ps.supplier_id
             WHERE ps.product_id = :product_id
             ORDER BY s.name'
        );
        $stmt->execute(['product_id' => $product_id]);
        $linked = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Produk dan Supplier</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Produk dan Supplier</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <h2>Pilih Produk</h2>
    <form method="get">
        <select name="product_id" required>
            <option value="">-- Pilih produk --</option>
            <?php foreach ($products as $product): ?>
                <option value="<?= $product['id'] ?>" <?= $product['id'] == $product_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($product['sku'] . ' - ' . $product['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Tampilkan</button>
    </form>

    <?php if ($product_id > 0 && !$selected): ?>
        <p style="color: red;">Produk tidak ditemukan.</p>
    <?php endif; ?>

    <?php if ($selected): ?>
        <h2>Supplier untuk <?= htmlspecialchars($selected['name']) ?></h2>

        <?php if (count($linked) === 0): ?>
            <p>Produk ini belum punya supplier.</p>
        <?php else: ?>
            <table border="1" cellpadding="6">
                <tr>
                    <th>Supplier</th>
                    <th>Telepon</th>
                    <th>Harga Supplier</th>
                    <?php if ($is_admin): ?><th>Aksi</th><?php endif; ?>
                </tr>
                <?php foreach ($linked as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                    <td><?= $row['supplier_price'] === null ? '-' : number_format($row['supplier_price'], 0, ',', '.') ?></td>
                    <?php if ($is_admin): ?>
                    <td>
                        <form method="post" onsubmit="return confirm('Lepas supplier ini dari produk?')">
                            <input type="hidden" name="unlink_supplier_id" value="<?= $row['id'] ?>">
                            <button type="submit">Lepas</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <?php if ($is_admin): ?>
            <h2>Tambah atau Ubah Supplier Produk</h2>
            <form method="post">
                <p>
                    <label>Supplier</label><br>
                    <select name="supplier_id" required>
                        <option value="">-- Pilih supplier --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label>Harga supplier</label><br>
                    <input type="number" name="supplier_price" min="0" step="0.01" required>
                </p>
                <button type="submit">Simpan</button>
            </form>
            <p>Kalau supplier sudah terhubung dengan produk ini, harganya akan diperbarui.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
