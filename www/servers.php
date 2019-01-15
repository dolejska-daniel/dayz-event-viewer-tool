<?php

require_once __DIR__ . '/../bootstrap.php';

use Nette\Neon\Neon;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;


$data = [];
$serverConfigs = glob(__DIR__ . "/../config/servers/*.neon");
foreach ($serverConfigs as $config)
{
	$config = ArrayHash::from(Neon::decode(file_get_contents($config)));
	$data[$config->server->group][$config->server->id] = $config->server->name;
}


echo Json::encode($data/*, Json::PRETTY*/);