<?php

$product_id = $_POST['product_id'];
$log = [];

if (isset($_POST['downloadedImages'])) {
    $downloadedImages = $_POST['downloadedImages'];

    $imagesInDB =  array_column(
        $mysqli->query("SELECT id FROM media WHERE essense_id = $product_id")->fetch_all(MYSQLI_ASSOC),
        'id'
    );

    $diff = array_diff(
        $imagesInDB,
        $downloadedImages,
    );

    foreach ($diff as $key => $image_id) {
        $image_name = $mysqli->query("SELECT name FROM media WHERE id = $image_id")->fetch_assoc();
        $deleteRes = $mysqli->query("DELETE FROM media WHERE id = $image_id");
        $path_to_file = $config['uploaddir'] . "/" . $image_name['name'];
    }
}


if (isset($_POST['attributes'])) {
    $log[] = ['Прислали атрибуты'];
    foreach ($_POST['attributes'] as $attribute_id => $attribute_value_id) {
        $qs = "SELECT * FROM attr_prod_relation WHERE attribute = $attribute_id AND product = $product_id";
        $log[] = ['заправшиваем наличие атрибута у товара', $qs];
        $res = $mysqli->query($qs)->fetch_assoc();

        if ($res) {
            $log[] = ['Такой атрибут у товара есть', $res];
            $qs_update_relation = "UPDATE attr_prod_relation SET attribute_value = $attribute_value_id WHERE id = " . $res['id'];
            $res2 = $mysqli->query($qs_update_relation);
            if ($res2) {
                $log[] = ['Апдейт удался', $qs_update_relation];
            } else {
                $log[] = 'Апдейт не удался';
            }
        } else {
            $log[] = ['Такого атрибута нет, создаем запись'];
            $mysqli->query("INSERT INTO attr_prod_relation (attribute, attribute_value, product) VALUES ($attribute_id, $attribute_value_id, $product_id)");
        }
    }
}

foreach ($_FILES as $not_named_variable_name => $file) {
    $fileName = basename($file['name']);
    if ($mysqli->query("SELECT * from media WHERE name='$fileName'")->num_rows) { //проверка на наименование файла в бд
        echo json_encode([
            'success' => false,
            'error' => "Товар создан, но файл с именем '$fileName' не удалось сохранить, т.к. он уже существует",
        ], JSON_UNESCAPED_UNICODE);
        exit();
    } else {
        $uploadfile = $config['uploaddir'] . "/" . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $uploadfile)) {

            $qs = "INSERT INTO media (type,name,essense_id) VALUES ('product_image','$fileName','$product_id')";
            $result = $mysqli->query($qs);
        } else {
            echo json_encode([
                'success' => false,
                'error' => "Не удалось сохранить '$fileName'. Пожалуйста обратитесь в службу поддержки",
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

echo json_encode([
    'success' => true,
    // 'data' => [
    //     'log' => $log,
    // ]
]);
