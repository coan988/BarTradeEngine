<?php

function getstartprice(array $priceinput){
    $result = [];
    foreach($priceinput as $line){
        $parts = explode(",", $line, 2);
        $name = trim($parts[0]);
        $price = number_Format(trim($parts[1]), 2);

        $result[$name] = $price;
    }
    return $result;
}

function drinkorder(){
    
}
$pricelist = getstartprice(file(__DIR__ . "/startprice.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
print_r($pricelist);