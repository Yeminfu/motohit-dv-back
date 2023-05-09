<?php

function get_attributes($category)
{
    global $mysqli;
    try {
        $qs = "SELECT * from attributes WHERE category = $category";
        $attributes = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
    } catch (\Throwable $th) {
        echo json_encode([
            'success' => false,
            'error' => "Что-то пошло не так [get-attributes]" . $th->getMessage()
        ]);
        exit();
    }

    array_walk($attributes, function (&$attribute) {
        global $mysqli;
        $attribute_id = $attribute['id'];
        $attribute['values'] = $mysqli->query("SELECT * from attributes_values WHERE attribute = $attribute_id")->fetch_all(MYSQLI_ASSOC);
    });
    return $attributes;
}
