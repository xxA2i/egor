<?php

require_once 'config.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['msg'] = 'Доступ запрещён: удаление заказа доступно только администратору.';
    header('Location: dashboard.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $dbConnection->prepare('DELETE FROM orders WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

$_SESSION['msg'] = 'Заказ удалён.';
header('Location: dashboard.php');
exit;
