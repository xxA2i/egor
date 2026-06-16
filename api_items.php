<?php

require_once 'config.php';
require_once 'item_repository.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['role'])) {
    http_response_code(403);
    echo '<tr><td class="empty">Доступ запрещён.</td></tr>';
    exit;
}

$role = $_SESSION['role'];
$products = getItemsFromDb($dbConnection, $_GET);

echo generateItemCards($products, $role);
