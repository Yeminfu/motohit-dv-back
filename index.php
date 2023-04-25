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
header('Access-Control-Expose-Headers: SID');
// Access-Control-Expose-Headers: Access-Token, Uid

$json = file_get_contents('php://input');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

$config = [
    'per_page_top_products' => 10,
    "uploaddir" => __DIR__ . '/images/',
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


// echo json_encode([
//     'jwt' => $jwt,
//     '$decoded' => $decoded,
// ]);

// exit();
// sleep(1 / 2);
// time_nanosleep(0, 500000000);

$values_from_post_json = json_decode($json, true);

// sleep(1);

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

            $images = $mysqli->query("SELECT name FROM `products_media` WHERE product_id = $product_id AND type='image_full'")->fetch_all(MYSQLI_ASSOC);
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
        ]);
    }

    if ($values_from_post_json['service'] == 'get-product') {
        $product_name = $values_from_post_json['product'];

        $product = $mysqli->query("SELECT * FROM products WHERE product_name = '$product_name'")->fetch_assoc();
        $product_id = $product['id'];

        $images = $mysqli->query("SELECT name FROM `products_media` WHERE product_id = $product_id ")->fetch_all(MYSQLI_ASSOC);
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
            ]);
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
            ]);
        }


    if ($values_from_post_json['service'] == 'get-stock-statuses') {
        $qs = "SELECT * FROM stock_statuses";
        try {
            $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
        } catch (\Throwable $th) {
            echo json_encode([
                'success' => false,
                'error' => $th->getMessage()
            ]);
            die();
        }
        if (!count($result)) {
            echo json_encode([
                "success" => false,
                "error" => "Статусы наличия не заданы"
            ]);
        }
        echo json_encode([
            "success" => true,
            "data" => $result
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
                'success' => false,
                'error' => "Что-то пошло не так " . $th->getMessage()
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
            'success' => true,
            'data' => $result
        ]);
        exit();
    }

    if ($values_from_post_json['service'] == 'create-attribute') {
        if (!isset($values_from_post_json['attribute_name'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не задано название атрибута',
            ]);
            exit();
        }
        if (!isset($values_from_post_json['category'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не задана категория товаров',
            ]);
            exit();
        }

        $category = $values_from_post_json['category'];
        $attribute_name = $values_from_post_json['attribute_name'];

        if ($mysqli->query("SELECT * from attributes WHERE category='$category' AND attribute_name='$attribute_name'")->num_rows) {
            echo json_encode([
                'success' => false,
                'error' => 'Такой атрибут уже существует',
            ]);
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
            ]);
            exit();
        }

        if ($new_attribute) {
            echo json_encode([
                'success' => true,
                'data' => $new_attribute
            ]);
        }
    }
    if ($values_from_post_json['service'] == 'create-attribute_value') {
        if (!isset($values_from_post_json['attribute'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не задан атрибут',
            ]);
            exit();
        }
        if (!isset($values_from_post_json['attribute_value'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не задано значение атрибута',
            ]);
            exit();
        }

        $attribute = $values_from_post_json['attribute'];
        $value_name = $values_from_post_json['attribute_value'];

        if ($mysqli->query("SELECT * from attributes_values WHERE attribute='$attribute' AND value_name='$value_name'")->num_rows) { // TODO value_name заменить на value
            echo json_encode([
                'success' => false,
                'error' => 'Такой атрибут уже существует',
            ]);
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
            ]);
            exit();
        }

        if ($new_attribute_value) {
            echo json_encode([
                'success' => true,
                'data' => $new_attribute_value
            ]);
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
            ]);
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
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => "Атрибуты не созданы"
            ]);
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
            ]);
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
            ]);
            exit();
        }



        foreach ($files as $not_named_variable_name => $file) {

            $fileName = basename($file['name']);
            if ($mysqli->query("SELECT * from products_media WHERE name='$fileName'")->num_rows) { //проверка на наименование файла в бд
                echo json_encode([
                    'success' => false,
                    'error' => "Товар создан, но файл с именем '$fileName' не удалось сохранить, т.к. он уже существует",
                ]);
                exit();
            } else {

                $uploadfile = $config['uploaddir'] . basename($file['name']);
                if (move_uploaded_file($file['tmp_name'], $uploadfile)) {

                    $qs = "INSERT INTO products_media (type,name,product_id) VALUES ('image_full','$fileName','$new_product_id')";
                    $result = $mysqli->query($qs);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => "Не удалось сохранить '$fileName'. Пожалуйста обратитесь в службу поддержки",
                    ]);
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
                ]);
                exit();
            }
        }

        echo json_encode([
            'success' => true,
            'product' => [
                'new_product_id' => $new_product_id,
            ],
        ]);

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

            $images = $mysqli->query("SELECT name FROM `products_media` WHERE product_id = $product_id AND type='image_full'")->fetch_all(MYSQLI_ASSOC);
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
            ]
        );
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'create-category')) {
        $category_name = $_POST['category_name'];

        if ($mysqli->query("SELECT * from categories WHERE category_name='$category_name'")->num_rows) {
            echo json_encode([
                'success' => false,
                'error' => 'Категория с таким названием уже существует',
            ]);
            exit();
        }


        $description = $_POST['description'];
        // $characteristics = json_decode($_POST['characteristics']);
        $files = $_FILES;

        $parent = (isset($_POST['parent'])) ? ("'" . $_POST['parent'] . "'") : null;

        $params = [
            'category_name' => $category_name,
            'description' => $description,
        ];
        if (isset($_POST['parent'])) $params['parent'] = $_POST['parent'];

        $cols = implode(",", array_keys($params));
        $values = implode(",", array_map(function ($value) {
            return "'$value'";
        }, array_values($params)));

        $qs = "INSERT INTO categories ($cols) VALUES ($values)";

        try {
            $mysqli->query($qs);
            $new_category_id = $mysqli->insert_id;
        } catch (\Throwable $th) {
            echo json_encode([
                'success' => false,
                'error' => "Возможно не всё заполнили " . $th->getMessage(),
            ]);
            exit();
        }

        //TODO добавить в бд возможность создания картинок для категорий


        // foreach ($files as $file_name => $file) {
        //     if ($mysqli->query("SELECT * from products_media WHERE name='$file_name'")->num_rows) { //проверка на наименование файла в бд
        //         echo json_encode([
        //             'success' => false,
        //             'error' => "Файл с именем '$file_name' уже существует",
        //         ]);
        //         exit();
        //     } else {
        //         $uploaddir = __DIR__ . '/images/';
        //         $uploadfile = $uploaddir . basename($file['name']);
        //         if (move_uploaded_file($file['tmp_name'], $uploadfile)) {
        //             $qs = "INSERT INTO products_media (type,name,product_id) VALUES ('image_full','$file_name','$new_product_id')";
        //             $result = $mysqli->query($qs);
        //         } else {
        //             echo json_encode([
        //                 'success' => false,
        //                 'error' => "Не удалось сохранить '$file_name'. Пожалуйста обратитесь в службу поддержки",
        //             ]);
        //         }
        //     }
        // }

        echo json_encode([
            'success' => true,
            'product' => [
                // 'name' => $product_name,
                'new_category_id' => $new_category_id,
                // 'price' => $price,
                // 'description' => $description,
                // 'images' => $mysqli->query("SELECT * FROM products_media WHERE product_id = '$new_product_id'")->fetch_all(MYSQLI_ASSOC),
            ],
        ]);

        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'get-products')) {
        require __DIR__ . "/api/modules/get_products.php";
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
            ]);
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
            'data'=>$user,
        ]);
        exit();
    }
    if ((isset($uri[2]) && $uri[2] == 'who-iam')) {
        if (!isset($values_from_post_json['sid'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Не введен sid!'
            ]);
            exit();
        }
        $sid = $values_from_post_json['sid'];
        $user = $mysqli->query("SELECT * from users WHERE token='$sid'")->fetch_assoc();
        if ($user) {
            echo json_encode([
                'success' => true,
                'data' => $user,
            ]);
        }
    }
}
