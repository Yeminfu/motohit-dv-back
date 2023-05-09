<?php

$attribute = $values_from_post_json['attribute'];
$attribute_value = $values_from_post_json['attribute-value'];

$created_attribute_value = $mysqli->query("SELECT * FROM attributes_values WHERE attribute = $attribute AND value_name='$attribute_value'")->fetch_assoc();

if ($created_attribute_value) {
    echo json_encode([
        'success' => false,
        'error' => "У этого атрибута уже есть значение с таким названием"
    ], JSON_UNESCAPED_UNICODE);
    exit();
} else {
    $res = $mysqli->query("INSERT INTO attributes_values (attribute, value_name) VALUES ($attribute, '$attribute_value')");
    if ($res) {
        echo json_encode([
            'success' => true,
        ], JSON_UNESCAPED_UNICODE);
    }
}
