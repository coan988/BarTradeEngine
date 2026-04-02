<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function applyScenario(array $scenario): array{
    $pdo = getDb();
    $pdo->beginTransaction();

    try {
        switch ($scenario['type']) {
            case 'all_prices_factor':
                $stmt = $pdo->prepare(
                    'UPDATE drinks
                     SET price = ROUND(price * :factor, 2)'
                );
                $stmt->execute([
                    'factor' => $scenario['factor'],
                ]);
                break;

            case 'random_drink_factor':
                $stmt = $pdo->query(
                    'SELECT id, name
                     FROM drinks
                     ORDER BY RAND()
                     LIMIT 1
                     FOR UPDATE'
                );

                $drink = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$drink) {
                    throw new RuntimeException('Keine Getränke gefunden.');
                }

                $updateStmt = $pdo->prepare(
                    'UPDATE drinks
                     SET price = ROUND(price * :factor, 2)
                     WHERE id = :id'
                );

                $updateStmt->execute([
                    'factor' => $scenario['factor'],
                    'id' => (int)$drink['id'],
                ]);

                $scenario['affected_drink'] = $drink['name'];
                break;

            default:
                throw new InvalidArgumentException('Unbekanntes Scenario.');
        }

        $pdo->commit();
        return $scenario;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function calculateDrinkUpdate(float $oldPrice, int $oldOrderCount, int $additionalOrders): array{
    if ($additionalOrders <= 0) {
        throw new InvalidArgumentException('additionalOrders muss größer als 0 sein.');
    }

    $newOrderCount = $oldOrderCount + $additionalOrders;

    $oldSteps = intdiv($oldOrderCount, 10);
    $newSteps = intdiv($newOrderCount, 10);
    $stepDiff = $newSteps - $oldSteps;

    $newPrice = $oldPrice;

    for ($i = 0; $i < $stepDiff; $i++){
        $newPrice *= 1.01;
    }

    return [
        'old_price' => $oldPrice,
        'new_price' => round($newPrice, 2),
        'old_order_count' => $oldOrderCount,
        'new_order_count' => $newOrderCount,
        'step_diff' => $stepDiff,
    ];
}

function findDrinkForUpdate(PDO $pdo, string $name): array{
    $stmt = $pdo->prepare(
        'SELECT id, name, price, order_count
         FROM drinks
         WHERE name = :name
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->execute(['name' => $name]);
    $drink = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$drink){
        throw new InvalidArgumentException("Getränk '{$name}' nicht gefunden.");
    }

    return $drink;
}

function updateDrink(PDO $pdo, int $id, float $newPrice, int $newOrderCount): void{
    $stmt = $pdo->prepare(
        'UPDATE drinks
         SET price = :price, order_count = :order_count
         WHERE id = :id'
    );

    $stmt->execute([
        'price' => $newPrice,
        'order_count' => $newOrderCount,
        'id' => $id,
    ]);
}

function insertOrderLog(PDO $pdo, int $drinkId, float $priceAtOrder, int $count): void{
    $stmt = $pdo->prepare(
        'INSERT INTO order_log (drink_id, price_at_order)
         VALUES (:id, :price)'
    );

    for ($i = 0; $i < $count; $i++){
        $stmt->execute([
            'id' => $drinkId,
            'price' => $priceAtOrder,
        ]);
    }
}
function drinkorder(string $name, int $orderCount): array{
    if ($orderCount <= 0) {
        throw new InvalidArgumentException('orderCount muss größer als 0 sein.');
    }

    $pdo = getDb();
    $pdo->beginTransaction();

    try {
        $drink = findDrinkForUpdate($pdo, $name);

        $result = calculateDrinkUpdate(
            (float)$drink['price'],
            (int)$drink['order_count'],
            $orderCount
        );

        updateDrink(
            $pdo,
            (int)$drink['id'],
            $result['new_price'],
            $result['new_order_count']
        );

        insertOrderLog(
            $pdo,
            (int)$drink['id'],
            $result['old_price'],
            $orderCount
        );

        $pdo->commit();

        return [
            'id' => (int)$drink['id'],
            'name' => (string)$drink['name'],
            ...$result,
        ];
    } catch (Throwable $e){
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}