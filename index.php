<?php

declare(strict_types=1);


ini_set('display_errors', 'On');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// error_reporting(-1);


header('Content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Expose-Headers: SID');
// Access-Control-Expose-Headers: Access-Token, Uid

$json = file_get_contents('php://input');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

$config = [
    'per_page_top_products' => 10,
    "uploaddir" => __DIR__ . '/images',
    "homeurl" => "http://motohit-dv.ru"
];

require __DIR__ . "/api/modules/mysqli.php";

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}


use Firebase\JWT\JWT;
use Firebase\JWT\Key;




/**
 * IMPORTANT:
 * You must specify supported algorithms for your application. See
 * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
 * for a list of spec-compliant algorithms.
 */
require __DIR__ . '/vendor/autoload.php';


$values_from_post_json = json_decode($json, true);


// echo json_encode(
//     [
//         'query string' => $qs->get_query_string(),
//         "result" => $mysqli->query(
//             strval($qs->get_query_string())
//         )->fetch_all(MYSQLI_ASSOC)
//     ]
// );

// echo json_encode(
//     (new OrderRepository(1231))->load()
// );
// exit();


if ($json && isset($values_from_post_json['service'])) {

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
            ], JSON_UNESCAPED_UNICODE);
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
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
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
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        if (!isset($values_from_post_json['password'])) {
            echo json_encode([
                'success' => false,
                'error' => "Вы не ввели пароль"
            ], JSON_UNESCAPED_UNICODE);
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
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        if ($result) {
            echo json_encode([
                'success' => true,
                'data' => $result,
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        echo json_encode([
            'success' => false,
            'error' => 'Непредвиденная ошибка',
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    if ($values_from_post_json['service'] == 'delete_user') {
        $userId = $values_from_post_json['userId'];
        $qs = "UPDATE users SET is_active = 0 WHERE id = $userId";
        $result = $mysqli->query($qs);
        echo json_encode([
            "delete_user" => $result,
        ], JSON_UNESCAPED_UNICODE);
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
        ], JSON_UNESCAPED_UNICODE);
    }

    if ($values_from_post_json['service'] == 'get_products') {
        $filterBy = [];
        $columns = implode(",", ["id", "stock_status", "created_date", "created_by", "is_active", "product_name", "description", "price", "category",]);

        if (isset($values_from_post_json['filterBy'])) {
            foreach ($values_from_post_json['filterBy'] as $key => $value) {
                $filterBy[] = "$key = '$value'";
            }
        }


        $where_string = count($filterBy) > 0 ? " WHERE " . implode(" AND ", $filterBy) : "";
        $qs = "SELECT $columns from products $where_string";
        $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);

        array_walk($result, function (&$product) {
            global $mysqli;
            $product_category = $product['category'];
            $product_id = $product['id'];

            $attributes = $mysqli->query("SELECT id, attribute_name FROM attributes WHERE category = '$product_category'")->fetch_all(MYSQLI_ASSOC);
            foreach ($attributes as $key => $attribute) {
                $attribute_id = $attribute['id'];
                $attribute_value = $mysqli->query("SELECT value_name FROM attributes_values WHERE id IN
                    (SELECT attribute FROM attr_prod_relation WHERE product = $product_id AND attribute = $attribute_id)
                ")->fetch_assoc();
                $attributes[$key]['value'] = $attribute_value['value_name'] ?? "-";
            }
            $product['attributes'] = ($attributes);

            $images = $mysqli->query("SELECT name FROM `media` WHERE essense_id = $product_id AND type='product_image'")->fetch_all(MYSQLI_ASSOC);
            $product['images'] = array_map(function ($image) {
                global $config;
                return $config['homeurl'] . "/images/" . $image['name'];
            }, $images);

            $product_status_id = $product['stock_status'];
            $stock_status = $mysqli->query("SELECT * FROM stock_statuses WHERE id = $product_status_id")->fetch_assoc();
            $product['stock_status'] = $stock_status['status_name'];
        });

        echo json_encode([
            'get_products' => $result,
            'description' => 'ответ на получение списка товаров',
        ], JSON_UNESCAPED_UNICODE);
    }

    if ($values_from_post_json['service'] == 'get-product') {
        $product_name = $values_from_post_json['product'];

        $product = $mysqli->query("SELECT * FROM products WHERE product_name = '$product_name'")->fetch_assoc();
        $product_id = $product['id'];

        $images = $mysqli->query("SELECT name FROM `media` WHERE essense_id = $product_id ")->fetch_all(MYSQLI_ASSOC);
        $product['images'] = array_map(function ($image) {
            global $config;
            return $config['homeurl'] . "/images/" . $image['name'];
        }, $images);

        $product_category = $product['category'];
        $attributes = $mysqli->query("SELECT id, attribute_name FROM attributes WHERE category = '$product_category'")->fetch_all(MYSQLI_ASSOC);
        foreach ($attributes as $key => $attribute) {
            $attribute_id = $attribute['id'];
            $attribute_value = $mysqli->query("SELECT value_name FROM attributes_values WHERE id IN
                    (SELECT attribute FROM attr_prod_relation WHERE product = $product_id AND attribute = $attribute_id)
                ")->fetch_assoc();
            $attributes[$key]['value'] = $attribute_value['value_name'] ?? "-";
        }
        $product['attributes'] = $attributes;

        if ($product) {
            echo json_encode([
                'success' => true,
                'data' => $product,
            ], JSON_UNESCAPED_UNICODE);
        }

        exit();
    }

    if (isset($values_from_post_json['service']))
        if ($values_from_post_json['service'] == 'delete_product') {
            $productId = $values_from_post_json['productId'];
            $qs = "UPDATE products SET is_active = 0 WHERE id = $productId";
            $result = $mysqli->query($qs);
            echo json_encode([
                "delete_product" => $result,
            ], JSON_UNESCAPED_UNICODE);
        }


    if ($values_from_post_json['service'] == 'get-stock-statuses') {
        $qs = "SELECT * FROM stock_statuses";
        try {
            $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
        } catch (\Throwable $th) {
            echo json_encode([
                'success' => false,
                'error' => $th->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            die();
        }
        if (!count($result)) {
            echo json_encode([
                "success" => false,
                "error" => "Статусы наличия не заданы"
            ], JSON_UNESCAPED_UNICODE);
        }
        echo json_encode([
            "success" => true,
            "data" => $result
        ], JSON_UNESCAPED_UNICODE);
    }


    if ($values_from_post_json['service'] == 'hints') {
        require_once __DIR__ . "/api/modules/smart_search.php";
        $text = $values_from_post_json['text'];
        if (!isset($values_from_post_json['text'])) {
            echo json_encode([
                "success" => false,
                "error" => "Нет входящей строки"
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        echo json_encode(
            smart_search($text),
            JSON_UNESCAPED_UNICODE
        );
        exit();
    }

    if ($values_from_post_json['service'] == 'get-categories') {
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
    }

    if ($values_from_post_json['service'] == 'create-attribute') {
        if (!isset($values_from_post_json['attribute_name'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не задано название атрибута',
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        if (!isset($values_from_post_json['category'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не задана категория товаров',
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $category = $values_from_post_json['category'];
        $attribute_name = $values_from_post_json['attribute_name'];

        if ($mysqli->query("SELECT * from attributes WHERE category='$category' AND attribute_name='$attribute_name'")->num_rows) {
            echo json_encode([
                'success' => false,
                'error' => 'Такой атрибут уже существует',
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $params = [
            'category' => $category,
            'attribute_name' => $attribute_name,
        ];
        $cols = implode(",", array_keys($params));
        $values = implode(",", array_map(function ($value) {
            return "'$value'";
        }, array_values($params)));


        try {
            $qs = "INSERT INTO attributes ($cols) VALUES ($values)";
            $mysqli->query($qs);
            $new_attribute = $mysqli->insert_id;
        } catch (\Throwable $th) {
            echo json_encode([
                'success' => false,
                'error' => "Возможно не всё заполнили " . $th->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        if ($new_attribute) {
            echo json_encode([
                'success' => true,
                'data' => $new_attribute
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    if ($values_from_post_json['service'] == 'create-attribute_value') {
        if (!isset($values_from_post_json['attribute'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не задан атрибут',
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        if (!isset($values_from_post_json['attribute_value'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не задано значение атрибута',
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $attribute = $values_from_post_json['attribute'];
        $value_name = $values_from_post_json['attribute_value'];

        if ($mysqli->query("SELECT * from attributes_values WHERE attribute='$attribute' AND value_name='$value_name'")->num_rows) { // TODO value_name заменить на value
            echo json_encode([
                'success' => false,
                'error' => 'Такой атрибут уже существует',
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $params = [
            'attribute' => $attribute,
            'value_name' => $value_name,
        ];
        $cols = implode(",", array_keys($params));
        $values = implode(",", array_map(function ($value) {
            return "'$value'";
        }, array_values($params)));


        try {
            $qs = "INSERT INTO attributes_values ($cols) VALUES ($values)";
            $mysqli->query($qs);
            $new_attribute_value = $mysqli->insert_id;
        } catch (\Throwable $th) {
            echo json_encode([
                'success' => false,
                'error' => "Возможно не всё заполнили " . $th->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        if ($new_attribute_value) {
            echo json_encode([
                'success' => true,
                'data' => $new_attribute_value
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    if ($values_from_post_json['service'] == 'get-attributes') {

        try {
            $qs = "SELECT * from attributes";
            $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
        } catch (\Throwable $th) {
            echo json_encode([
                'success' => false,
                'error' => "Что-то пошло не так [get-attributes]" . $th->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        array_walk($result, function (&$attribute) {
            global $mysqli;
            $attribute_id = $attribute['id'];
            $attribute['values'] = $mysqli->query("SELECT * from attributes_values WHERE attribute = $attribute_id")->fetch_all(MYSQLI_ASSOC);
        });

        if (count($result)) {
            echo json_encode([
                'success' => true,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'error' => "Атрибуты не созданы"
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    if ($values_from_post_json['service'] == 'get-filter-attributes') {
        // SELECT * FROM attributes WHERE category IN (SELECT id FROM categories WHERE category_name = 'электросамокаты');
        $category = $values_from_post_json['category'];
        try {
            $qs = "SELECT * from attributes WHERE view_in_filter=1 AND category IN (SELECT id FROM categories WHERE category_name = '$category')";
            $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
        } catch (\Throwable $th) {
            echo json_encode([
                'success' => false,
                'error' => "Что-то пошло не так [get-filter-attributes]" . $th->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        array_walk($result, function (&$attribute) {
            global $mysqli;
            $attribute_id = $attribute['id'];
            $attribute['values'] = $mysqli->query("SELECT * from attributes_values WHERE attribute = $attribute_id")->fetch_all(MYSQLI_ASSOC);
        });

        if (count($result)) {
            echo json_encode([
                'success' => true,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'error' => "Атрибуты не созданы"
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

if (isset($uri[1]) && $uri[1] == 'api') {
    if ((isset($uri[2]) && $uri[2] == 'create-product')) {
        $product_name = $_POST['product_name'];

        if ($mysqli->query("SELECT * from products WHERE product_name='$product_name'")->num_rows) {
            echo json_encode([
                'success' => false,
                'error' => 'Товар с таким названием уже существует',
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $price = $_POST['price'];
        $category = $_POST['category'];
        $description = $_POST['description'];
        $attributes = json_decode($_POST['attributes']);
        $stock_status = $_POST['stock_status'];
        $files = $_FILES;

        $params = [
            'product_name' => $product_name,
            'price' => $price,
            'description' => $description,
            'category' => $category,
            'stock_status' => $stock_status,
        ];

        try {
            $cols = implode(",", array_keys($params));
            $values = implode(",", array_map(function ($value) {
                return "'$value'";
            }, array_values($params)));
            $qs = "INSERT INTO products ($cols) VALUES ($values)";
            $mysqli->query($qs);
            $new_product_id = $mysqli->insert_id;
        } catch (\Throwable $th) {
            echo json_encode([
                'success' => false,
                'error' => "Возможно не всё заполнили " . $th->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        foreach ($files as $not_named_variable_name => $file) {

            $fileName = basename($file['name']);
            if ($mysqli->query("SELECT * from media WHERE name='$fileName'")->num_rows) { //проверка на наименование файла в бд
                echo json_encode([
                    'success' => false,
                    'error' => "Товар создан, но файл с именем '$fileName' не удалось сохранить, т.к. он уже существует",
                ], JSON_UNESCAPED_UNICODE);
                exit();
            } else {

                $uploadfile = $config['uploaddir'] . '/' . basename($file['name']);
                if (move_uploaded_file($file['tmp_name'], $uploadfile)) {

                    $qs = "INSERT INTO media (type,name,essense_id) VALUES ('product_image','$fileName','$new_product_id')";
                    $result = $mysqli->query($qs);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => "Не удалось сохранить '$fileName'. Пожалуйста обратитесь в службу поддержки",
                    ], JSON_UNESCAPED_UNICODE);
                }
            }
        }

        foreach ($attributes as $attribute) {
            $params = [
                'attribute' => $attribute->attribute,
                'attribute_value' => $attribute->value_name,
                'product' => $new_product_id,
            ];
            $cols = implode(",", array_keys($params));
            $values = implode(",", array_map(function ($value) {
                return "'$value'";
            }, array_values($params)));
            $qs = "INSERT INTO attr_prod_relation ($cols) VALUES ($values)";
            try {
                $mysqli->query($qs);
                $new_category_id = $mysqli->insert_id;
            } catch (\Throwable $th) {
                echo json_encode([
                    'success' => false,
                    'error' => "Товар создан, но есть проблемы с атрибутами " . $th->getMessage(),
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }

        echo json_encode([
            'success' => true,
            'product' => [
                'new_product_id' => $new_product_id,
            ],
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    if ((isset($uri[2]) && $uri[2] == 'last-products')) {

        $total = $mysqli->query("SELECT COUNT(*) as total from products WHERE is_active=1")->fetch_assoc()['total'];

        $page = $values_from_post_json['page'] - 1;
        $per_page = $values_from_post_json['per_page'];
        $pages = ceil($total / $per_page);

        $offset = $page * $per_page;

        $qs = "SELECT * from products ORDER BY id DESC LIMIT $offset, $per_page";

        $products = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);

        array_walk($products, function (&$product) {
            global $mysqli;
            $product_category = $product['category'];
            $product_id = $product['id'];
            $attributes = $mysqli->query("SELECT id, attribute_name FROM attributes WHERE category = '$product_category'")->fetch_all(MYSQLI_ASSOC);
            foreach ($attributes as $key => $attribute) {
                $attribute_id = $attribute['id'];
                $attribute_value = $mysqli->query("SELECT value_name FROM attributes_values WHERE id IN
                    (SELECT attribute FROM attr_prod_relation WHERE product = $product_id AND attribute = $attribute_id)
                ")->fetch_assoc();
                $attributes[$key]['value'] = $attribute_value['value_name'] ?? "-";
            }
            $product['attributes'] = ($attributes);

            $images = $mysqli->query("SELECT name FROM `media` WHERE essense_id = $product_id AND type='product_image'")->fetch_all(MYSQLI_ASSOC);
            $product['images'] = array_map(function ($image) {
                global $config;
                return $config['homeurl'] . "/images/" . $image['name'];
            }, $images);

            $product_status_id = $product['stock_status'];
            $stock_status = $mysqli->query("SELECT * FROM stock_statuses WHERE id = $product_status_id")->fetch_assoc();
            $product['stock_status'] = $stock_status['status_name'];
        });

        echo json_encode(
            [
                'success' => true,
                'data' => [
                    'total' => $total,
                    // 'qs' => $qs,
                    'products' => $products,
                    'pages' => $pages,
                    // 'config' => $config,
                    // '$values_from_post_json' => $values_from_post_json,
                ]
            ],
            JSON_UNESCAPED_UNICODE
        );
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'create-category')) {
        require __DIR__ . "/api/modules/create-category/create-category.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'get-products')) {
        require __DIR__ . "/api/modules/get_products.php";
    }
    if ((isset($uri[2]) && $uri[2] == 'get-admin-products')) {
        require __DIR__ . "/api/modules/get_admin_products.php";
    }
    if ((isset($uri[2]) && $uri[2] == 'login')) {
        $login = $values_from_post_json['login'];
        $password = $values_from_post_json['password'];
        $qs = "SELECT * from users WHERE username='$login' AND password = '$password' ";
        $user = $mysqli->query("SELECT * from users WHERE username='$login' AND password = '$password' ")->fetch_assoc();
        if (!$user) {
            echo json_encode([
                'success' => false,
                'error' => 'Ошибка входа'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $userId = $user['id'];
        $key = 'шышл мышл';
        $payload = [
            'username' => $user['username'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'useragent' => $_SERVER['HTTP_USER_AGENT'],
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        $mysqli->query("UPDATE users SET token = '$jwt' WHERE id = $userId");
        header("sid: $jwt");
        echo json_encode([
            'success' => true,
            'data' => $user,
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'who-iam')) {
        if (!isset($values_from_post_json['sid'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не введен sid!'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $sid = $values_from_post_json['sid'];
        $user = $mysqli->query("SELECT * from users WHERE token='$sid'")->fetch_assoc();
        if ($user) {
            echo json_encode([
                'success' => true,
                'data' => $user,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'error' => "Отказано в доступе",
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    if ((isset($uri[2]) && $uri[2] == 'admin-data-for-edit-product')) {
        require __DIR__ . "/api/modules/admin_data_for_edit_product/admin_data_for_edit_product.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'edit-product')) {
        require __DIR__ . "/api/modules/edit_product/edit_product.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'get-all-admin-attributes')) {
        require __DIR__ . "/api/modules/admin-attributes/get-all-admin-attributes/get-all-admin-attributes.php";
        exit();
    }

    if ((isset($uri[2]) && $uri[2] == 'create-attribute')) {
        require __DIR__ . "/api/modules/admin-attributes/create-attribute.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'delete-attribute')) {
        require __DIR__ . "/api/modules/admin-attributes/delete-attribute.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'create-attribute-value')) {
        require __DIR__ . "/api/modules/admin-attributes/create-attribute-value.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'delete-attribute-value')) {
        require __DIR__ . "/api/modules/admin-attributes/delete-attribute-value.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'get-category-attributes')) {
        require __DIR__ . "/api/modules/admin-get-category-attributes/admin-get-category-attributes.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'get-admin-categories')) {
        require __DIR__ . "/api/modules/admin-get-categories/admin-get-categories.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'delete-category')) {
        require __DIR__ . "/api/modules/delete-category.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'admin-data-for-edit-category')) {
        require __DIR__ . "/api/modules/admin_data_for_edit_category/admin_data_for_edit_category.php";
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'get-attributes-to-category-filter')) {
        require __DIR__ . "/api/modules/get_attributes_to_category_filter/get_attributes_to_category_filter.php";
        exit();
    }
}
