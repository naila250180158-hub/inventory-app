<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$error = '';

//Hapus produk (khusus admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak. Hanya admin yang boleh menghapus produk.');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['delete_id']]);
        header('Location: produk.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Produk sudah punya riwayat barang masuk/keluar, tidak bisa dihapus.';
    }
}

// Tambah produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $minimum_stock = $_POST['minimum_stock'] ?? '';

    if ($sku === '' || $name === '' || $category_id === 0) {
        $error = 'SKU, nama, dan kategori wajib diisi.';
    } elseif (!is_numeric($price) || $price < 0) {
        $error = 'Harga tidak boleh negatif.';
    } elseif (!ctype_digit((string) $stock) || !ctype_digit((string) $minimum_stock)) {
        $error = 'Stok harus berupa angka bulat, tidak boleh negatif.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO products (category_id, sku, name, price, stock, minimum_stock)
                 VALUES (:category_id, :sku, :name, :price, :stock, :minimum_stock)'
            );
            $stmt->execute([
                'category_id' => $category_id,
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'stock' => $stock,
                'minimum_stock' => $minimum_stock,
            ]);
            header('Location: produk.php');
            exit;
                } catch (PDOException $e) {
            $error = 'SKU sudah dipakai, atau kategori tidak valid.';
        }
    }
}


$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$q = trim($_GET['q'] ?? '');
$filter_category = (int) ($_GET['category'] ?? 0);

$where = '(p.name LIKE :q OR p.sku LIKE :q)';
$params = ['q' => '%' . $q . '%'];

if ($filter_category > 0) {
    $where .= ' AND p.category_id = :category_id';
    $params['category_id'] = $filter_category;
}

$sort_map = [
    'name_asc' => 'p.name ASC',
    'name_desc' => 'p.name DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'stock_asc' => 'p.stock ASC',
    'stock_desc' => 'p.stock DESC',
];
$sort = $_GET['sort'] ?? 'name_asc';
$order_by = $sort_map[$sort] ?? 'p.name ASC';

$per_page = 5;
$page = max(1, (int) ($_GET['page'] ?? 1));

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $where");
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare(
    "SELECT p.id, p.sku, p.name, p.price, p.stock, c.name AS category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE $where
     ORDER BY $order_by
     LIMIT $per_page OFFSET $offset"
);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Produk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Produk</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <h2>Tambah Produk</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <p>
            <label>SKU</label><br>
            <input type="text" name="sku" required>
        </p>
        <p>
            <label>Nama</label><br>
            <input type="text" name="name" required>
        </p>
        <p>
            <label>Kategori</label><br>
            <select name="category_id" required>
                <option value="">-- Pilih kategori --</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label>Harga</label><br>
            <input type="number" name="price" min="0" step="0.01" required>
        </p>
        <p>
            <label>Stok awal</label><br>
            <input type="number" name="stock" min="0" value="0" required>
        </p>
        <p>
            <label>Stok minimum</label><br>
            <input type="number" name="minimum_stock" min="0" value="5" required>
        </p>
        <button type="submit">Simpan</button>
    </form>

    <h2>Cari Produk</h2>
<form method="get">
    <input type="text" name="q" placeholder="Nama atau SKU" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <select name="category">
    <option value="">Semua kategori</option>
    <?php foreach ($categories as $category): ?>
        <option value="<?= $category['id'] ?>" <?= ($_GET['category'] ?? '') == $category['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($category['name']) ?>
        </option>
    <?php endforeach; ?>
</select>
<select name="sort">
    <?php
    $sort_options = [
        'name_asc' => 'Nama A-Z',
        'name_desc' => 'Nama Z-A',
        'price_asc' => 'Harga termurah',
        'price_desc' => 'Harga termahal',
        'stock_asc' => 'Stok tersedikit',
        'stock_desc' => 'Stok terbanyak',
    ];
    foreach ($sort_options as $value => $label): ?>
        <option value="<?= $value ?>" <?= ($_GET['sort'] ?? 'name_asc') === $value ? 'selected' : '' ?>>
            <?= $label ?>
        </option>
    <?php endforeach; ?>
</select>
    <button type="submit">Cari</button>
    <a href="produk.php">Reset</a>
</form>
<br>
    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>SKU</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Aksi</th>
        </tr>
        <?php foreach ($products as $i => $product): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($product['sku']) ?></td>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td><?= htmlspecialchars($product['category_name']) ?></td>
            <td><?= number_format($product['price'], 0, ',', '.') ?></td>
            <td><?= $product['stock'] ?></td>
            <td>
                <a href="produk-edit.php?id=<?= $product['id'] ?>">Edit</a>
                <form method="post" onsubmit="return confirm('Hapus produk ini?')">
                    <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                    <button type="submit">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php
$base_query = $_GET;
unset($base_query['page']);
?>
<p>
    <?php if ($page > 1): ?>
        <a href="?<?= http_build_query($base_query + ['page' => $page - 1]) ?>">Sebelumnya</a>
    <?php endif; ?>

    Halaman <?= $page ?> dari <?= $total_pages ?>

    <?php if ($page < $total_pages): ?>
        <a href="?<?= http_build_query($base_query + ['page' => $page + 1]) ?>">Berikutnya</a>
    <?php endif; ?>
</p>
</body>
</html>