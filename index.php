<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 'On');

header('Content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Credentials: true');


$config = [
    'per_page_top_products' => 10
];

$mysqli = new mysqli("localhost", "admin", "xKF2eA", "china_kol");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$autoload = __DIR__ . '/vendor/autoload.php';

require $autoload;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Error\DebugFlag;


require __DIR__ . "/api/modules/products/get_products";
require "/var/www/html/china-back/api/modules/products/objectTypes.php";

try {
    $schema = new Schema(
        (new SchemaConfig())
            ->setQuery($queryType)
    );

    $rawInput = file_get_contents('php://input');
    if ($rawInput === false) {
        throw new RuntimeException('Failed to get php://input');
    }

    $input = json_decode($rawInput, true);
    $query = $input['query'];
    $variableValues = $input['variables'] ?? null;

    $rootValue = ['prefix' => 'You said: '];
    $result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues);
    $output = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);
} catch (Throwable $e) {
    $output = [
        'error' => [
            'message' => $e->getMessage(),
        ],
    ];
}

echo json_encode($output);

/*
    CREATE TABLE transactions
        (
            id int NOT NULL AUTO_INCREMENT,
            sum int NOT NULL,
            is_incoming boolean NOT NULL,
            comment varchar(500) NOT NULL,
            -- FirstName varchar(255), Address varchar(255), City varchar(255)
            PRIMARY KEY (id)
        )

    INSERT INTO `transactions` (sum, is_incoming, comment) VALUES (100, 1, 'Заказ 123456. ...')
    
*/
