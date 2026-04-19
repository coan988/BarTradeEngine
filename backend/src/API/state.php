<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../pricebuilding/priceservice.php';

header('Content-Type: application/json');

try {
    $scenario = new Scenario();
    $drinks = new Pricebuilding();
    $clock = new Clock();

    echo json_encode([
        'scenario' => $scenario->latestScenario,
        'drinks' => $drinks->drinks,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}