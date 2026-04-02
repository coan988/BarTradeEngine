<?php

declare(strict_types=1);

require_once __DIR__ . '/priceservice.php';

echo "\nTest drinkorder():\n";

try {

    $drinkName = "Asbach";
    $orders = 15;

    $result = drinkorder($drinkName, $orders);

    echo "Bestellung erfolgreich verarbeitet.\n";

    echo "ID: " . $result['id'] . "\n";
    echo "Name: " . $result['name'] . "\n";
    echo "Alter Preis: " . $result['old_price'] . "\n";
    echo "Neuer Preis: " . $result['new_price'] . "\n";
    echo "Orders vorher: " . $result['old_order_count'] . "\n";
    echo "Orders danach: " . $result['new_order_count'] . "\n";

} catch (Throwable $e) {

    echo "Fehler bei drinkorder():\n";
    echo $e->getMessage() . "\n";
}