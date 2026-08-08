<?php
$pdo = new PDO('sqlite:' . ($argv[1] ?? __DIR__ . '/../database/database.sqlite'));
$rows = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
foreach ($rows as $name) {
    $n = $pdo->query("SELECT COUNT(*) FROM \"{$name}\"")->fetchColumn();
    echo "{$name}: {$n}\n";
}
