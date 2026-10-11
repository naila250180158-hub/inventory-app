<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $type = $_POST['type'] ?? '';
    $quantity = $_POST['quantity'] ?? '';

    if ($product_id === 0 || !in_array($type, ['IN', 'OUT'], true)) {
        $error = 'Produk dan jenis wajib dipilih.';
    } elseif (!ctype_digit((string) $quantity) || (int) $quantity < 1) {
        $error = 'Jumlah harus berupa angka bulat lebih dari 0.';
    } else {
        $quantity = (int) $quantity;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $product_id]);
            $current = $stmt->fetchColumn();

            if ($current === false) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }
            if ($type === 'OUT' && $quantity > (int) $current) {
                throw new RuntimeException('Stok tidak cukup. Stok saat ini: ' . $current . '.');
            }

            $delta = $type === 'IN' ? $quantity : -$quantity;

            $stmt = $pdo->prepare('UPDATE products SET stock = stock + :delta WHERE id = :id');
            $stmt->execute(['delta' => $delta, 'id' => $product_id]);

            $stmt = $pdo->prepare(
                'INSERT INTO stock_movements (product_id, user_id, type, quantity)
                 VALUES (:product_id, :user_id, :type, :quantity)'
            );
            $stmt->execute([
                'product_id' => $product_id,
                'user_id' => $_SESSION['user']['id'],
                'type' => $type,
                'quantity' => $quantity,
            ]);

            $pdo->commit();
            header('Location: stok.php');
            exit;
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Terjadi kesalahan, data tidak disimpan.';
        }
    }
}

$products = $pdo->query('SELECT id, sku, name, stock FROM products ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$movements = $pdo->query(
    'SELECT m.created_at, m.type, m.quantity, p.name AS product_name, u.username
     FROM stock_movements m
     JOIN products p ON p.id = m.product_id
     JOIN users u ON u.id = m.user_id
     ORDER BY m.id DESC
     LIMIT 20'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Stok</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Barang Masuk dan Keluar</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <h2>Catat Pergerakan Stok</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <p>
            <label>Produk</label><br>
            <select name="product_id" required>
                <option value="">-- Pilih produk --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>">
                        <?= htmlspecialchars($product['sku'] . ' - ' . $product['name']) ?> (stok: <?= $product['stock'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label>Jenis</label><br>
            <select name="type" required>
                <option value="IN">Barang masuk</option>
                <option value="OUT">Barang keluar</option>
            </select>
        </p>
        <p>
            <label>Jumlah</label><br>
            <input type="number" name="quantity" min="1" required>
        </p>
        <button type="submit">Simpan</button>
    </form>

    <h2>Riwayat Terakhir</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Waktu</th>
            <th>Produk</th>
            <th>Jenis</th>
            <th>Jumlah</th>
            <th>Dicatat oleh</th>
        </tr>
        <?php foreach ($movements as $movement): ?>
        <tr>
            <td><?= htmlspecialchars($movement['created_at']) ?></td>
            <td><?= htmlspecialchars($movement['product_name']) ?></td>
            <td><?= $movement['type'] === 'IN' ? 'Masuk' : 'Keluar' ?></td>
            <td><?= $movement['quantity'] ?></td>
            <td><?= htmlspecialchars($movement['username']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>