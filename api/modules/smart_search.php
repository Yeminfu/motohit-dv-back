<?php


function smart_search($string)
{
    if (gettype($string) != "string") return [
        'success' => false,
        'error' => "Исходное значение должно быть строкой",
    ];
    if (strlen($string) < 3) return [
        'success' => false,
        'error' => "Исходное значение должно иметь минимум 3 символа",
    ];
    $words = explode(" ", $string);
    $words_with_livenstein = livenstein($words[0]);
    echo json_encode($words_with_livenstein);
    echo json_last_error_msg(); // Print out the error if any
    die(); // halt the script
    exit();
    return livenstein($words[0]);
    // return livenstein();
}

function livenstein($string)
{
    $words = [];
    for ($i = 0; $i <  strlen($string); $i++) {
        $words[] = substr($string, 0, $i) . "." . substr($string, $i);
        $words[] = substr($string, 0, $i) . substr($string, $i + 1);
        $words[] = substr($string, 0, $i) . "." . substr($string, $i + 1);
    }
    $words[] = $string . ".";
    return $words;
}
