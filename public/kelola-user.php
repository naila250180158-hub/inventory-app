<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini hanya untuk admin.');
}

$error = '';

// Hapus user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];

    if ($delete_id === (int) $_SESSION['user']['id']) {
        $error = 'Kamu tidak bisa menghapus akunmu sendiri.';
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $delete_id]);
            header('Location: kelola-user.php');
            exit;
        } catch (PDOException $e) {
            $error = 'User ini sudah punya riwayat barang masuk/keluar, tidak bisa dihapus.';
        }
    }
}

// Tambah user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $error = 'Username harus 3 sampai 50 karakter, hanya huruf, angka, dan garis bawah.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif (!in_array($role, ['admin', 'staff'], true)) {
        $error = 'Role harus admin atau staff.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)'
            );
            $stmt->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
            ]);
            header('Location: kelola-user.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Username sudah dipakai.';
        }
    }
}

$users = $pdo->query('SELECT id, username, role FROM users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Kelola User</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <h2>Tambah User</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <p>
            <label>Username</label><br>
            <input type="text" name="username" required>
        </p>
        <p>
            <label>Password</label><br>
            <input type="password" name="password" required>
        </p>
        <p>
            <label>Role</label><br>
            <select name="role" required>
                <option value="staff">staff</option>
                <option value="admin">admin</option>
            </select>
        </p>
        <button type="submit">Simpan</button>
    </form>

    <h2>Daftar User</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>Username</th>
            <th>Role</th>
            <th>Aksi</th>
        </tr>
        <?php foreach ($users as $i => $user): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td>
                <?php if ((int) $user['id'] === (int) $_SESSION['user']['id']): ?>
                    (akun kamu)
                <?php else: ?>
                    <form method="post" onsubmit="return confirm('Hapus user ini?')">
                        <input type="hidden" name="delete_id" value="<?= $user['id'] ?>">
                        <button type="submit">Hapus</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
