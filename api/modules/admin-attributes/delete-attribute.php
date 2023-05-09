<?php

$attribute = $values_from_post_json['attribute'];

$delete_relations_from_products_result = $mysqli->query("DELETE FROM attr_prod_relation WHERE attribute = $attribute");
if (!$delete_relations_from_products_result) {
    echo json_encode([
        'success' => false,
        'error' => "Error delete from attr_prod_relation"
    ]);
    exit();
}

$delete_atribute_values_result = $mysqli->query("DELETE FROM attributes_values WHERE attribute = $attribute");
if (!$delete_atribute_values_result) {
    echo json_encode([
        'success' => false,
        'error' => "Error delete from attributes_values"
    ]);
    exit();
}

$delete_atribute_result = $mysqli->query("DELETE FROM attributes WHERE id = $attribute");
if (!$delete_atribute_result) {
    echo json_encode([
        'success' => false,
        'error' => "Error delete from attributes"
    ]);
    exit();
}




echo json_encode([
    'success' => true,
    'data' => [
        'delete_relations_from_products_result' => $delete_relations_from_products_result,
        'delete_atribute_values_result' => $delete_atribute_values_result,
        'delete_atribute_result' => $delete_atribute_result,
    ]
]);
