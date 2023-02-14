<?php
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: token, Content-Type');


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


$now = new DateTime('now', new DateTimeZone('+1000'));
$date = $now->format('Y-m-d H:i:s');
header('Content-type: text/javascript');

$_POST = json_decode(file_get_contents('php://input'), true);

$new_token_limit = strtotime('now') + (5 * 60);

include "modules/queryFN.php";

include "api_1.php";



if ($_GET["service"] === "mail_report") {
    include 'modules/mail_report.php';
}
if ($_GET["service"] === "update_products") {
    include 'modules/update_products.php';
}

$errors = array();

function updateStock($shop_id, $product_id, $count)
{
    $length = count(mysqli_fetch_all(queryFN("SELECT * FROM birm_stock WHERE `product_id` = '$product_id' AND `shop_id`= '$shop_id'"), MYSQLI_ASSOC));
    $id = $product_id . '_' . $shop_id;
    $query = $length > 0 ?
        "UPDATE birm_stock SET `count` = '$count' WHERE `product_id` = '$product_id' AND `shop_id`= '$shop_id'" :
        "INSERT INTO `birm_stock` (`id`, `product_id`, `shop_id`, `count`) VALUES ('$id','$product_id','$shop_id','$count')";
    $success = queryFN($query);
    return array(
        'success' => $success,
        'length' => $length,
    );
}

