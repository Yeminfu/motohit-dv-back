<?php
require __DIR__ . "/get_category.php";

$category = get_category($values_from_post_json['category']);

echo json_encode(
    [
        'data' => [
            'category' => $category,
        ]
    ],
    JSON_UNESCAPED_UNICODE
);
