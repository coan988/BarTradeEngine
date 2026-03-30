<?php

require_once __DIR__ .'backend/src/db.php';

function button(){
    $pdo = getDb(); # preisliste holen
    $drinkId = $pdo[]; #pdo output anschauen
    # button class ausführen --> Ergebniss(scenario) prozentuale Preisveränderung, 
    # preise mit prozentuealer veränderung bearbeiten
    require __DIR__ .'src/scenario/button.php';
    $button = new Button();
    for (n; $pdo) {

    }
}

function drinkOrder(string $drinkName){
    $pdo = getDb();

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT id, price, orders FROM drinks WHERE name = $drinkName");
        $stmt->execute([$drinkName]);
        $drink = $stmt->fetch();

        if (!$drink) {
            throw new InvalidArgumentException("Getränk '{$drinkName}' nicht gefunden.");
        }

        $newOrders = $drink['orders'] + 1;
        $newPrice = (float)$drink['price'];

        if ($newOrders % 10 === 0) {
            $newPrice = round($newPrice * 1.01, 2);
        }

        $updateStmt = $pdo->prepare("
            UPDATE drinks
            SET orders = ?, price = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$newOrders, $newPrice, $drink['id']]);

        $logStmt = $pdo->prepare("
            INSERT INTO order_log (drink_id, ordered_price)
            VALUES (?, ?)
        ");
        $logStmt->execute([$drink['id'], $drink['price']]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}