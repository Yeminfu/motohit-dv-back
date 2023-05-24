<?php

$slug = $values_from_post_json['product'];

$product = $mysqli->query("SELECT * FROM products WHERE slug = '$slug'")->fetch_assoc();

if (!$product) {
    echo json_encode([
        'success' => false,
        'error' => "Нет такого товара",

    ], JSON_UNESCAPED_UNICODE);
    die();
}

$product_id = $product['id'];

$images = $mysqli->query("SELECT name FROM `media` WHERE essense_id = $product_id ")->fetch_all(MYSQLI_ASSOC);
$product['images'] = array_map(function ($image) {
    global $config;
    return $config['homeurl'] . "/images/" . $image['name'];
}, $images);

$product_category = $product['category'];
$attributes = $mysqli->query("SELECT id, attribute_name FROM attributes WHERE category = '$product_category'")->fetch_all(MYSQLI_ASSOC);

$values = [];
foreach ($attributes as $key => $attribute) {
    $attribute_id = $attribute['id'];
    $value_qs = "SELECT value_name FROM attributes_values WHERE id IN
    (SELECT attribute_value FROM attr_prod_relation WHERE product = $product_id AND attribute = $attribute_id)
    ";
    $values[] = $value_qs;
    $attribute_value = $mysqli->query($value_qs)->fetch_assoc();
    $attributes[$key]['value'] = $attribute_value['value_name'] ?? "-";
}
$product['attributes'] = $attributes;

if ($product) {
    echo json_encode([
        'success' => true,
        'data' => $product,
        'values' => $values,

    ], JSON_UNESCAPED_UNICODE);
}
