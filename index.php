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


$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

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
        $filterBy = [];
        $columns = implode(',', $values_from_post_json['columns']);
        if (isset($values_from_post_json['filterBy'])) {
            foreach ($values_from_post_json['filterBy'] as $key => $value) {
                $filterBy[] = "$key = '$value'";
            }
        }
        $where_string = count($filterBy) > 0 ? " WHERE " . implode(" AND ", $filterBy) : "";
        $qs = "SELECT $columns from users $where_string";
        $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            'get_users' => $result,
            'description' => 'ответ на получение списка пользователей',
        ]);
    }

    if ($values_from_post_json['service'] == 'create_user') {
        $cols = implode(', ', array_keys($values_from_post_json['data']));
        $values = implode(', ', array_map(function ($v) {
            return "'$v'";
        }, array_values($values_from_post_json['data'])));
        $qs = "INSERT INTO users ($cols) VALUES ($values)";
        $result = $mysqli->query($qs);
        echo json_encode([
            'create_user' => $result,
            'description' => 'ответ на создание пользователя',
        ]);
    }

    if ($values_from_post_json['service'] == 'delete_user') {
        $userId = $values_from_post_json['userId'];
        $qs = "UPDATE users SET is_active = 0 WHERE id = $userId";
        $result = $mysqli->query($qs);
        echo json_encode([
            "delete_user" => $result,
        ]);
    }

    if ($values_from_post_json['service'] == 'edit_users') {
        $userId = $values_from_post_json['userId'];
        $data = $values_from_post_json['data'];
        $tostring = [];
        foreach ($data as $key => $value) {
            $tostring[] = "$key = '$value'";
        }
        $qs = "UPDATE users SET " . implode(', ', $tostring) . " WHERE id = $userId";
        $result = $mysqli->query($qs);
        echo json_encode([
            "edit_users" => $result,
            "description" => "Обновляем данные пользователя"
        ]);
    }

    if ($values_from_post_json['service'] == 'get_products') {
        $filterBy = [];
        $columns = implode(',', $values_from_post_json['columns']);
        if (isset($values_from_post_json['filterBy'])) {
            foreach ($values_from_post_json['filterBy'] as $key => $value) {
                $filterBy[] = "$key = '$value'";
            }
        }
        $where_string = count($filterBy) > 0 ? " WHERE " . implode(" AND ", $filterBy) : "";
        $qs = "SELECT $columns from products $where_string";
        $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            'get_products' => $result,
            'description' => 'ответ на получение списка товаров',
        ]);
    }

    if ($values_from_post_json['service'] == 'delete_product') {
        $productId = $values_from_post_json['productId'];
        $qs = "UPDATE products SET is_active = 0 WHERE id = $productId";
        $result = $mysqli->query($qs);
        echo json_encode([
            "delete_product" => $result,
        ]);
    }
}

if (isset($uri[1]) && $uri[1] == 'api') {
    if ((isset($uri[2]) && $uri[2] == 'create-product')) {
        $product_name = $_POST['product_name'];

        if ($mysqli->query("SELECT * from products WHERE product_name='$product_name'")->num_rows) {
            echo json_encode([
                'success' => false,
                'error' => 'Товар с таким названием уже существует',
            ]); // не удалять, НУЖНАЯ ВЕЩЬ
            exit();
        }

        $price = $_POST['price'];
        $description = $_POST['description'];
        $supplier = $_POST['supplier'];
        $characteristics = json_decode($_POST['characteristics']);
        $files = $_FILES;

        $qs = "INSERT INTO products (product_name,price,description,supplier) VALUES ('$product_name','$price','$description','$supplier');";
        $mysqli->query($qs);
        $new_product_id = $mysqli->insert_id;



        foreach ($files as $file_name => $file) {
            if ($mysqli->query("SELECT * from products_media WHERE name='$file_name'")->num_rows) { //проверка на наименование файла в бд
                echo json_encode([
                    'success' => false,
                    'error' => "Файл с именем '$file_name' уже существует",
                ]);
                exit();
            } else {
                $uploaddir = __DIR__ . '/images/';
                $uploadfile = $uploaddir . basename($file['name']);
                if (move_uploaded_file($file['tmp_name'], $uploadfile)) {
                    $qs = "INSERT INTO products_media (type,name,product_id) VALUES ('image_full','$file_name','$new_product_id')";
                    $result = $mysqli->query($qs);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => "Не удалось сохранить '$file_name'. Пожалуйста обратитесь в службу поддержки",
                    ]);
                }
            }
        }
        echo json_encode([
            'success' => true,
            'product' => [
                'name' => $product_name,
                'new_product_id' => $new_product_id,
                'price' => $price,
                'description' => $description,
                'supplier' => $supplier,
                'images' => $mysqli->query("SELECT * FROM products_media WHERE product_id = '$new_product_id'")->fetch_all(MYSQLI_ASSOC),
            ],
        ]);
        exit();
    }
}
