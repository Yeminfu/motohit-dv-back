<?php

function get_category($category_name)
{
    global $mysqli;
    global $config;
    $category = $mysqli->query("SELECT * FROM categories WHERE category_name = '$category_name'")->fetch_assoc();
    $category_id = $category['id'];

    $image = $mysqli->query("SELECT id, name FROM `media` WHERE essense_id = $category_id ")->fetch_assoc();

    $image['src'] = $config['homeurl'] . "/images/" . $image['name'];

    $category['image'] = $image;
    
    return $category;
}
