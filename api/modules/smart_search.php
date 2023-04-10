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
    return gettype($string) != "string";
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
