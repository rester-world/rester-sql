<?php use rester\sql\db;

if(!defined('__RESTER__')) exit;

$query = " SELECT * FROM `example` LIMIT 10 ";

$pdo = db::get();

$list = [];
foreach($pdo->query($query,PDO::FETCH_ASSOC) as $row)
{
    $list[] = $row;
}

return $list;
