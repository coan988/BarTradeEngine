<?php

declare(strict_types=1);

require_once __DIR__ . '/priceservice.php';

echo "\nTest drinkorder():\n";

$id = 3;
$orders = 15;
$scenario = [
    'name' => 'stockmarketcrash',
    'type' => 'all_drinks',
    'factor' => 0.5
];

$order = new Pricebuilding();
$order->run($id, $orders);

echo "Bestellung verarbeitet.\n";
echo "Drinks Array:\n";
var_dump($order->drinks);


$button = new Scenario();
$button->run($scenario);
echo "Szenario ausgeführt.\n";
echo "Aktuelles Szenario:\n";
var_dump($button->latestScenario);