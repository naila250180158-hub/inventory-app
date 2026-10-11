<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $price = $_POST['price'] ?? '';
    $minimum_stock = $_POST['minimum_stock'] ?? '';

    if ($sku === '' || $name === '' || $category_id === 0) {
        $error = 'SKU, nama, dan kategori wajib diisi.';
    } elseif (!is_numeric($price) || $price < 0) {
        $error = 'Harga tidak boleh negatif.';
    } elseif (!ctype_digit((string) $minimum_stock)) {
        $error = 'Stok minimum harus berupa angka bulat, tidak boleh negatif.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET category_id = :category_id, sku = :sku, name = :name,
                     price = :price, minimum_stock = :minimum_stock
                 WHERE id = :id'
            );
            $stmt->execute([
                'category_id' => $category_id,
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'minimum_stock' => $minimum_stock,
                'id' => $id,
            ]);
            header('Location: produk.php');
            exit;
        } catch (PDOException $e) {
            $error = 'SKU sudah dipakai, atau kategori tidak valid.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Edit Produk</h1>
    <p><a href="produk.php">Kembali ke Produk</a></p>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <p>
            <label>SKU</label><br>
            <input type="text" name="sku" value="<?= htmlspecialchars($product['sku']) ?>" required>
        </p>
        <p>
            <label>Nama</label><br>
            <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
        </p>
        <p>
            <label>Kategori</label><br>
            <select name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label>Harga</label><br>
            <input type="number" name="price" min="0" step="0.01" value="<?= htmlspecialchars($product['price']) ?>" required>
        </p>
        <p>
            <label>Stok minimum</label><br>
            <input type="number" name="minimum_stock" min="0" value="<?= (int) $product['minimum_stock'] ?>" required>
        </p>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>