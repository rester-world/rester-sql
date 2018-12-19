<?php use rester\sql\db;

if(!defined('__RESTER__')) exit;

$query = " SELECT * FROM example ORDER BY rand() LIMIT 1 ";

$pdo = db::get();

$updated_row = [];
foreach($pdo->query($query,PDO::FETCH_ASSOC) as $row)
{
    $_key = rand(0,255);
    $_value = rand(0,255);
    $query = "UPDATE `example` SET `key`=:key, `value`=:value WHERE no={$row['no']} LIMIT 1 ";
    $pdo->prepare($query)->execute([
        'key'=>$_key,
        'value'=>$_value
    ]);
    $updated_row['old'] = $row;
    $updated_row['new'] = [
        'key'=>$_key,
        'value'=>$_value
    ];
}

return [
    'One row updated!',
    $updated_row
];
