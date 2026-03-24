<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/order.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drink'])) {
    try {
        drinkOrder($_POST['drink']);
        $message = "Bestellung gespeichert: " . htmlspecialchars($_POST['drink']);
    } catch (Throwable $e) {
        $message = "Fehler: " . $e->getMessage();
    }
}

$pdo = getDb();
$drinks = $pdo->query("SELECT name, price, orders FROM drinks ORDER BY name")->fetchAll();
$logs = $pdo->query("
    SELECT d.name, l.ordered_price, l.created_at
    FROM order_log l
    JOIN drinks d ON d.id = l.drink_id
    ORDER BY l.created_at DESC
    LIMIT 20
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Drink Orders</title>
</head>
<body>
    <h1>Drink Orders</h1>

    <?php if ($message): ?>
        <p><?= $message ?></p>
    <?php endif; ?>

    <h2>Bestellen</h2>
    <form method="post">
        <select name="drink">
            <?php foreach ($drinks as $drink): ?>
                <option value="<?= htmlspecialchars($drink['name']) ?>">
                    <?= htmlspecialchars($drink['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Bestellen</button>
    </form>

    <h2>Drinks</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Name</th>
            <th>Preis</th>
            <th>Orders</th>
        </tr>
        <?php foreach ($drinks as $drink): ?>
            <tr>
                <td><?= htmlspecialchars($drink['name']) ?></td>
                <td><?= number_format((float)$drink['price'], 2) ?></td>
                <td><?= (int)$drink['orders'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Letzte Logs</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Drink</th>
            <th>Preis bei Order</th>
            <th>Zeitpunkt</th>
        </tr>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['name']) ?></td>
                <td><?= number_format((float)$log['ordered_price'], 2) ?></td>
                <td><?= htmlspecialchars($log['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>