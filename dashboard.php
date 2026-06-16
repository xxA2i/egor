<?php

// подключение конфигурации
require_once 'config.php';
require_once 'item_repository.php';

// проверка авторизации
if (!isset($_SESSION['role'])) {
    header('Location: index.php');
    exit;
}

$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];

$pageTitle = 'Панель управления - СтройМатериалы';

$search    = trim($_GET['search'] ?? '');
$sortField = $_GET['sort']  ?? '';
$sortDir   = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$manFilter = $_GET['man']   ?? ''; 

// выборка товаров из базы
$products = getItemsFromDb($dbConnection, $_GET);

$manufacturers = [];
$mres = $dbConnection->query('SELECT id, name FROM manufacturers ORDER BY name');
while ($row = $mres->fetch_assoc()) {
    $manufacturers[] = $row;
}

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="theme.css">
    <link rel="icon" href="assets/icon.png">
</head>
<body>
<div class="navbar">
    <div class="navbar-left">
        <img src="assets/logo.png" alt="Логотип" class="logo-sm" onerror="this.style.display='none'">
        <span class="brand">СтройМатериалы</span>
    </div>
    <div class="navbar-right">
        <span class="user-name-display"><?= e($fullName) ?></span>
        <a class="btn btn-alt btn-small" href="logout.php">Выйти</a>
    </div>
</div>

<div class="container">
    <?php if ($msg): ?>
        <div class="msg msg-info"><?= e($msg) ?></div>
    <?php endif; ?>

    <h1>Список товаров</h1>

    <?php if ($role === 'manager' || $role === 'admin'): ?>
    <div class="filters" id="filters">
        <input type="text" id="search" placeholder="Поиск..." value="<?= e($search) ?>"
               autocomplete="off">
        <select id="man">
            <option value="">Все производители</option>
            <?php foreach ($manufacturers as $m): ?>
                <option value="<?= e($m['name']) ?>" <?= $manFilter === $m['name'] ? 'selected' : '' ?>>
                    <?= e($m['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="sort">
            <?php
            $opts = ['' => 'Без сортировки', 'stock' => 'Количество', 'price' => 'Цена', 'discount' => 'Скидка'];
            foreach ($opts as $k => $v):
            ?>
                <option value="<?= $k ?>" <?= $sortField === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <select id="dir">
            <option value="asc"  <?= $sortDir === 'asc'  ? 'selected' : '' ?>>По возрастанию</option>
            <option value="desc" <?= $sortDir === 'desc' ? 'selected' : '' ?>>По убыванию</option>
        </select>
        <span class="filter-status" id="filterStatus"></span>
    </div>
    <script>

    // асинхронная фильтрация товаров
    var timer = null;
    function loadItemsAjax() {
        var s  = document.getElementById('search').value;
        var m  = document.getElementById('man').value;
        var so = document.getElementById('sort').value;
        var d  = document.getElementById('dir').value;
        var qs = new URLSearchParams();
        if (s)  qs.set('search', s);
        if (m)  qs.set('man', m);
        if (so) qs.set('sort', so);
        if (d)  qs.set('dir', d);
        var status = document.getElementById('filterStatus');
        status.textContent = 'Обновление...';

        fetch('api_items.php?' + qs.toString())
            .then(function(r) { return r.text(); })
            .then(function(html) {
                document.getElementById('itemsContainer').innerHTML = html;
                status.textContent = '';
            })
            .catch(function() {
                status.textContent = 'Ошибка загрузки.';
            });
    }

    function onFilterChange() {
        clearTimeout(timer);
        timer = setTimeout(loadItemsAjax, 300);
    }
    document.getElementById('search').addEventListener('input', onFilterChange);
    document.getElementById('man').addEventListener('change', onFilterChange);
    document.getElementById('sort').addEventListener('change', onFilterChange);
    document.getElementById('dir').addEventListener('change', onFilterChange);
    </script>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
        <p><a class="btn btn-primary" href="item_edit.php">Добавить товар</a></p>
    <?php endif; ?>

    <div class="items-layout" id="itemsContainer">
        <?= generateItemCards($products, $role) ?>
    </div>

    <?php if ($role === 'manager' || $role === 'admin'): ?>
        <h1 class="section-title">Заказы</h1>
        <?php if ($role === 'admin'): ?>
            <p><a class="btn btn-primary" href="order_edit.php">Добавить заказ</a></p>
        <?php endif; ?>

        <?php
        // получение списка заказов
        $ores = $dbConnection->query(
            "SELECT o.*, pp.address AS pickup, u.full_name AS client, os.name AS status
             FROM orders o
             LEFT JOIN pickup_points pp ON o.pickup_point_id = pp.id
             LEFT JOIN users u          ON o.client_id = u.id
             LEFT JOIN order_statuses os ON o.status_id = os.id
             ORDER BY o.id"
        );
        $orders = $ores->fetch_all(MYSQLI_ASSOC);
        ?>
        <div class="orders-layout">
            <?php foreach ($orders as $o): ?>
                <div class="order-box">
                    <div class="box-image-side">
                        <div class="order-article">Артикул: <?= e($o['article_code']) ?></div>
                        <div class="order-details">
                            <p><strong>Статус:</strong> <?= e($o['status']) ?></p>
                            <p><strong>Адрес пункта выдачи:</strong> <?= e($o['pickup']) ?></p>
                            <p><strong>Дата заказа:</strong> <?= e($o['order_date']) ?></p>
                        </div>
                    </div>
                    <div class="box-action-side">
                        <div class="order-delivery">
                            <small>Дата доставки:</small>
                            <br>
                            <strong><?= e($o['delivery_date']) ?></strong>
                        </div>
                        <?php if ($role === 'admin'): ?>
                        <div class="actions card-actions">
                            <a class="btn btn-alt btn-small" href="order_edit.php?id=<?= (int)$o['id'] ?>">Изменить</a>
                            <a class="btn btn-warn btn-small" href="order_remove.php?id=<?= (int)$o['id'] ?>"
                               onclick="return confirm('Удалить заказ?')">Удалить</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
                <div class="empty">Заказов нет.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
