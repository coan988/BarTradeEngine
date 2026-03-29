<?php

require_once __DIR__ . '/../src/db.php';

header('Content-Type: application/json');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'GET' && $path === '/api/drinks') {
    $stmt = $pdo->query('SELECT id, name, price, order_count FROM drinks ORDER BY name');
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST' && $path === '/api/order') {
    $data = json_decode(file_get_contents('php://input'), true);
    $drinkName = $data['name'] ?? null;

    if (!$drinkName) {
        http_response_code(400);
        echo json_encode(['error' => 'name fehlt']);
        exit;
    }

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT id, price, order_count FROM drinks WHERE name = ?');
        $stmt->execute([$drinkName]);
        $drink = $stmt->fetch();

        if (!$drink) {
            throw new RuntimeException('Drink nicht gefunden');
        }

        $newCount = $drink['order_count'] + 1;
        $newPrice = (float)$drink['price'];

        $logStmt = $pdo->prepare(
            'INSERT INTO order_log (drink_id, price_at_order) VALUES (?, ?)'
        );
        $logStmt->execute([$drink['id'], $drink['price']]);

        if ($newCount % 10 === 0) {
            $newPrice = round($newPrice * 1.01, 2);
        }

        $updateStmt = $pdo->prepare(
            'UPDATE drinks SET order_count = ?, price = ? WHERE id = ?'
        );
        $updateStmt->execute([$newCount, $newPrice, $drink['id']]);

        $pdo->commit();

        echo json_encode([
            'ok' => true,
            'name' => $drinkName,
            'new_count' => $newCount,
            'new_price' => $newPrice
        ]);
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'not found']);