<?php

use Nette\Utils\Json;

/** @var \App\Control\ServerSources $ServerSources */
require_once __DIR__ . '/../bootstrap.php';

try
{
	$servers = $ServerSources->getServerList();

	echo Json::encode($servers/*, Json::PRETTY*/);
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
