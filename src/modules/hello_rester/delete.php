<?php if(!defined('__RESTER__')) exit;

// TODO delete 예제 넣기

$rows = rester::cfg('rows');
$query = " SELECT * FROM example LIMIT {$rows} ";

$pdo = db::get();

$list = [];
foreach($pdo->query($query,PDO::FETCH_ASSOC) as $row)
{
    $list[] = $row;
}

return $list;
