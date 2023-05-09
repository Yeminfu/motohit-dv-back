<?php

$categories = $mysqli->query("SELECT id, category_name from categories")->fetch_all(MYSQLI_ASSOC);

foreach ($categories as $category_index => $category) {
    $attribute_qs = "SELECT id, attribute_name from attributes WHERE category = " . $category['id'];

    $attributes = $mysqli->query($attribute_qs)->fetch_all(MYSQLI_ASSOC);
    foreach ($attributes as $attribute_index => $attribute) {

        $attributes_values_qs = "SELECT * FROM attributes_values WHERE attribute = " . $attribute['id'];
        $attribute_values = $mysqli->query($attributes_values_qs)->fetch_all(MYSQLI_ASSOC);

        $attributes[$attribute_index]['values'] = $attribute_values;
    }

    $categories[$category_index]['attributes'] = $attributes;
}

echo json_encode([
    'success' => true,
    'data' => $categories,
]);

// $categories = $mysqli->query("SELECT value_name FROM attributes_values WHERE id IN
// (SELECT attribute FROM attr_prod_relation WHERE product = $product_id AND attribute = $attribute_id)
// ")->fetch_assoc();


// echo 'get-all-admin-attributesget-all-admin-attributes';