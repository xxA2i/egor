<?php

require_once 'config.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['msg'] = 'Доступ запрещён: удаление доступно только администратору.';
    header('Location: dashboard.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

$check = $dbConnection->prepare('SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1');
$check->bind_param('i', $id);
$check->execute();
$check->store_result();
$inOrder = $check->num_rows > 0;
$check->close();

if ($inOrder) {

    $_SESSION['msg'] = 'Невозможно удалить товар: он присутствует в заказе.';
    header('Location: dashboard.php');
    exit;
}

$stmt = $dbConnection->prepare('SELECT photo FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$photo = $stmt->get_result()->fetch_assoc()['photo'] ?? null;
$stmt->close();

if ($photo) {
    $photoPath = (strpos($photo, 'assets/photos/') === 0) ? $photo : 'assets/photos/' . $photo;
    if (file_exists(__DIR__ . '/' . $photoPath)) {
        unlink(__DIR__ . '/' . $photoPath);
    }
}

$stmt = $dbConnection->prepare('DELETE FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

$_SESSION['msg'] = 'Товар удалён.';
header('Location: dashboard.php');
exit;
