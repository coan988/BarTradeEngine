<?php

require_once __DIR__ . '/db.php';

try {

    $pdo = getDb();

    echo "DB Verbindung erfolgreich\n\n";

    var_dump($pdo);

} catch (PDOException $e) {

    echo "DB Verbindung fehlgeschlagen:\n";
    echo $e->getMessage();
}