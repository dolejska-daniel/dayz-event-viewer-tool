<?php

require_once __DIR__ . '/../bootstrap.php';

use Nette\Neon\Neon;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;


$data = [];
$serverConfigs = [];
if (in_array($service->behaviour->server, ["selectable", "group"]))
{
	$serverConfigs = glob(__DIR__ . "/../config/servers/*.neon");
}
else if ($service->behaviour->server == "preselected")
{
	$serverConfigs = glob(__DIR__ . "/../config/servers/{$service->behaviour->serverSelection}.neon");
}
else if ($service->behaviour->server == "list")
{
	foreach ($service->behaviour->serverSelection as $serverId)
		$serverConfigs[] = __DIR__ . "/../config/servers/{$serverId}.neon";
}
else if ($service->behaivour->server == "all")
{
}

foreach ($serverConfigs as $config)
{
	$config = ArrayHash::from(Neon::decode(file_get_contents($config)));
	if ($service->behaviour->server == "group")
		if ($config->server->group != $service->behaviour->serverSelection)
			continue;

	$data[$config->server->group][$config->server->id] = $config->server->name;
}

echo Json::encode($data/*, Json::PRETTY*/);