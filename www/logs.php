<?php

require_once __DIR__ . '/../bootstrap.php';

use Nette\Utils\Json;


$data = [
	'server' => [
		'name' => 'DONOR',
	]
];
$logs = glob(__DIR__ . "/../logs/*.ADM");
foreach ($logs as $id => $log)
{
	$filepath = explode('/', $log);
	$filename = end($filepath);

	$nameFrom = strpos($filename, '_x64_') + 5;
	$name = substr($filename, $nameFrom, strlen($filename) - $nameFrom - 4);
	$data['files'][$filename] = $name;
}


echo Json::encode($data/*, Json::PRETTY*/);