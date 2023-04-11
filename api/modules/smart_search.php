<?php
function smart_search($string)
{
    global $mysqli;
    if (gettype($string) != "string") return [
        'success' => false,
        'error' => "Исходное значение должно быть строкой",
    ];
    if (mb_strlen($string) < 3) return [
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
    $result = $mysqli->query($qs)->fetch_all(MYSQLI_ASSOC);

    if (count($result)) {
        return [
            'success' => true,
            'data' => array_map(
                function ($item) {
                    return $item['product_name'];
                },
                $result
            ),
        ];
    } else {
        return [
            'success' => false,
            'error' => "Нет подсказок",
        ];
    }
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
