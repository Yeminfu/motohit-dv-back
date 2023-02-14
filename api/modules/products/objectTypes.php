<?php

$autoload = '/var/www/html/china-back/api/vendor/autoload.php';

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;


$photoType = new ObjectType([
    'name' => 'photoType',
    'fields' => [
        'id' => ['type' => Type::int()],
        'product' => ['type' => Type::int()],
        'data' => ['type' => Type::string()],
    ]
]);

$productType = new ObjectType([
    'name' => 'ProductType',
    'fields' => [
        'id' => ['type' => Type::int()],
        'name' => ['type' => Type::string()],
        'slug' => ['type' => Type::string()],
        'creation_date' => ['type' => Type::string()],
        'views' => ['type' => Type::int()],
        'photo' => Type::listOf($photoType),
    ]
]);
$top_products = new ObjectType([
    'name' => 'top_products',
    'fields' => [
        'products' => Type::listOf($productType),
        'page' => Type::int(),
        'per_page' => Type::int(),
        'pages_total' => Type::int(),
        'products_total' => Type::int(),
    ]
]);

$queryType = new ObjectType([
    'name' => 'Query',
    'fields' => [
        'top_products' => [
            'type' => $top_products,
            'args' => [
                'page' => [
                    'type' => Type::int(),
                    'description' => 'Limit the number of recent likes returned',
                    'defaultValue' => 1
                ],
                'per_page' => [
                    'type' => Type::int(),
                    'description' => 'Limit the number of recent likes returned',
                    'defaultValue' => $config['per_page_top_products']
                ],
            ],
            'resolve' => function ($rootValue, $args) {
                global $mysqli;
                global $config;
                $per_page = $args['per_page'];
                $page = $args['page'];
                $offset = $args['page'] * $per_page - 1;

                $total = $mysqli->query(
                    "SELECT COUNT(*) as total FROM chk_products"
                )->fetch_assoc();



                $products = mysqli_fetch_all($mysqli->query(
                    "SELECT * FROM `chk_products` ORDER BY views DESCLIMIT $offset, $per_page"
                ), MYSQLI_ASSOC);

                array_walk($products, function (&$product) {
                    global $mysqli;
                    $product_id = $product['id'];
                    $photo = mysqli_fetch_all($mysqli->query("SELECT * FROM `chk_products_meta`  WHERE product=$product_id AND prefix ='photo' "), MYSQLI_ASSOC);
                    $product['photo'] = $photo;
                });


                return [
                    'products' => $products,
                    'products_total' => $total['total'],
                    'page' => $page,
                    'per_page' => $per_page,
                    'pages_total' =>ceil( $total['total'] / $per_page),
                ];
            }
        ],
    ],
]);
