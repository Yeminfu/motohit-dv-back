<?php

try {
    $qs = "SELECT * from categories WHERE is_active = 1";
    $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
} catch (\Throwable $th) {
    //throw $th;
    echo json_encode(
        [
            'success' => false,
            'error' => "Что-то пошло не так " . $th->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );
    exit();
}

if (count($result) == 0) {
    echo json_encode([
        'success' => false,
        'error' => "Категории не созданы"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $result
], JSON_UNESCAPED_UNICODE);
exit();