function api()
{
    if ($_GET["service"] != "sign_in") {

        $token = isset(getallheaders()['token']) ? getallheaders()['token'] : null;
        $nows = strtotime('now');

        $tl_1 = queryFN(
            "SELECT `token_limit` FROM `birm_users` WHERE token = '$token'"
        );
        $token_limit = isset($tl_1->fetch_assoc()["token_limit"]) ? $tl_1->fetch_assoc()["token_limit"] : null;
        $limit_time = $token_limit;
        $isSoon = $limit_time > $nows;

        if (!$isSoon) {
            //http_response_code(401);
            //return null;
        } else {
            queryFN("UPDATE `birm_users` SET `token_limit` = $new_token_limit WHERE token = '$token'");
        }
        $user_1 = mysqli_fetch_all(queryFN("SELECT id, name, email, role, shop_id  FROM `birm_users` WHERE token = '$token'"), MYSQLI_ASSOC);
        $user = isset($user_1[0]) ? $user_1[0] : null;
    }
    global $new_token_limit;
    global $errors;


    function clear()
    {
        $uniqs = array();
        $repeats = array();
        $rows = mysqli_fetch_all(queryFN("SELECT *  FROM `birm_stock`"), MYSQLI_ASSOC);
        foreach ($rows as $key => $row_value) {
            $product_id = $row_value['product_id'];
            $shop_id = $row_value['shop_id'];
            if (count($uniqs) == 0) {
                $uniqs[] = $row_value;
            } else {
                $isExistss = false;
                foreach ($uniqs as $keya => $uniq_value) {
                    $product_id2 = $uniq_value['product_id'];
                    $shop_id2 = $uniq_value['shop_id'];
                    if ($product_id == $product_id2 && $shop_id == $shop_id2) {
                        $isExistss = true;
                    };
                }
                if ($isExistss) {
                    $repeats[] = $row_value;
                } else {
                    $uniqs[] = $row_value;
                }
            }
        }
        $todel = array_column($repeats, 'id');
        $succarr = array();
        foreach ($todel as $key => $value) {
            $succ = queryFN("DELETE FROM birm_stock WHERE `id`='$value'");
            $succarr[] = $succ;
        }
        $data = array(
            "query" => "SELECT * FROM `birm_stock`",
            "data" => mysqli_fetch_all(queryFN("SELECT *  FROM `birm_products`"), MYSQLI_ASSOC),
            '$uniqs[]' => $uniqs,
            '$repeats[]' => $repeats,
            '$todel' => $todel,
            '$succarr' => $succarr,
        );
        // echo (json_encode($data));
    }
    // clear();
    // return null;

    if ($_GET["service"] === "sign_in") {


        $email = $_POST['login'];
        $password = $_POST['password'];


        $token = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 25);
        $token_limit = $new_token_limit;

        $user = mysqli_fetch_all(queryFN("SELECT id, name, email, role, shop_id  FROM `birm_users` WHERE email='$email' AND password='$password' AND `active`='1'"), MYSQLI_ASSOC);
        // echo "SELECT id, name, email, role, shop_id  FROM `birm_users` WHERE email='$email' AND password='$password' AND `active`='1'";
        //return null;


        if ($user) {



            $user_id = $user[0]["id"];

            $_SESSION['user'] = $user;

            queryFN("UPDATE birm_users SET token = '$token', `token_limit` = $token_limit WHERE id=$user_id");



            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From:  ' . 'CRM МотоХит' . ' <' . 'info@crm.xn--27-vlcpka1acz.xn--p1ai' . '>' . " \r\n" .
                'Reply-To: ' . 'info@crm.xn--27-vlcpka1acz.xn--p1ai' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

            // $message = wordwrap($message, 70, "\r\n");

            // echo 123123; return ;
            // mail('bir-moto@mail.ru', "Кто-то вошел в CRM", ($user[0]['name']), $headers);
            //mail('bir-moto@mail.ru', "Кто-то вошел в CRM", json_encode( $user[0] ), $headers);



            echo (json_encode(array(
                "success" => true,
                "token" => $token,
                "user" => $user[0],
                // 'query' => "UPDATE birm_users SET token = '$token', `token_limit` = $token_limit WHERE id=$user_id",
                //'$token_limit' => $token_limit,
                '$user_id' => $user_id,
            )));
        }
    } else {
    };

    if ($_GET["service"] === "report") {

        include 'modules/create-year-report.php';
        get_report(queryFN, $user);
    };

    if ($_GET["service"] === "php_stock_sender") {
        include 'modules/php_stock_sender.php';
        php_stock_sender(queryFN);
    }

    if ($_GET["service"] === "get_categories") {
        echo json_encode(array(
            "categories" => mysqli_fetch_all(queryFN("SELECT *  FROM `birm_categories`"), MYSQLI_ASSOC),
            "user" => $user
        ));
    };

    // if ($_GET["service"] === "get_products") {
    //     include "modules/get_products.php";
    // };

    if ($_GET["service"] === "add_sale") {
        include "modules/add_sale.php";
    };
    if ($_GET["service"] === "get_staff") {
        $output = array(
            "staff" => mysqli_fetch_all(queryFN("SELECT `id`,`name`, `role`, `shop_id`, `email`  FROM `birm_users` WHERE `active`='1'"), MYSQLI_ASSOC),
            "user" => $user
        );
        echo json_encode($output);
    };

    if ($_GET["service"] === "get_roles") {

        echo json_encode(array(
            "roles" => mysqli_fetch_all(queryFN("SELECT *  FROM `birm_roles`"), MYSQLI_ASSOC),
            "user" => $user
        ));
    };

    if ($_GET["service"] === "get_shops") {

        $shops = mysqli_fetch_all(queryFN("SELECT *  FROM `birm_shops`"), MYSQLI_ASSOC);

        $output = array(
            "shops" => $shops,
            "user" => $user
        );

        echo json_encode($output);
    };

    if ($_GET["service"] === "add_products") {
        include "modules/add_product.php";
    };

    if ($_GET["service"] === "add_category") {
        $array = array();

        $name = $_POST["name"];
        $id = $_POST["id"];

        $query = "INSERT INTO `birm_categories` (`name`,`id`) VALUES ('$name','$id')";

        if (!(isset($name) && isset($id))) {
            $errors[] = "Name and id are required";
        }

        if (queryFN($query)) {
        } else {
            $errors[] = "Something wrong";
        };

        if (count($errors) > 0) {
            $success = false;
        } else {
            $success = true;
        }

        $output = array(
            "query" => $query,
            "name" => $name,
            // "array" => $array,
            "success" => $success,
            "errors" => $errors,
            "user" => $user
        );

        echo json_encode($output);
    };

    if ($_GET["service"] === "add_shop") {

        //$id = queryFN("SELECT MAX( id ) AS max FROM `birm_products`")->fetch_assoc()["max"]+1;
        $array = array(
            //    "cells" => array("`id`"),
            //    "values" => array($id)
        );

        foreach ($_POST as $key => $value) {
            $array["cells"][] = is_string($key) ? "`$key`" : $key;
            $array["values"][] = is_string($value) ? "'$value'" : $value;
        }

        $cells = implode(",", $array["cells"]);
        $values = implode(",", $array["values"]);
        $query = "INSERT INTO `birm_shops` ($cells) VALUES ($values)";
        $success = queryFN($query);

        $output = array(
            // "query" => $query,
            "array" => $array,
            "success" => $success,
            "user" => $user
        );
        echo json_encode($output);
    };

    if ($_GET["service"] === "add_user") {
        include "modules/add_user.php";
    };

    if ($_GET["service"] === "edit_product") {
        include "modules/edit_product.php";
    };

    function get_category_name($id, $array)
    {
        foreach ($array as $key => $cat_data) {
            if ($cat_data['name_en'] === $id) {
                $name = $cat_data['name_ru'];
            }
        }
        return $name;
    }

    if ($_GET["service"] === "get_sum_in_products") {
        include "modules/get_sum_in_products.php";
    }

    // include 'modules/check_prduct_is_exist.php';

    if ($_GET["service"] === "send_to_archive") {
        include 'modules/send_to_archive.php';
    }

    if ($_GET["service"] === "get_archive_products") {
        include 'modules/get_archive_products.php';
    }

    if ($_GET["service"] === "remove_from_archive") {
        include 'modules/remove_from_archive.php';
    }
    if ($_GET["service"] === "edit_staff") {
        include 'modules/edit_staff.php';
    }
    if ($_GET["service"] === "fire_an_employee") {
        include 'modules/fire_an_employee.php';
    }
    if ($_GET["service"] === "update_sheets") {
        include 'modules/update_sheets.php';
    }

    if ($_GET["service"] === "delete_product") {
        include 'modules/delete_product.php';
    }
    if ($_GET["service"] === "get_table_db") {
        include 'modules/get_table_db.php';
        // echo (json_encode(
        //     get_table_db($_GET['table_id'])
        // ));
    }
    if ($_GET["service"] === "delete_row") {
        include 'modules/delete_row.php';
        echo (json_encode(array(
            'success' => delete_row($_POST['bname'], $_POST['row_conditions']),
            "user" => $user
        )));
    }
}
api();
