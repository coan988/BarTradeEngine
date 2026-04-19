<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../pricebuilding/priceservice.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $drinkId = $input['drink_id'] ?? null;
    $quantity = $input['quantity'] ?? null;

    if ($drinkId === null || $quantity === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'drink_id und quantity fehlen',
        ]);
        exit;
    }

    $drinkId = (int)$drinkId;
    $quantity = (int)$quantity;

    if ($drinkId <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'drink_id und quantity müssen größer als 0 sein',
        ]);
        exit;
    }
    $order = new Pricebuilding();
    $order->run($drinkId, $quantity);

    echo json_encode([
        'success' => true,
        'drinks' => $order->drinks,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}