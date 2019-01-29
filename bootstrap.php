<?php

/**
 * Copyright (C) 2019  Daniel Dolejška
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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

if (!defined('WEBROOT'))
	define('WEBROOT', realpath(__DIR__));

if (!defined('CFGDIR'))
	define('CFGDIR', realpath(WEBROOT . "/config"));

if (!defined('CACHEDIR'))
	define('CACHEDIR', realpath(WEBROOT . "/cache"));

if (!defined('TEMPLATEDIR'))
	define('TEMPLATEDIR', realpath(WEBROOT . "/app/templates"));


//=============================================================dd==
// SYSTEM CONFIGURATION
//=============================================================dd==

// System configuration file
/** @var ArrayHash $service */
$service = ArrayHash::from(Neon::decode(file_get_contents(CFGDIR . "/service.neon")));

// Map configuration file
/** @var ArrayHash $map */
$map = ArrayHash::from(Neon::decode(file_get_contents(CFGDIR . "/map.neon")));
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
$Steam = $session->getSection($service->steam->login->sessionName);
if (!isset($Steam['id']))
	$Steam->id = 0;


//=============================================================dd==
// CONFIGURATION MODIFICATIONS
//=============================================================dd==

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

if ($service->steam->login->enabled
	&& $Steam->id)
{
	// Steam login is enabled and currently used
	// Whitelist setup
	switch ($service->steam->login->limits->whitelistOperation)
	{
		case 'override':
		{
			$service->limits->events->whitelist = $service->steam->login->limits->events->whitelist;
			$service->limits->attributes->whitelist = $service->steam->login->limits->attributes->whitelist;
			break;
		}

		case 'intersection':
		{
			$service->limits->events->whitelist = array_intersect(
				$service->limits->events->whitelist,
				$service->steam->login->limits->events->whitelist
			);
			$service->limits->attributes->whitelist = array_intersect(
				$service->limits->attributes->whitelist,
				$service->steam->login->limits->attributes->whitelist
			);
			break;
		}

		default:
		case 'union':
		{
			$service->limits->events->whitelist = array_merge(
				$service->limits->events->whitelist,
				$service->steam->login->limits->events->whitelist
			);
			$service->limits->attributes->whitelist = array_merge(
				$service->limits->attributes->whitelist,
				$service->steam->login->limits->attributes->whitelist
			);
			break;
		}
	}

	// Blacklist setup
	switch ($service->steam->login->limits->blacklistOperation)
	{
		case 'override':
		{
			$service->limits->events->blacklist = $service->steam->login->limits->events->blacklist;
			$service->limits->attributes->blacklist = $service->steam->login->limits->attributes->blacklist;
			break;
		}

		case 'intersection':
		{
			$service->limits->events->blacklist = array_intersect(
				$service->limits->events->blacklist,
				$service->steam->login->limits->events->blacklist
			);
			$service->limits->attributes->blacklist = array_intersect(
				$service->limits->attributes->blacklist,
				$service->steam->login->limits->attributes->blacklist
			);
			break;
		}

		default:
		case 'union':
		{
			$service->limits->events->blacklist = array_merge(
				$service->limits->events->blacklist,
				$service->steam->login->limits->events->blacklist
			);
			$service->limits->attributes->blacklist = array_merge(
				$service->limits->attributes->blacklist,
				$service->steam->login->limits->attributes->blacklist
			);
			break;
		}
	}

	function overridServiceSettingsBySteamLoginSettings( $serviceSettings, $steamSettings )
	{
		foreach ($steamSettings as $key => $value)
		{
			if (in_array($key, [ 'events', 'attributes' ]))
				continue;

			if (is_object($value) || is_array($value))
				overridServiceSettingsBySteamLoginSettings($serviceSettings[$key], $value);
			else
				$serviceSettings[$key] = $value;
		}
	}
	overridServiceSettingsBySteamLoginSettings($service->limits, $service->steam->login->limits);
}


//=============================================================dd==
// CONTROL CLASSES INITIALIZATION
//=============================================================dd==

/** @var ServerSources $ServerSources */
$ServerSources = new ServerSources($session, $service);
/** @var LogLoader $LogLoader */
$LogLoader = new LogLoader($ServerSources);
/** @var EventParser $EventParser */
$EventParser = new EventParser($LogLoader);

/** @var Engine $Latte */
global $Latte;
$Latte = new Engine;
$Latte->setTempDirectory(CACHEDIR . "/latte");


//=============================================================dd==
// GLOBAL ACTION PROCESS
//=============================================================dd==

if ($httpRequest->getQuery('action') === 'steamlogin-init'
	&& @$service->steam->login->enabled)
{
	$SteamLogin = new SteamLogin();
	$returnUrl = $httpRequest->getUrl()->setQueryParameter('action', 'steamlogin');
	$loginUrl = $SteamLogin->url((string)$returnUrl);
	$httpResponse->redirect($loginUrl);
}
elseif ($httpRequest->getQuery('action') === 'steamlogin'
	&& @$service->steam->login->enabled)
{
	try
	{
		$SteamLogin = new SteamLogin();
		$steamid64 = $SteamLogin->validate();

		if ($steamid64)
		{
			$profile = \SteamId::create($steamid64);

			$Steam->id = $profile->getSteamId64();
			$Steam->nickname = $profile->getNickname();
			$Steam->avatar_icon = $profile->getIconAvatarUrl();
			$Steam->avatar_medium = $profile->getMediumAvatarUrl();
			$Steam->avatar_full = $profile->getFullAvatarUrl();
		}
	}
	catch (\Throwable $ex)
	{

	}

	$redirectUrl = $httpRequest->getUrl();
	$query = [];
	foreach ($redirectUrl->getQueryParameters() as $parameter => $value)
		if (strpos($parameter, 'openid') === false && strpos($parameter, 'action') === false)
			$query[$parameter] = $value;
	$httpResponse->redirect($redirectUrl->setQuery($query));
}
elseif ($httpRequest->getQuery('action') === 'steamlogout')
{
	$Steam->setExpiration(-3600);

	$redirectUrl = $httpRequest->getUrl();
	$query = [];
	foreach ($redirectUrl->getQueryParameters() as $parameter => $value)
		if (strpos($parameter, 'action') === false)
			$query[$parameter] = $value;
	$httpResponse->redirect($redirectUrl->setQuery($query));
}
else
{
	if ($service->behaviour->server === 'selectable'
		&& $service->behaviour->serverSelection
		&& !$httpRequest->getQuery('server'))
	{
		$redirectUrl = $httpRequest->getUrl();
		$httpResponse->redirect($redirectUrl->setQueryParameter('server', $service->behaviour->serverSelection));
	}
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