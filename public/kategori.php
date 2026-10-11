<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $error = 'Nama kategori wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
            $stmt->execute(['name' => $name]);
            header('Location: kategori.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Kategori sudah ada.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak. Hanya admin yang boleh menghapus kategori.');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['delete_id']]);
        header('Location: kategori.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Kategori masih dipakai oleh produk, tidak bisa dihapus.';
    }
}
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kategori</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Kategori</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <h2>Tambah Kategori</h2>

    <?php if ($error): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<form method="post">
    <input type="text" name="name" placeholder="Nama kategori" required>
    <button type="submit">Simpan</button>
</form>


    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>Nama Kategori</th>

            <th>Aksi</th>
        </tr>
        <?php foreach ($categories as $i => $category): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($category['name']) ?></td>
            <td>
                <a href="kategori-edit.php?id=<?= $category['id'] ?>">Edit</a>
    <form method="post" onsubmit="return confirm('Hapus kategori ini?')">
        <input type="hidden" name="delete_id" value="<?= $category['id'] ?>">
        <button type="submit">Hapus</button>
    </form>
</td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>