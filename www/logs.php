<?php

use Nette\Utils\Json;

/** @var \App\Control\ServerSources $ServerSources */
require_once __DIR__ . '/../bootstrap.php';

$serverId = $_GET['server'];

try
{
	$server = $ServerSources->getServer($serverId);
	$server = $server['server'];

	$files = [];
	$logFiles = $ServerSources->getLogFiles($serverId);
	foreach ($logFiles as $filepath => $file)
		$files[$filepath] = $file['name'];

	echo Json::encode([
		'server' => $server,
		'files' => $files,
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