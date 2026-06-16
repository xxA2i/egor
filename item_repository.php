<?php

function getItemsFromDb(mysqli $dbConnection, array $in): array {
    $search    = trim($in['search'] ?? '');
    $sortField = $in['sort']  ?? '';
    $sortDir   = ($in['dir']  ?? 'asc') === 'desc' ? 'desc' : 'asc';
    $manFilter = $in['man']   ?? '';

    $allowedSort = [
        'stock'    => 'p.stock',
        'price'    => 'p.price',
        'discount' => 'p.discount',
    ];
    $orderClause = 'ORDER BY p.name';
    if (isset($allowedSort[$sortField])) {
        $orderClause = 'ORDER BY ' . $allowedSort[$sortField] . ' ' . strtoupper($sortDir);
    }

    $sql = "SELECT p.*, c.name AS category_name, m.name AS manufacturer_name,
                   s.name AS supplier_name, u.name AS unit_name
            FROM products p
            JOIN categories c     ON p.category_id = c.id
            JOIN manufacturers m  ON p.manufacturer_id = m.id
            JOIN suppliers s      ON p.supplier_id = s.id
            JOIN units u          ON p.unit_id = u.id
            WHERE 1=1";

    $params = [];
    $types  = '';

    if ($manFilter !== '') {
        $sql .= " AND m.name = ?";
        $params[] = $manFilter;
        $types  .= 's';
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.article LIKE ?
                    OR m.name LIKE ? OR s.name LIKE ? OR c.name LIKE ?)";
        $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        $types .= 'ssssss';
    }
    $sql .= ' ' . $orderClause;

    $stmt = $dbConnection->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $products;
}

function generateItemCards(array $products, string $role): string {
    $html = '';
    foreach ($products as $p) {

        $rowClass = 'item-box';
        // проверка отсутствия на складе
        if ((int)$p['stock'] === 0) {
            $rowClass .= ' highlight-out';
        } elseif ((int)$p['discount'] > 12) {
            // выделение скидки больше 12 процентов
            $rowClass .= ' highlight-sale';
        }
        $discounted = (int)$p['discount'] > 0;
        $finalPrice = $discounted
            ? round($p['price'] * (100 - $p['discount']) / 100, 2)
            : (float)$p['price'];

        $photoField = $p['photo'] ?? '';
        $photoPath = (strpos($photoField, 'assets/photos/') === 0) ? $photoField : 'assets/photos/' . $photoField;

        $photo = $photoField && file_exists(__DIR__ . '/' . $photoPath)
            ? $photoPath
            : 'assets/picture.png';

        $html .= '<div class="' . $rowClass . '">';

        $html .= '<div class="box-image-side">';
        $html .= '<img src="' . e($photo) . '" alt="" class="item-picture" onerror="this.src=\'assets/picture.png\'">';
        $html .= '</div>';

        $html .= '<div class="box-info-side">';
        $html .= '<div class="item-heading">' . e($p['category_name']) . ' | <strong>' . e($p['name']) . '</strong></div>';
        $html .= '<div class="item-specs-stack">';
        $html .= '<p><strong>Описание товара:</strong> ' . e($p['description']) . '</p>';
        $html .= '<p><strong>Производитель:</strong> ' . e($p['manufacturer_name']) . '</p>';
        $html .= '<p><strong>Поставщик:</strong> ' . e($p['supplier_name']) . '</p>';
        $html .= '<p><strong>Цена:</strong> ';
        if ($discounted) {
            $html .= '<span class="old-cost">' . e($p['price']) . '</span> <span class="new-cost">' . e($finalPrice) . '</span>';
        } else {
            $html .= e($p['price']);
        }
        $html .= '</p>';
        $html .= '<p><strong>Единица измерения:</strong> ' . e($p['unit_name']) . '</p>';
        $html .= '<p><strong>Количество на складе:</strong> ' . (int)$p['stock'] . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="box-action-side">';
        if ($discounted) {
            $html .= '<div class="item-sale">Действующая скидка:<br><strong>' . (int)$p['discount'] . '%</strong></div>';
        }
        if ($role === 'admin') {
            $html .= '<div class="actions card-actions">';
            $html .= '<a class="btn btn-alt btn-small" href="item_edit.php?id=' . (int)$p['id'] . '">Изменить</a>';
            $html .= '<a class="btn btn-warn btn-small" href="item_remove.php?id=' . (int)$p['id'] . '" onclick="return confirm(\'Удалить товар?\')">Удалить</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    if (!$products) {
        $html .= '<div class="empty">Товары не найдены.</div>';
    }
    return $html;
}
