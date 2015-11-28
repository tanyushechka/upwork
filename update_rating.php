<?php
require __DIR__ . '/autoload.php';
use App\Classes\Db;

$configDb = json_decode(file_get_contents(__DIR__ . '/config.json'));
$db = new Db($configDb);

$sql = 'UPDATE upwork SET rating = :rating WHERE id = :id';
$db->dbExecute($sql, [':rating' => $_POST['rating'], ':id' => $_POST['id']]);