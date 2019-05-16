<?php use rester\sql\db;

if(!defined('__RESTER__')) exit;

$query = " SELECT * FROM example LIMIT 1 ";

$pdo = db::get();

$deleted_row = [];
foreach($pdo->query($query,PDO::FETCH_ASSOC) as $row)
{
    $pdo->query("DELETE FROM `example` WHERE no={$row['no']} ");
    $deleted_row[] = $row;
}

return [
    'Delete one row!',
    $deleted_row
];
