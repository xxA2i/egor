<?php

require_once 'config.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['msg'] = 'Доступ запрещён: заказы может изменять только администратор.';
    header('Location: dashboard.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$order = null;

if ($isEdit) {
    $stmt = $dbConnection->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) {
        $_SESSION['msg'] = 'Заказ не найден.';
        header('Location: dashboard.php');
        exit;
    }
}

$statuses = $dbConnection->query('SELECT id, name FROM order_statuses ORDER BY id')->fetch_all(MYSQLI_ASSOC);
$pickups  = $dbConnection->query('SELECT id, address FROM pickup_points ORDER BY id')->fetch_all(MYSQLI_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articleCode = trim($_POST['article_code'] ?? '');
    $statusId    = (int)($_POST['status_id'] ?? 0);
    $pickupId    = (int)($_POST['pickup_point_id'] ?? 0);
    // 0 = не указан: сохраняем как NULL, чтобы не нарушить внешний ключ
    if ($pickupId === 0) {
        $pickupId = null;
    }
    $orderDate   = trim($_POST['order_date'] ?? '');
    $deliveryDate= trim($_POST['delivery_date'] ?? '');

    if ($articleCode === '') {
        $error = 'Укажите артикул заказа.';
    } elseif ($statusId === 0) {
        $error = 'Выберите статус заказа.';
    } elseif ($orderDate === '') {
        $error = 'Укажите дату заказа.';
    } elseif ($deliveryDate === '') {
        $error = 'Укажите дату выдачи.';
    } elseif ($deliveryDate < $orderDate) {
        // дата выдачи не может быть раньше даты заказа
        $error = 'Дата выдачи не может быть раньше даты заказа.';
    } else {
        if ($isEdit) {

            $stmt = $dbConnection->prepare(
                'UPDATE orders SET article_code=?, status_id=?, pickup_point_id=?,
                    order_date=?, delivery_date=? WHERE id=?'
            );
            $stmt->bind_param('siissi', $articleCode, $statusId, $pickupId,
                $orderDate, $deliveryDate, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['msg'] = 'Заказ обновлён.';
        } else {

            $stmt = $dbConnection->prepare(
                'INSERT INTO orders (article_code, status_id, pickup_point_id, order_date, delivery_date)
                 VALUES (?,?,?,?,?)'
            );
            $stmt->bind_param('siiss', $articleCode, $statusId, $pickupId,
                $orderDate, $deliveryDate);
            $stmt->execute();
            $stmt->close();
            $_SESSION['msg'] = 'Заказ добавлен.';
        }
        header('Location: dashboard.php');
        exit;
    }
}

$o = $order ?? [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Редактирование' : 'Добавление' ?> заказа - СтройМатериалы</title>
    <link rel="stylesheet" href="theme.css">
    <link rel="icon" href="assets/icon.png">
</head>
<body>
<div class="navbar">
    <div class="navbar-left">
        <a class="btn btn-alt btn-small" href="dashboard.php">&larr; Назад</a>
        <span class="brand">СтройМатериалы</span>
    </div>
    <div class="navbar-right">
        <span class="user-name-display"><?= e($_SESSION['full_name']) ?></span>
        <a class="btn btn-alt btn-small" href="logout.php">Выйти</a>
    </div>
</div>

<div class="container">
    <h1><?= $isEdit ? 'Редактирование заказа' : 'Добавление заказа' ?></h1>

    <?php if ($error): ?>
        <div class="msg msg-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="form-card">
        <?php if ($isEdit): ?>
        <label>Номер заказа
            <input type="text" value="<?= (int)$o['id'] ?>" readonly disabled>
        </label>
        <?php endif; ?>

        <label>Артикул
            <input type="text" name="article_code" required
                   value="<?= e($_POST['article_code'] ?? $o['article_code'] ?? '') ?>">
        </label>

        <label>Статус заказа
            <select name="status_id" required>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s['id'] ?>"
                        <?= (int)($o['status_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Адрес пункта выдачи
            <select name="pickup_point_id">
                <option value="0">&mdash; не указан &mdash;</option>
                <?php foreach ($pickups as $pp): ?>
                    <option value="<?= $pp['id'] ?>"
                        <?= (int)($o['pickup_point_id'] ?? 0) === (int)$pp['id'] ? 'selected' : '' ?>>
                        <?= e($pp['address']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="form-row">
            <label>Дата заказа
                <input type="date" name="order_date" required
                       value="<?= e($_POST['order_date'] ?? $o['order_date'] ?? '') ?>">
            </label>
            <label>Дата выдачи
                <input type="date" name="delivery_date" required
                       value="<?= e($_POST['delivery_date'] ?? $o['delivery_date'] ?? '') ?>">
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a class="btn btn-alt" href="dashboard.php">Отмена</a>
        </div>
    </form>
</div>
</body>
</html>
