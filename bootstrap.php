<?php

use App\Control\EventParser;
use App\Control\LogLoader;
use App\Control\ServerSources;
use Latte\Engine;
use Nette\Neon\Neon;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;

require_once __DIR__ . '/vendor/autoload.php';

//=============================================================dd==
// CONSTANT DEFINITIONS
//=============================================================dd==

define('WEBROOT', realpath(__DIR__));
define('CACHEDIR', realpath(WEBROOT . "/cache"));
define('TEMPLATEDIR', realpath(WEBROOT . "/app/templates"));


//=============================================================dd==
// SYSTEM CONFIGURATION
//=============================================================dd==

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


//=============================================================dd==
// CONTROL CLASSES INITIALIZATION
//=============================================================dd==

/** @var ServerSources $ServerSources */
$ServerSources = new ServerSources($service);
/** @var LogLoader $LogLoader */
$LogLoader = new LogLoader($service, $ServerSources);
/** @var EventParser $EventParser */
$EventParser = new EventParser($service, $LogLoader);

/** @var Engine $Latte */
global $Latte;
$Latte = new Engine;
$Latte->setTempDirectory(CACHEDIR . "/latte");


//=============================================================dd==
// RESPONSE CONTROL FUNCTIONS
//=============================================================dd==

//--------------------------------------------dd--
// Latte response
//--------------------------------------------dd--

global $response_template, $response_vars;
function latte_setView( $template, $vars )
{
	global $response_template, $response_vars;
	$response_template = $template;
	$response_vars = $vars;
}

function latte_finish()
{
	global $response_template, $response_vars, $Latte;
	try
	{
		$Latte->render(TEMPLATEDIR . "/$response_template.latte", $response_vars);
	}
	catch (\Throwable $ex)
	{
		echo <<<HTML
<h1>Internal server error occured.</h1>
<p>I am sorry, but something really BAD happend.
I cannot really help you, the only thing I am able to provide you with is this error message:</p>
<code>{$ex->getMessage()}</code>
HTML;
	}
	exit(0);
}

//--------------------------------------------dd--
// JSON response
//--------------------------------------------dd--

global $response_json;
function json_setData( array $data )
{
	global $response_json;
	$response_json = $data;
}

function json_setError( string $message, int $code = 0 )
{
	global $response_json;
	$response_json = [
		'error' => true,
		'message' => $message,
		'code' => $code,
	];
}

function json_finish()
{
	global $response_json;
	try
	{
		echo Json::encode($response_json);
	}
	catch (\Throwable $ex)
	{
		json_setError($ex->getMessage(), $ex->getCode());
		json_finish();
	}
	exit(0);
}