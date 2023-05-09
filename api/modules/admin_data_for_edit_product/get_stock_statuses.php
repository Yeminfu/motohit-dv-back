<?php

function get_stock_statuses()
{
    global $mysqli;
    $qs = "SELECT * FROM stock_statuses";
    try {
        $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
    } catch (\Throwable $th) {
        echo json_encode([
            'success' => false,
            'error' => $th->getMessage()
        ]);
        die();
    }
    if (!count($result)) {
        echo json_encode([
            "success" => false,
            "error" => "Статусы наличия не заданы"
        ]);
    }
    return $result;
}
