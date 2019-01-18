<?php

/** @var \App\Control\ServerSources $ServerSources */
require_once __DIR__ . '/../bootstrap.php';

$serverId = $_GET['server'];

try
{
	$serverConfig = $ServerSources->getServer($serverId);

	$zones = $serverConfig->map->zones;

	$result = [
		'zones' => $zones,
	];

	json_setData($result);
}
catch (\Throwable $ex)
{
	json_setError($ex->getMessage(), $ex->getCode());
}
json_finish();
