<?php


$category_name = $_POST['category_name'];

// if ($mysqli->query("SELECT * from categories WHERE category_name='$category_name'")->num_rows) {
//     echo json_encode([
//         'success' => false,
//         'error' => 'Категория с таким названием уже существует',
//     ], JSON_UNESCAPED_UNICODE);
//     exit();
// }


$description = $_POST['description'];
// $characteristics = json_decode($_POST['characteristics']);
$files = $_FILES;

$parent = (isset($_POST['parent'])) ? ("'" . $_POST['parent'] . "'") : null;

$params = [
    'category_name' => $category_name . rand(0, 15),
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
    ], JSON_UNESCAPED_UNICODE);
    exit();
}




foreach ($_FILES as $not_named_variable_name => $file) {
    if (count($_FILES)) {
        $fileName = basename($file['name']);
        if ($mysqli->query("SELECT * from media WHERE name='$fileName'")->num_rows) { //проверка на наименование файла в бд
            echo json_encode([
                'success' => false,
                'error' => "Категория создана, но файл с именем '$fileName' не удалось сохранить, т.к. он уже существует",
            ], JSON_UNESCAPED_UNICODE);
            exit();
        } else {
            $uploadfile = $config['uploaddir'] . "/" . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $uploadfile)) {

                $qs = "INSERT INTO media (type,name,product_id) VALUES ('category_image','$fileName','$new_category_id')";
                $result = $mysqli->query($qs);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => "Не удалось сохранить '$fileName'. Пожалуйста обратитесь в службу поддержки",
                ], JSON_UNESCAPED_UNICODE);
            }
        }
    }
}



echo json_encode([
    'success' => true,
    'product' => [
        // 'name' => $product_name,
        'new_category_id' => $new_category_id,
        'file' => ([
            // $_POST['image'],
            count($_FILES),
            $_FILES,
        ]),
        // 'price' => $price,
        // 'description' => $description,
        // 'images' => $mysqli->query("SELECT * FROM media WHERE product_id = '$new_product_id'")->fetch_all(MYSQLI_ASSOC),
    ],
], JSON_UNESCAPED_UNICODE);
