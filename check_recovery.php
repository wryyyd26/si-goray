<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = Illuminate\Support\Facades\DB::connection('mysql');
$database = 'gor_reservation_recovery';

$relations = $db->select(
    "SELECT
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = ?
       AND REFERENCED_TABLE_NAME IS NOT NULL",
    [$database]
);

$totalOrphan = 0;

foreach ($relations as $relation) {
    $table = $relation->TABLE_NAME;
    $column = $relation->COLUMN_NAME;
    $parent = $relation->REFERENCED_TABLE_NAME;
    $parentColumn = $relation->REFERENCED_COLUMN_NAME;

    $sql = "
        SELECT COUNT(*) AS total
        FROM `{$database}`.`{$table}` AS child
        LEFT JOIN `{$database}`.`{$parent}` AS parent
          ON child.`{$column}` = parent.`{$parentColumn}`
        WHERE child.`{$column}` IS NOT NULL
          AND parent.`{$parentColumn}` IS NULL
    ";

    $count = (int) $db->selectOne($sql)->total;

    echo "{$table}.{$column} => {$parent}.{$parentColumn}: {$count} orphan\n";

    $totalOrphan += $count;
}

echo "\nTOTAL ORPHAN: {$totalOrphan}\n";
