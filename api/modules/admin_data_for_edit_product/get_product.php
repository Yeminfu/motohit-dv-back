<?php

function get_product($product_name)
{
    global $mysqli;
    $product = $mysqli->query("SELECT * FROM products WHERE product_name = '$product_name'")->fetch_assoc();
    $product_id = $product['id'];

    $images = $mysqli->query("SELECT id, name FROM `products_media` WHERE product_id = $product_id ")->fetch_all(MYSQLI_ASSOC);

    $product['images'] = array_map(function ($image) {
        global $config;
        return [
            'id' => $image['id'],
            'src' => $config['homeurl'] . "/images/" . $image['name']
        ];
    }, $images);

    $product_category = $product['category'];
    $attributes = $mysqli->query("SELECT id, attribute_name FROM attributes WHERE category = '$product_category'")->fetch_all(MYSQLI_ASSOC);
    foreach ($attributes as $key => $attribute) {
        $attribute_id = $attribute['id'];
        $qsssssss = "SELECT value_name, id as value_id FROM attributes_values WHERE id IN
        (SELECT attribute FROM attr_prod_relation WHERE product = $product_id AND attribute = $attribute_id)
    ";

        $attribute_value = $mysqli->query($qsssssss)->fetch_assoc();
        $attributes[$key]['value'] = $attribute_value['value_name'] ?? "-";
        $attributes[$key]['value_id'] = $attribute_value['value_id'] ?? "-";
        // $attributes[$key]['value'] = $attribute_value['value_name'] ?? "-";
    }
    $product['attributes'] = $qsssssss;
    return $product;
}
