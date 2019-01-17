<?php

use Nette\Utils\DateTime;
use Nette\Utils\Json;

/** @var \App\Control\ServerSources $ServerSources */
require_once __DIR__ . '/../bootstrap.php';

$serverId = $_GET['server'];

try
{
	$serverConfig = $ServerSources->getServer($serverId);

	$zones = $serverConfig->map->zones;

	echo Json::encode([
		'zones' => $zones,
	]/*, Json::PRETTY*/);
}
catch (\Throwable $ex)
{
	echo json_encode([
		'error' => true,
		'message' => $ex->getMessage(),
		'code' => $ex->getCode(),
	]);
}
die();
