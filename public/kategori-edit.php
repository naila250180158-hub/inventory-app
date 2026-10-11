<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id');
$stmt->execute(['id' => $id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    http_response_code(404);
    exit('Kategori tidak ditemukan.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $error = 'Nama kategori wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE categories SET name = :name WHERE id = :id');
            $stmt->execute(['name' => $name, 'id' => $id]);
            header('Location: kategori.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Kategori sudah ada.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Edit Kategori</h1>
    <p><a href="kategori.php">Kembali ke Kategori</a></p>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>