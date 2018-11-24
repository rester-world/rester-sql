<?php if(!defined('__RESTER__')) exit;

$pdo = db::get();
$query = " INSERT INTO `rester-sql`.`example` (`key`, `value`) VALUES (?, ?) ";
$pdo->prepare($query)->execute([rand(0,255),rand(0,255)]);

return [
    'inserted_id' => $pdo->lastInsertId()
];
