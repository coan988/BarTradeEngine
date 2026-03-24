<?php
require __DIR__ .'/button.php';

$button = new Button();
echo "Alle Szenarien:\n";
var_dump($button->getScenarios());

echo "\nRandom Szenario:\n";
$scene = $button->chooseScenario();
var_dump($scene);
var_dump($button->run());