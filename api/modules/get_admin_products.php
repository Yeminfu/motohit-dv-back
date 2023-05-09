<?php


$filterBy = [];

$columns = implode(",", ["id", "stock_status", "created_date", "created_by", "is_active", "product_name", "description", "price", "category",]);

if (isset($values_from_post_json['params'])) {
    if (isset($values_from_post_json['params']['category'])) {
        $category = $values_from_post_json['params']['category'];
        $filterBy[] = "category = $category";
    }
}

$where_string = count($filterBy) > 0 ? " WHERE " . implode(" AND ", $filterBy) : "";

$total = 0;

$page = $values_from_post_json['page'];

$per_page = $values_from_post_json['per_page'];
$offset = ($page - 1) * $per_page;

$qs = "SELECT $columns from products $where_string LIMIT $offset, $per_page";

$products = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);

if (!is_array($products)) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка получения списка товаров',
    ], JSON_UNESCAPED_UNICODE);
    exit();
}


$total = $mysqli->query("SELECT COUNT(*) as total from products $where_string")->fetch_assoc()['total'];
$pages = ceil($total / $per_page);


echo json_encode([
    'success' => true,
    'data' => [
        'products' => $products,
        'total' => intval($total),
        'pages' => $pages,
    ],
], JSON_UNESCAPED_UNICODE);
die();
