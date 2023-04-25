<?php


$filterBy = [];

if (isset($values_from_post_json['category'])) {
    $category_name = $values_from_post_json['category'];
    $filterBy[] = "category IN (SELECT id FROM categories WHERE category_name = '$category_name')";
}


$columns = implode(",", ["id", "stock_status", "created_date", "created_by", "is_active", "product_name", "description", "price", "category",]);

if (isset($values_from_post_json['params'])) {
    if (isset($values_from_post_json['params']['price_min'])) {
        $filterBy[] = "price >= " . $values_from_post_json['params']['price_min'];
    }
    if (isset($values_from_post_json['params']['price_max'])) {
        $filterBy[] = "price <= " . $values_from_post_json['params']['price_max'];
    }

    $attributes = array_filter($values_from_post_json['params'], function ($k) {
        return str_contains($k, 'attribute_');
    }, ARRAY_FILTER_USE_KEY);

    foreach ($attributes as $key => $values) {
        foreach ($values as $value) {
            $filterBy[] = "id IN (SELECT product FROM attr_prod_relation WHERE attribute_value = $value)";
        }
    }
}

$where_string = count($filterBy) > 0 ? " WHERE " . implode(" AND ", $filterBy) : "";

$qs = "SELECT $columns from products $where_string";
$products = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);

if (!is_array($products)) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка получения списка товаров',
    ]);
    exit();
}

$total = 0;

$per_page = $values_from_post_json['per_page'];

$total = $mysqli->query("SELECT COUNT(*) as total from products $where_string")->fetch_assoc()['total'];
$pages = ceil($total / $per_page);


echo json_encode([
    'success' => true,
    'data' => [
        'products' => $products,
        'total' => intval($total),
        'pages' => $pages,
    ],
]);
die();
