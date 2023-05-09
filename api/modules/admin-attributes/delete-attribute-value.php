<?php

$attribute_value = $values_from_post_json['attribute_value'];

$delete_relations_from_products_result = $mysqli->query("DELETE FROM attr_prod_relation WHERE attribute_value = $attribute_value");
if (!$delete_relations_from_products_result) {
    echo json_encode([
        'success' => false,
        'error' => "Error delete from attr_prod_relation"
    ]);
    exit();
}

$delete_atribute_values_result = $mysqli->query("DELETE FROM attributes_values WHERE id = $attribute_value");
if (!$delete_atribute_values_result) {
    echo json_encode([
        'success' => false,
        'error' => "Error delete from attributes_values"
    ]);
    exit();
}

echo json_encode([
    'success' => true,
]);
