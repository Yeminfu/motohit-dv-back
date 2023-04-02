<?php

declare(strict_types=1);


error_reporting(E_ALL);
ini_set('display_errors', 'On');

header('Content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Credentials: true');

$json = file_get_contents('php://input');

$config = [
    'per_page_top_products' => 10
];

$mysqli = new mysqli("localhost", "admin", "xKF2eA", "china_kol");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}


if ($json) {
    $values_from_post_json = json_decode($json, true);

    if ($values_from_post_json['service'] == 'get_users') {
        $result = $mysqli->query("SELECT * from users")->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'get_users' => $result,
            'description' => 'ответ на получение списка пользователей'
        ]);
    }

    if ($values_from_post_json['service'] == 'create_user') {
        $cols = implode(', ', array_keys($values_from_post_json['data']));
        $values = implode(', ', array_map(function ($v) {
            return "'$v'";
        }, array_values($values_from_post_json['data'])));
        $qs = "INSERT INTO users ($cols) VALUES ($values)";
        $result = $mysqli->query($qs); //->fetch_all(MYSQLI_ASSOC);


        echo json_encode([
            'create_user' => $result,
            'description' => 'ответ на создание пользователя',
            '$values_from_post_json' => $values_from_post_json,
            '$cols' => $cols,
            '$values' => $values,
            '$qs' => $qs,
        ]);
    }
}
