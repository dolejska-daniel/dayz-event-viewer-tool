<?php

/** @var \App\Control\ServerSources $ServerSources */
require_once __DIR__ . '/../bootstrap.php';

try
{
	$servers = $ServerSources->getServerList();

	json_setData($servers);
}
catch (\Throwable $ex)
{
	json_setError($ex->getMessage(), $ex->getCode());
}
json_finish();
