<?php
require __DIR__ . '/autoload.php';
use App\Classes\Db;
use App\Classes\Upwork;
$configDb = json_decode(file_get_contents(__DIR__ . '/config.json'));
$db = new Db($configDb);

//
//$result['draw'] = 1;
//$result['recordsTotal'] = 100;
//$result['recordsFiltered'] = 100;


$result['data'] = Upwork::findAll($db);
echo json_encode($result, JSON_UNESCAPED_UNICODE);