<?php

if (!isset($values_from_post_json['category'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Нет категории товара'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$filterBy = [];

$category_name = $values_from_post_json['category'];

$filterBy[] = "category IN (SELECT id FROM categories WHERE category_name = '$category_name')";

$columns = implode(",", ["id", "stock_status", "created_date", "created_by", "is_active", "product_name", "slug", "description", "price", "category",]);

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
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

array_walk($products, function (&$product) {
    global $mysqli;
    $product_category = $product['category'];
    $product_id = $product['id'];

    $attributes = $mysqli->query("SELECT id, attribute_name FROM attributes WHERE category = '$product_category'")->fetch_all(MYSQLI_ASSOC);
    foreach ($attributes as $key => $attribute) {
        $attribute_id = $attribute['id'];
        $attribute_value = $mysqli->query("SELECT value_name FROM attributes_values WHERE id IN
            (SELECT attribute FROM attr_prod_relation WHERE product = $product_id AND attribute = $attribute_id)
        ")->fetch_assoc();
        $attributes[$key]['value'] = $attribute_value['value_name'] ?? "-";
    }
    $product['attributes'] = ($attributes);

    $images = $mysqli->query("SELECT name FROM `media` WHERE essense_id = $product_id AND type='product_image'")->fetch_all(MYSQLI_ASSOC);
    $product['images'] = array_map(function ($image) {
        global $config;
        return $config['homeurl'] . "/images/" . $image['name'];
    }, $images);

    $product_status_id = $product['stock_status'];
    $stock_status = $mysqli->query("SELECT * FROM stock_statuses WHERE id = $product_status_id")->fetch_assoc();
    $product['stock_status'] = $stock_status['status_name'];
});

echo json_encode([
    'success' => true,
    'data' => [
        'products' => $products
    ],
], JSON_UNESCAPED_UNICODE);
die();
