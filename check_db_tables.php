<?php
require 'global/connection.php';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in database:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}
?>
