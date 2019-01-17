<?php

use App\Control\EventParser;
use App\Control\LogLoader;
use App\Control\ServerSources;
use Nette\Neon\Neon;
use Nette\Utils\ArrayHash;

require_once __DIR__ . '/vendor/autoload.php';

define('WEBROOT', realpath(__DIR__));

// System configuration file
$service = ArrayHash::from(Neon::decode(file_get_contents(__DIR__ . "/config/service.neon")));

// Map configuration file
$map = ArrayHash::from(Neon::decode(file_get_contents(__DIR__ . "/config/map.neon")));
// String array key fix
foreach ($map->baseLayers as $layer)
{
	$bounds = $layer->options->bounds;
	$layer->options->bounds = [];
	foreach ($bounds as $bound)
		$layer->options->bounds[] = array_values((array)$bound);
}
$map->settings->options->center = array_values((array)$map->settings->options->center);

$ServerSources = new ServerSources($service);
$LogLoader = new LogLoader($service, $ServerSources);
$EventParser = new EventParser($service, $LogLoader);
