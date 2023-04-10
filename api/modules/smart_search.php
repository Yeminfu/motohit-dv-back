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
    $words_with_livenstein = array_map('livenstein', $words);

    return  $words_with_livenstein;
}

function livenstein($string)
{
    $words = [];
    for ($i = 0; $i <  strlen($string); $i++) {
        $words[] = wrap(substr($string, 0, $i) . "." . substr($string, $i));
        $words[] = wrap(substr($string, 0, $i) . substr($string, $i + 1));
        $words[] = wrap(substr($string, 0, $i) . "." . substr($string, $i + 1));
    }
    $words[] = $string . ".";
    return $words;
}

function wrap($string)
{
    return  mb_convert_encoding(
        $string,
        'UTF-8',
        'UTF-8'
    );
}
