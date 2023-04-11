<?php

declare(strict_types=1);


ini_set('display_errors', 'On');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// error_reporting(-1);


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

require __DIR__ . "/api/modules/mysqli.php";

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
        try {

            $qs = "SELECT $columns from users $where_string";
            $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
            echo json_encode([
                'success' => true,
                'data' => $result,
                'description' => 'ответ на запрос списка пользователей',
            ]);
            exit();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    if ($values_from_post_json['service'] == 'create_user') {
        $cols = implode(', ', array_keys($values_from_post_json['data']));
        $values = implode(', ', array_map(function ($v) {
            return "'$v'";
        }, array_values($values_from_post_json['data'])));
        $qs = "INSERT INTO users ($cols) VALUES ($values)";
        try {
            $result = $mysqli->query($qs);
            echo json_encode([
                'success' => true,
                'data' => "всё хорошо",
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            // echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
        // if ($mysqli->query($qs)) {
        // }
    }

    if ($values_from_post_json['service'] == 'login') {

        if (!isset($values_from_post_json['username'])) {
            echo json_encode([
                'success' => false,
                'error' => "Вы не ввели логин"
            ]);
            exit();
        }
        if (!isset($values_from_post_json['password'])) {
            echo json_encode([
                'success' => false,
                'error' => "Вы не ввели пароль"
            ]);
            exit();
        }
        $username = $values_from_post_json['username'];
        $password = $values_from_post_json['password'];
        $qs = "SELECT * FROM users WHERE username='$username' AND password='$password' AND is_active = 1";

        $result = $mysqli->query($qs)->fetch_assoc();

        if (!$result) {
            echo json_encode([
                'success' => false,
                'error' => 'Нет такого пользователя',
            ]);
            exit();
        }

        if ($result) {
            echo json_encode([
                'success' => true,
                'data' => $result,
            ]);
            exit();
        }

        echo json_encode([
            'success' => false,
            'error' => 'Непредвиденная ошибка',
        ]);
        exit();
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

    if ($values_from_post_json['service'] == 'hints') {
        require_once __DIR__ . "/api/modules/smart_search.php";
        $text = $values_from_post_json['text'];
        if (!isset($values_from_post_json['text'])) {
            echo json_encode([
                "success" => false,
                "error" => "Нет входящей строки"
            ]);
            exit();
        }
        echo json_encode(
            smart_search($text)
        );
        exit();
    }

    if ($values_from_post_json['service'] == 'get-categories') {
        try {
            $qs = "SELECT * from categories WHERE is_active = 1";
            $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
        } catch (\Throwable $th) {
            //throw $th;
            echo json_encode([
                'success' => true,
                'error' => "Что-то пошло не так"
            ]);
            exit();
        }

        if (count($result) == 0) {
            echo json_encode([
                'success' => false,
                'error' => "Категории не созданы"
            ]);
            exit();
        }

        echo json_encode([
            'data' => $result
        ]);
        exit();
    }
}

if (isset($uri[1]) && $uri[1] == 'api') {
    if ((isset($uri[2]) && $uri[2] == 'create-product')) {
        $product_name = $_POST['product_name'];

        if ($mysqli->query("SELECT * from products WHERE product_name='$product_name'")->num_rows) {
            echo json_encode([
                'success' => false,
                'error' => 'Товар с таким названием уже существует',
            ]);
            exit();
        }

        $price = $_POST['price'];
        $description = $_POST['description'];
        $characteristics = json_decode($_POST['characteristics']);
        $files = $_FILES;

        try {
            $qs = "INSERT INTO products (product_name,price,description) VALUES ('$product_name','$price','$description');";
            $mysqli->query($qs);
            $new_product_id = $mysqli->insert_id;
        } catch (\Throwable $th) {
            echo json_encode([
                'success' => false,
                'error' => "Возможно не всё заполнили " . $th->getMessage(),
            ]);
            exit();
        }



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
                // 'name' => $product_name,
                'new_product_id' => $new_product_id,
                // 'price' => $price,
                // 'description' => $description,
                // 'images' => $mysqli->query("SELECT * FROM products_media WHERE product_id = '$new_product_id'")->fetch_all(MYSQLI_ASSOC),
            ],
        ]);

        exit();
    }
}
