<?php

/** @var \App\Control\ServerSources $ServerSources */
require_once __DIR__ . '/../bootstrap.php';

$serverId = $_GET['server'];

try
{
	$server = $ServerSources->getServer($serverId);
	$server = $server->server;

	$files = [];
	$logFiles = $ServerSources->getLogFiles($serverId);
	foreach ($logFiles as $filepath => $file)
		$files[$filepath] = $file['name'];

	$result = [
		'server' => $server,
		'files' => $files,
	];

	json_setData($result);
}
catch (\Throwable $ex)
{
	json_setError($ex->getMessage(), $ex->getCode());
}
json_finish();
