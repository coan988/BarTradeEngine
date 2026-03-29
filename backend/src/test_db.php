<?php

require __DIR__ . '/db.php';

try {
    $db = getDb();

    echo "DB Verbindung erfolgreich\n";

    $stmt = $db->query("SELECT NOW() as time");
    $result = $stmt->fetch();

    print_r($result);

} catch (Throwable $e) {
    echo "DB Fehler:\n";
    echo $e->getMessage();
}