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

    $regexps = array_map(
        function ($item) {
            $implodes = implode(
                "|",
                $item
            );
            return "product_name REGEXP '$implodes'";
        },
        $words_with_livenstein
    );

    $imploded_regexps = implode(" AND ", $regexps);

    $qs = "SELECT * from products WHERE $imploded_regexps";

    return  $qs;
}

function livenstein($string)
{
    $words = [];
    for ($i = 0; $i <  strlen($string); $i++) {
        $words[] = mb_substr($string, 0, $i) . "." . mb_substr($string, $i);
        $words[] = mb_substr($string, 0, $i) . mb_substr($string, $i + 1);
        $words[] = mb_substr($string, 0, $i) . "." . mb_substr($string, $i + 1);
    }
    $words[] = $string . ".";
    return $words;
}
