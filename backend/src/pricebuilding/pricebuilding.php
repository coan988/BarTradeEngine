<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../scenario/button.php';

function button(){
    $button = new Button();
    return $button->chooseScenario();
}

function drinkorder(string $name, int $order_count): array{
    if ($order_count <= 0) {
        throw new InvalidArgumentException('order_count muss größer als 0 sein.');
    }

    $pdo = getDb();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'SELECT id, name, price, order_count
             FROM drinks
             WHERE name = :name
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute(['name' => $name]);

        $drink = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$drink) {
            throw new InvalidArgumentException("Getränk '{$name}' nicht gefunden.");
        }

        $oldOrderCount = (int)$drink['order_count'];
        $newOrderCount = $oldOrderCount + $order_count;

        $oldPrice = (float)$drink['price'];
        $newPrice = $oldPrice;

        $oldSteps = intdiv($oldOrderCount, 10);
        $newSteps = intdiv($newOrderCount, 10);
        $stepDiff = $newSteps - $oldSteps;

        for ($i = 0; $i < $stepDiff; $i++){
            $newPrice *= 1.01;
        }

        $newPrice = round($newPrice, 2);

        $updateStmt = $pdo->prepare(
            'UPDATE drinks
             SET price = :price, order_count = :order_count
             WHERE id = :id'
        );

        $updateStmt->execute([
            'price'       => $newPrice,
            'order_count' => $newOrderCount,
            'id'          => (int)$drink['id'],
        ]);

        $ordervolume = $newOrderCount - $oldOrderCount;

        for ($i = 0; $i < $ordervolume; $i++){
            $updatelog = $pdo->prepare(
                'INSERT INTO order_log (drink_id, price_at_order)
                VALUES (:id, :price)'
            );

            $updatelog->execute([
                'price'       => $oldPrice,
                'id'          => (int)$drink['id'],
            ]);
        };
        
        $pdo->commit();

        return [
            'id' => (int)$drink['id'],
            'name' => (string)$drink['name'],
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'old_order_count' => $oldOrderCount,
            'new_order_count' => $newOrderCount,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}