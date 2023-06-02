<?php

if(isset($values_from_post_json['init'])){
    $categories = $mysqli->query("SELECT COUNT(*) as total from products WHERE is_active=1")->fetch_all()['FETCH_ASSOC'];
    echo json_encode((
        $categories
    ));
    exit();
}


$total = $mysqli->query("SELECT COUNT(*) as total from products WHERE is_active=1")->fetch_assoc()['total'];

$page = $values_from_post_json['page'] - 1;
$per_page = $values_from_post_json['per_page'];
$pages = ceil($total / $per_page);

$offset = $page * $per_page;

$qs = "SELECT * from products ORDER BY id DESC LIMIT $offset, $per_page";

$products = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);

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

echo json_encode(
    [
        'success' => true,
        'data' => [
            'total' => $total,
            // 'qs' => $qs,
            'products' => $products,
            'pages' => $pages,
            // 'config' => $config,
            // '$values_from_post_json' => $values_from_post_json,
        ]
    ],
    JSON_UNESCAPED_UNICODE
);
