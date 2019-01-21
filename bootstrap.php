<?php

use App\Control\EventParser;
use App\Control\LogLoader;
use App\Control\ServerSources;
use Ehesp\SteamLogin\SteamLogin;
use Latte\Engine;
use Nette\Http\Request;
use Nette\Http\RequestFactory;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Neon\Neon;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/koraktor/steam-condenser/lib/steam-condenser.php';

//=============================================================dd==
// CONSTANT DEFINITIONS
//=============================================================dd==

define('WEBROOT', realpath(__DIR__));
define('CACHEDIR', realpath(WEBROOT . "/cache"));
define('TEMPLATEDIR', realpath(WEBROOT . "/app/templates"));


//=============================================================dd==
// CORE CLASSES INITIALIZATION
//=============================================================dd==

$httpRequestFactory = new RequestFactory();

/** @var Request $httpRequest */
global $httpRequest;
$httpRequest = $httpRequestFactory->createHttpRequest();
/** @var Response $httpResponse */
global $httpResponse;
$httpResponse = new Response();

/** @var Session $session */
global $session;
$session = new Session($httpRequest, $httpResponse);
$session->setSavePath(CACHEDIR . "/session");

/** @var SessionSection $Steam */
global $Steam;
$Steam = $session->getSection('steam');
if (!isset($Steam['id']))
	$Steam->id = 0;


//=============================================================dd==
// SYSTEM CONFIGURATION
//=============================================================dd==

// System configuration file
/** @var ArrayHash $service */
$service = ArrayHash::from(Neon::decode(file_get_contents(__DIR__ . "/config/service.neon")));
// Steam login prompt timing
if ($Steam->_loginPrompt)
{
	$service->steam->login->prompt = false;
}
elseif ($service->steam->login->prompt)
{
	$Steam->_loginPrompt = true;
	$Steam->setExpiration($service->steam->login->promptInterval, '_loginPrompt');
}

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

/** @var SteamLogin $SteamLogin */
$SteamLogin = new SteamLogin();
$Steam->_loginUrl = $SteamLogin->url($httpRequest->getUrl()->getBaseUrl() . "?action=steamlogin");

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
// GLOBAL ACTION PROCESS
//=============================================================dd==

if ($httpRequest->getQuery('action') === 'steamlogin'
	&& @$service->steam->login->enabled)
{
	try
	{
		$steamid64 = $SteamLogin->validate();

		if ($steamid64)
		{
			$profile = \SteamId::create($steamid64);

			$Steam->id = $profile->getId();
			$Steam->nickname = $profile->getNickname();
			$Steam->avatar_icon = $profile->getIconAvatarUrl();
			$Steam->avatar_medium = $profile->getMediumAvatarUrl();
			$Steam->avatar_full = $profile->getFullAvatarUrl();
		}
	}
	catch (\Throwable $ex)
	{

	}
	$httpResponse->redirect($httpRequest->getUrl()->getBaseUrl());
}
elseif ($httpRequest->getQuery('action') === 'steamlogout')
{
	$Steam->setExpiration(-3600);
	$httpResponse->redirect($httpRequest->getUrl()->getBaseUrl());
}

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

	global $Steam, $httpRequest;
	$response_vars = array_merge([
		'httpRequest' => $httpRequest,
		'Steam' => $Steam,
	], $vars);
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