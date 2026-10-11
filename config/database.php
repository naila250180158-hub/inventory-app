<?php
$pdo = new PDO(
    'mysql:host=localhost;dbname=inventory_db;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);