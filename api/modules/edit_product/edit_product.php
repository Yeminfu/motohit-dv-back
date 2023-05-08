<?php

$product_id = $_POST['product_id'];

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
