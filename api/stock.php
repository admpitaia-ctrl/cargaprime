<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, 'Método não permitido.', 405);
}

$pdo = db();
$sql = is_admin()
    ? 'SELECT id, name, stock_tons, active FROM products ORDER BY id ASC'
    : 'SELECT id, name, stock_tons, active FROM products WHERE active = 1 ORDER BY id ASC';

$rows = $pdo->query($sql)->fetchAll();
$products = [];
foreach ($rows as $row) {
    $products[] = [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'stock_tons' => (float) $row['stock_tons'],
        'active' => (int) $row['active'] === 1,
    ];
}

json_response(true, 'Stock sincronizado.', 200, [
    'products' => $products,
    'updated_at' => date(DATE_ATOM),
]);
