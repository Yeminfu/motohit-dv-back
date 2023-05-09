<?php

$category = $values_from_post_json['category'];
$attribute = $values_from_post_json['attribute'];

$created_attribute = $mysqli->query("SELECT * FROM attributes WHERE category = $category AND attribute_name='$attribute'")->fetch_assoc();

if ($created_attribute) {
    echo json_encode([
        'success' => false,
        'error' => "У этой категории уже создан атрибут с таким названием"
    ],JSON_UNESCAPED_UNICODE);
    exit();
} else {
    $res = $mysqli->query("INSERT INTO attributes (attribute_name, category) VALUES ('$attribute', $category)");
    if ($res) {
        echo json_encode([
            'success' => true,
        ],JSON_UNESCAPED_UNICODE);
    }
}
