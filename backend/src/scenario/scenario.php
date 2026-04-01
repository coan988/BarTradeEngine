<?php
require_once __DIR__ . '/../db.php';

function stockmarketcrash(){
    $pdo = getDb();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE drinks
        SET price = price*0.5'
    );
    $stmt->execute();

    $pdo->commit();
    echo("stockmarketcrash");
}

function drinksubvention(){
    $pdo = getDb();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id, name
        FROM drinks
        FOR UPDATE'
    );
    $stmt->execute();

    $drinks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$drinks) {
    $pdo->rollBack();
    return;
    }

    $randomKey = array_rand($drinks);
    $drink = $drinks[$randomKey];

    $updateStmt = $pdo->prepare(
        'UPDATE drinks
        SET price = price*0.5
        WHERE id = :id'
    );

    $updateStmt->execute([
        'id' => (int)$drink['id'],
    ]);

    $pdo->commit();
    echo("drinksubvention");
}

function alltimehight(){
    $pdo = getDb();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE drinks
        SET price = price*1.5'
    );
    $stmt->execute();

    $pdo->commit();
    echo("alltimehight");
}