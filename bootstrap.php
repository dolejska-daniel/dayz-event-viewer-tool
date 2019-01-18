<?php

use App\Control\EventParser;
use App\Control\LogLoader;
use App\Control\ServerSources;
use Latte\Engine;
use Nette\Neon\Neon;
use Nette\Utils\ArrayHash;

require_once __DIR__ . '/vendor/autoload.php';

define('WEBROOT', realpath(__DIR__));
define('CACHEDIR', realpath(WEBROOT . "/cache"));
define('TEMPLATEDIR', realpath(WEBROOT . "/app/templates"));

// System configuration file
/** @var ArrayHash $service */
$service = ArrayHash::from(Neon::decode(file_get_contents(__DIR__ . "/config/service.neon")));

// Map configuration file
/** @var ArrayHash $map */
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

/** @var ServerSources $ServerSources */
$ServerSources = new ServerSources($service);
/** @var LogLoader $LogLoader */
$LogLoader = new LogLoader($service, $ServerSources);
/** @var EventParser $EventParser */
$EventParser = new EventParser($service, $LogLoader);

/** @var Engine $Latte */
$Latte = new Engine;
$Latte->setTempDirectory(CACHEDIR . "/latte");
