<?php

$product_id = $_POST['product_id'];


if (isset($_POST['downloadedImages'])) {
    $downloadedImages = $_POST['downloadedImages'];

    $imagesInDB =  array_column(
        $mysqli->query("SELECT id FROM products_media WHERE product_id = $product_id")->fetch_all(MYSQLI_ASSOC),
        'id'
    );

    $diff = array_diff(
        $imagesInDB,
        $downloadedImages,
    );
 
    foreach ($diff as $key => $image_id) {
        $image_name = $mysqli->query("SELECT name FROM products_media WHERE id = $image_id")->fetch_assoc();
        $deleteRes = $mysqli->query("DELETE FROM products_media WHERE id = $image_id");
        $path_to_file = $config['uploaddir'] . "/" . $image_name['name'];
    }
}

foreach ($_FILES as $not_named_variable_name => $file) {
    $fileName = basename($file['name']);
    if ($mysqli->query("SELECT * from products_media WHERE name='$fileName'")->num_rows) { //проверка на наименование файла в бд
        echo json_encode([
            'success' => false,
            'error' => "Товар создан, но файл с именем '$fileName' не удалось сохранить, т.к. он уже существует",
        ], JSON_UNESCAPED_UNICODE);
        exit();
    } else {
        $uploadfile = $config['uploaddir'] . "/" . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $uploadfile)) {

            $qs = "INSERT INTO products_media (type,name,product_id) VALUES ('image_full','$fileName','$product_id')";
            $result = $mysqli->query($qs);
        } else {
            echo json_encode([
                'success' => false,
                'error' => "Не удалось сохранить '$fileName'. Пожалуйста обратитесь в службу поддержки",
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

if (isset($_POST['attributes'])) {
    foreach ($_POST['attributes'] as $attribute_id => $attribute_value_id) {
        $qs = "SELECT * FROM attr_prod_relation WHERE attribute = $attribute_id AND attribute_value = $attribute_value_id AND product = $product_id";
        $res = $mysqli->query($qs)->fetch_assoc();
        if ($res) {
            $mysqli->query("UPDATE attr_prod_relation SET attribute_value = $attribute_value_id WHERE id = " . $res['id']);
        } else {
            $mysqli->query("INSERT INTO attr_prod_relation (attribute, attribute_value, product) VALUES ($attribute_id, $attribute_value_id, $product_id)");
        }
    }
}

echo json_encode([
    'success' => true,
]);
