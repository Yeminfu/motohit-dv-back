<?php

$filterBy = [];
$columns = implode(",", ["id", "stock_status", "created_date", "created_by", "is_active", "product_name", "description", "price", "category",]);

if (isset($values_from_post_json['filterBy'])) {
    foreach ($values_from_post_json['filterBy'] as $key => $value) {
        $filterBy[] = "$key = '$value'";
    }
}


$where_string = count($filterBy) > 0 ? " WHERE " . implode(" AND ", $filterBy) : "";
$qs = "SELECT $columns from products $where_string";
$result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);

array_walk($result, function (&$product) {
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

    $images = $mysqli->query("SELECT name FROM `products_media` WHERE product_id = $product_id AND type='image_full'")->fetch_all(MYSQLI_ASSOC);
    $product['images'] = array_map(function ($image) {
        global $config;
        return $config['homeurl'] . "/images/" . $image['name'];
    }, $images);

    $product_status_id = $product['stock_status'];
    $stock_status = $mysqli->query("SELECT * FROM stock_statuses WHERE id = $product_status_id")->fetch_assoc();
    $product['stock_status'] = $stock_status['status_name'];
});

echo json_encode([
    'get_products' => $result,
    'description' => 'ответ на получение списка товаров',
]);
die();