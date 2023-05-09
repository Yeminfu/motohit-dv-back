<?php

require __DIR__ . "/get_product.php";
require __DIR__ . "/get_attributes.php";
require __DIR__ . "/get_categories.php";
require __DIR__ . "/get_stock_statuses.php";

$product_name = $values_from_post_json['product'];
$product = get_product($product_name);

$attributes = get_attributes($product['category']);

$categories = get_categories();
$stock_statuses = get_stock_statuses();

if ($product) {
    echo json_encode([
        'success' => true,
        'data' => [
            'product' => $product,
            'attributes' => $attributes,
            'categories' => $categories,
            'stock_statuses' => $stock_statuses,
        ],
    ], JSON_UNESCAPED_UNICODE);
}
