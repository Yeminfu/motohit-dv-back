<?php

try {
    if (
        $mysqli->query("DELETE FROM categories WHERE parent = " . $values_from_post_json['category'])
        &&
        $mysqli->query("DELETE FROM categories WHERE id = " . $values_from_post_json['category'])
    ) {
        echo json_encode(
            [
                'success' => true,
            ],
            JSON_UNESCAPED_UNICODE,
        );
    } else {
        echo json_encode(
            [
                'success' => false,
                'error' => "Что-то пошло не так",
            ],
            JSON_UNESCAPED_UNICODE,
        );
    }
} catch (\Throwable $th) {
    echo json_encode(
        [
            'success' => false,
            'error' => "Что-то пошло не так >> " . $th->getMessage(),
        ],
        JSON_UNESCAPED_UNICODE,
    );
}
