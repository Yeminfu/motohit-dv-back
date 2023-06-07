<?php


$categories = $mysqli->query("SELECT id, category_name, parent from categories WHERE is_active")->fetch_all(MYSQLI_ASSOC);

$parents = array_map(function ($c) {
    return $c['parent'];
}, $categories);

$categoriesWithoutChildren = array_filter(
    $categories,
    function ($category) use ($parents) {
        return !in_array(
            $category['id'],
            $parents
        );
    }
);

array_walk(
    $categoriesWithoutChildren,
    function (&$category) use ($mysqli, $perPage) {
        $category_id = $category['id'];
        $products = $mysqli->query("SELECT COUNT(*) as count from products WHERE category = $category_id")->fetch_assoc()['count'];
        $category['pages'] = ceil($products / $perPage);
    }
);

$firstCategory_id = array_shift($categoriesWithoutChildren)['id'];
$qs = "SELECT id,product_name,slug, price from products WHERE category = $firstCategory_id LIMIT $perPage ";
$products = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);


array_walk($products, function (&$product) use ($config) {
    global $mysqli;
    $product_id = $product['id'];
    $image = $mysqli->query("SELECT name FROM `media` WHERE essense_id = $product_id AND type='product_image'")->fetch_assoc();
    $product['image'] =  isset($image['name']) ? $config['homeurl'] . "/images/" . $image['name'] : null;
});

echo json_encode([
    'categories' => $categoriesWithoutChildren,
    'products' => $products,
]);
