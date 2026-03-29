<?php

require_once __DIR__ .'/db.php';

function drinkOrder(string $drinkName): void
{
    $pdo = getDb();

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT id, price, orders FROM drinks WHERE name = ?");
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