<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function applyScenario(array $scenario): array
{
    $pdo = getDb();
    $pdo->beginTransaction();

    try {
        $affectedDrink = null;

        $logStmt = $pdo->prepare(
            'INSERT INTO scenario_log (scenario_name)
             VALUES (:scenario_name)'
        );
        $logStmt->execute([
            'scenario_name' => $scenario['name'],
        ]);

        $scenarioId = (int)$pdo->lastInsertId();

        switch ($scenario['type']) {
            case 'all_prices_factor':
                applyAllPricesFactorScenario($pdo, $scenario, $scenarioId);
                break;

            case 'random_drink_factor':
                $affectedDrink = applyRandomDrinkFactorScenario($pdo, $scenario, $scenarioId);
                break;

            default:
                throw new InvalidArgumentException('Unbekanntes Szenario.');
        }

        $pdo->commit();

        return [
            'id' => $scenarioId,
            'name' => $scenario['name'],
            'affected_drink' => $affectedDrink,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function applyAllPricesFactorScenario(PDO $pdo, array $scenario, int $scenarioId): void
{
    if (!isset($scenario['factor']) || !is_numeric($scenario['factor'])) {
        throw new InvalidArgumentException('Für das Szenario fehlt ein gültiger Faktor.');
    }

    $factor = (float)$scenario['factor'];

    $selectStmt = $pdo->query(
        'SELECT id, name, price
         FROM drinks
         FOR UPDATE'
    );

    $drinks = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare(
        'UPDATE drinks
         SET price = :new_price
         WHERE id = :id'
    );

    foreach ($drinks as $drink) {
        $oldPrice = (float)$drink['price'];
        $newPrice = round($oldPrice * $factor, 2);

        if ($newPrice === $oldPrice) {
            continue;
        }

        $updateStmt->execute([
            'new_price' => $newPrice,
            'id' => (int)$drink['id'],
        ]);

        insertPriceChange(
            $pdo,
            $newPrice,
            $oldPrice,
            (int)$drink['id'],
            null,
            $scenarioId
        );
    }
}

function applyRandomDrinkFactorScenario(PDO $pdo, array $scenario, int $scenarioId): array
{
    if (!isset($scenario['factor']) || !is_numeric($scenario['factor'])) {
        throw new InvalidArgumentException('Für das Szenario fehlt ein gültiger Faktor.');
    }

    $factor = (float)$scenario['factor'];

    $stmt = $pdo->query(
        'SELECT id, name, price
         FROM drinks
         ORDER BY RAND()
         LIMIT 1
         FOR UPDATE'
    );

    $drink = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$drink) {
        throw new RuntimeException('Keine Getränke gefunden.');
    }

    $oldPrice = (float)$drink['price'];
    $newPrice = round($oldPrice * $factor, 2);

    if ($newPrice !== $oldPrice) {
        $updateStmt = $pdo->prepare(
            'UPDATE drinks
             SET price = :new_price
             WHERE id = :id'
        );

        $updateStmt->execute([
            'new_price' => $newPrice,
            'id' => (int)$drink['id'],
        ]);

        insertPriceChange(
            $pdo,
            $newPrice,
            $oldPrice,
            (int)$drink['id'],
            null,
            $scenarioId
        );
    }

    return [
        'id' => (int)$drink['id'],
        'name' => (string)$drink['name'],
    ];
}

function insertPriceChange(PDO $pdo, float $newPrice, float $oldPrice, int $drinkId, ?int $orderId, ?int $scenarioId): void {
    $stmt = $pdo->prepare(
        'INSERT INTO price_change (
            new_price,
            old_price,
            drink_id,
            order_id,
            scenario_id
         ) VALUES (
            :new_price,
            :old_price,
            :drink_id,
            :order_id,
            :scenario_id
         )'
    );

    $stmt->execute([
        'new_price' => round($newPrice, 2),
        'old_price' => round($oldPrice, 2),
        'drink_id' => $drinkId,
        'order_id' => $orderId,
        'scenario_id' => $scenarioId,
    ]);
}

function getAllDrinks(): array
{
    $pdo = getDb();

    $stmt = $pdo->query(
        'SELECT id, name, price, order_count
         FROM drinks
         ORDER BY name ASC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCurrentScenario(): ?array
{
    $pdo = getDb();

    $stmt = $pdo->query(
        'SELECT id, scenario_name, executed_at
         FROM scenario_log
         ORDER BY id DESC
         LIMIT 1'
    );

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    return [
        'id' => (int)$row['id'],
        'name' => (string)$row['scenario_name'],
        'executed_at' => (string)$row['executed_at'],
    ];
}

function calculateDrinkUpdate(float $oldPrice, int $oldOrderCount, int $additionalOrders): array
{
    if ($additionalOrders <= 0) {
        throw new InvalidArgumentException('additionalOrders muss größer als 0 sein.');
    }

    $newOrderCount = $oldOrderCount + $additionalOrders;

    $oldSteps = intdiv($oldOrderCount, 10);
    $newSteps = intdiv($newOrderCount, 10);
    $stepDiff = $newSteps - $oldSteps;

    $newPrice = $oldPrice;

    for ($i = 0; $i < $stepDiff; $i++) {
        $newPrice *= 1.01;
    }

    $newPrice = round($newPrice, 2);

    return [
        'old_price' => $oldPrice,
        'new_price' => $newPrice,
        'old_order_count' => $oldOrderCount,
        'new_order_count' => $newOrderCount,
        'step_diff' => $stepDiff,
    ];
}

function findDrinkForUpdate(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, price, order_count
         FROM drinks
         WHERE id = :id
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->execute(['id' => $id]);
    $drink = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$drink) {
        throw new InvalidArgumentException("Getränk mit ID {$id} nicht gefunden.");
    }

    return $drink;
}

function updateDrink(PDO $pdo, int $id, float $newPrice, int $newOrderCount): void
{
    $stmt = $pdo->prepare(
        'UPDATE drinks
         SET price = :price, order_count = :order_count
         WHERE id = :id'
    );

    $stmt->execute([
        'price' => round($newPrice, 2),
        'order_count' => $newOrderCount,
        'id' => $id,
    ]);
}

function insertOrderLog(PDO $pdo, int $drinkId, float $priceAtOrder, int $count): array
{
    $stmt = $pdo->prepare(
        'INSERT INTO order_log (drink_id, price_at_order)
         VALUES (:drink_id, :price_at_order)'
    );

    $orderIds = [];

    for ($i = 0; $i < $count; $i++) {
        $stmt->execute([
            'drink_id' => $drinkId,
            'price_at_order' => $priceAtOrder,
        ]);

        $orderIds[] = (int)$pdo->lastInsertId();
    }

    return $orderIds;
}

function drinkOrder(int $id, int $orderCount): array{
    if ($orderCount <= 0) {
        throw new InvalidArgumentException('orderCount muss größer als 0 sein.');
    }

    $pdo = getDb();
    $pdo->beginTransaction();

    try {
        $drink = findDrinkForUpdate($pdo, $id);

        $result = calculateDrinkUpdate(
            (float)$drink['price'],
            (int)$drink['order_count'],
            $orderCount
        );

        updateDrink(
            $pdo,
            (int)$drink['id'],
            (float)$result['new_price'],
            (int)$result['new_order_count']
        );

        $orderIds = insertOrderLog(
            $pdo,
            (int)$drink['id'],
            (float)$result['old_price'],
            $orderCount
        );

        if ((int)$result['step_diff'] > 0) {
            insertPriceChange(
                $pdo,
                (float)$result['new_price'],
                (float)$result['old_price'],
                (int)$drink['id'],
                $orderIds[0],
                null
            );
    }

    $pdo->commit();

    return [
        'id' => (int)$drink['id'],
        'name' => (string)$drink['name'],
        ...$result,
    ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
    }

    throw $e;
    }
}