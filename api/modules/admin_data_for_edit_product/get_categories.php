<?php

function get_categories()
{
    global $mysqli;
    try {
        $qs = "SELECT * from categories WHERE is_active = 1";
        $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);
    } catch (\Throwable $th) {
        echo json_encode([
            'success' => false,
            'error' => "Что-то пошло не так " . $th->getMessage()
        ]);
        exit();
    }
    return $result;
}
