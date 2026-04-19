<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

class Scenario{
    private $latestScenario;

    public function __construct(){
        $pdo = getDb();
        $pdo->beginTransaction();
        $stmt = $pdo->query(
            'SELECT id, scenario_name, executed_at
            FROM scenario_log
            ORDER BY id DESC
            LIMIT 1'
        );

        $this->latestScenario = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdo->commit();
    }

    function AllDrinks(PDO $pdo, array $scenario, int $scenarioId): void{
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

            $updateStmt->execute([
                'new_price' => $newPrice,
                'id' => (int)$drink['id'],
            ]);
            $pricebuilding = new Pricebuilding();
            $pricebuilding->insertPriceChange($pdo, $newPrice, $oldPrice, (int)$drink['id'], null, $scenarioId);
        }
    }

    function RandomDrink(PDO $pdo, array $scenario, int $scenarioId): void{
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
            $pricebuilding = new Pricebuilding();
            $pricebuilding->insertPriceChange($pdo, $newPrice, $oldPrice, (int)$drink['id'], null, $scenarioId);
            }
    }

    function FixDrink(PDO $pdo, array $scenario, int $scenarioId): void{
        $factor = (float)$scenario['factor'];
        $drinkId = (int)$scenario['DrinkId'];

        $stmt = $pdo->prepare(
            'SELECT id, name, price
            FROM drinks
            WHERE id = :id
            LIMIT 1
            FOR UPDATE'
        );

        $stmt->execute(['id' => $drinkId]);
        $drink = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$drink) {
            throw new RuntimeException("Getränk mit ID {$drinkId} nicht gefunden.");
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
            $pricebuilding = new Pricebuilding();
            $pricebuilding->insertPriceChange($pdo, $newPrice, $oldPrice, (int)$drink['id'], null, $scenarioId);
        }
    }
    
    function applyScenario(PDO $pdo, array $scenario): void{
        $logStmt = $pdo->prepare(
            'INSERT INTO scenario_log (scenario_name)
             VALUES (:scenario_name)'
        );
        $logStmt->execute([
            'scenario_name' => $scenario['name'],
        ]);

        $scenarioId = (int)$pdo->lastInsertId();

        switch ($scenario['type']) {
            case 'all_drinks':
                $this->AllDrinks($pdo, $scenario, $scenarioId);
                break;

            case 'random_drink':
                $this->RandomDrink($pdo, $scenario, $scenarioId);
                break;
            
            case 'fix_drink':
                $this->FixDrink($pdo, $scenario, $scenarioId);
                break;

            default:
                throw new InvalidArgumentException('Unbekanntes Szenario.');
        }
    }

    function run($scenario): void{
        try{
            $pdo = getDb();
            $pdo->beginTransaction();
            $this->applyScenario($pdo, $scenario);
            $pdo->commit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
}

class Pricebuilding{
    private $drinks;

    public function __construct(){
        $pdo = getDb();

        $stmt = $pdo->query(
            'SELECT id, name, price, order_count
            FROM drinks
            ORDER BY name ASC'
        );

        $this->drinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function insertPriceChange(PDO $pdo, float $newPrice, float $oldPrice, int $drinkId, ?int $orderId, ?int $scenarioId): void{
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

    function findDrinkForUpdate(PDO $pdo, int $id): array{
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

    function calculateDrinkUpdate(float $oldPrice, int $oldOrderCount, int $additionalOrders): array{
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

    function updateDrink(PDO $pdo, int $id, float $newPrice, int $newOrderCount): void{
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

    public function insertOrderLog(PDO $pdo, int $drinkId, float $priceAtOrder, int $count): array{
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

    public function run(int $id, int $orderCount): void{
        if ($orderCount <= 0) {
            throw new InvalidArgumentException('orderCount muss größer als 0 sein.');
        }

        $pdo = getDb();
        $pdo->beginTransaction();

        try {
            $drink = $this->findDrinkForUpdate($pdo, $id);

            $result = $this->calculateDrinkUpdate((float)$drink['price'], (int)$drink['order_count'], $orderCount);

            $this->updateDrink($pdo, (int)$drink['id'], (float)$result['new_price'], (int)$result['new_order_count']);

            $orderIds = $this->insertOrderLog($pdo, (int)$drink['id'], (float)$result['old_price'], $orderCount);

            if ((int)$result['step_diff'] > 0) {
                $this->insertPriceChange($pdo, (float)$result['new_price'], (float)$result['old_price'], (int)$drink['id'], $orderIds[0], null);
            }

            $pdo->commit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

        throw $e;
        }
    }
}

class Clock{
    function run(): void{
        while (true) {
            sleep(5*60);
            $pdo = getDb();
            $pdo->beginTransaction();

            $selectStmt = $pdo->prepare(
                'SELECT id, price
                FROM drinks
                FOR UPDATE;'
            );
            $selectStmt->execute();
            $drinks = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

            $updateStmt = $pdo->prepare(
                'UPDATE drinks
                set price = :new_price
                WHERE id = :id'
            );

            foreach ($drinks as $drink){
                $oldPrice = (float)$drink['price'];
                $newPrice = round($oldPrice *0.9, 2);

                $updateStmt->execute([
                    'new_price' => $newPrice,
                    'id' => (int)$drink['id'],
                ]);
                $pricebuilding = new Pricebuilding();
                $pricebuilding->insertPriceChange($pdo, $newPrice, $oldPrice, (int)$drink['id'], null, null);
            }
            $pdo->commit();
        }
    }
}