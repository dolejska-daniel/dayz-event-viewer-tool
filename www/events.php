<?php

require_once __DIR__ . '/../bootstrap.php';

use Nette\Utils\Json;
use Nette\Utils\ArrayHash;
use Nette\Neon\Neon;
use Sabre\DAV\Client;


// TODO: preg match validation
$serverId = $_GET['server'];
if ($service->behaviour->server == "selectable")
{
}
else if ($service->behaviour->server == "group")
{
	$serverId = null;
}
else if ($service->behaviour->server == "preselected")
{
	$serverId = $service->behaviour->serverSelection;
}
else if ($service->behaviour->server == "list")
{
	$serverId = null;
}
else if ($service->behaivour->server == "all")
{
	$serverId = null;
}

$serverConfig = file_get_contents(__DIR__ . "/../config/servers/$serverId.neon");
if (!$serverConfig)
{
	echo Json::encode([ 'error' => 'Server not found!' ]);
	die();
}

$serverConfig = ArrayHash::from(Neon::decode($serverConfig));
$data['server'] = $serverConfig->server;

$fileId = $_GET['file'];
if ($service->behaviour->log == "selectable")
{
}
else if ($service->behaviour->log == "preselected")
{
	$fileId = $service->behaviour->logSelection;
}
else if ($service->behaviour->log == "list")
{
}
else if ($service->behaviour->log == "today")
{
}
else if ($service->behaviour->log == "lastXHours")
{
}
else if ($service->behaivour->log == "all")
{
}

if ($serverConfig->webdav->enabled)
{
	$LogClient = new Client([
		'baseUri'   => $serverConfig->webdav->baseUri,
		'userName'  => $serverConfig->webdav->user,
		'password'  => $serverConfig->webdav->pass,
	]);

	//  TODO: Cache
	$data = $LogClient->request('GET', "{$serverConfig->webdav->directoryPath}$fileId");
	if ($data['statusCode'] != 200)
	{
		echo Json::encode([ 'error' => 'Logfile not found!' ]);
		die();
	}

	$log = $data['body'];
	preg_match_all($service->regex->log_entry, $log, $matches);

	$data = [
		'time' => [
			'from' => null,
			'to' => null,
		],
		'events' => [],
	];
	if (count($matches))
	{
		$data['time']['from'] = reset($matches['time']);
		$data['time']['from_numeric'] = strtotime($data['time']['from']) - strtotime('today');
		$data['time']['to'] = end($matches['time']);
		$data['time']['to_numeric'] = strtotime($data['time']['to']) - strtotime('today');
		if ($data['time']['from_numeric'] > $data['time']['to_numeric'])
		{
			// Midnight problem
			$data['time']['to_numeric'] += 24 * 60 * 60;
		}
	}

	$lastEvent_timeNumeric = 0;
	foreach (array_keys($matches[0]) as $entry_id)
	{
		preg_match_all($service->regex->attribute_entry, $matches['attrs'][$entry_id], $attrs);

		$time = $matches['time'][$entry_id];
		$timeNumeric = strtotime($time) - strtotime('today');
		if ($lastEvent_timeNumeric > $timeNumeric)
		{
			// Midnight problem
			$timeNumeric += 24 * 60 * 60;
		}
		$event = $matches['event'][$entry_id];
		$attrs = array_combine($attrs['keys'], $attrs['values']);

		// Whitelist filter
		if ($service->limits->events->whitelist && !in_array($event, (array)$service->limits->events->whitelist))
		{
			continue;
		}

		// Blacklist filter
		if ($service->limits->events->blacklist && in_array($event, (array)$service->limits->events->blacklist))
			continue;

		// Process special attributes (Eg. position)
		foreach ($service->regex->attributes as $key => $regex)
		{
			if (isset($attrs[$key]))
			{
				preg_match($regex, $attrs[$key], $attr_matches);
				$attrs[$key] = [];
				foreach ($attr_matches as $match_key => $match_value)
					if (!is_numeric($match_key))
						$attrs[$key][$match_key] = $match_value;
			}
		}

		// Parse attribute groups
		foreach ($attrs as $key => $value)
		{
			$key_array = explode($service->regex->attribute_group_delimiter, $key);
			if (count($key_array) > 1)
			{
				$key_real = array_splice($key_array, count($key_array) - 1)[0];
				$x = &$attrs;
				foreach ($key_array as $sub_key)
				{
					if (!isset($x[$sub_key]))
						$x[$sub_key] = [];

					$x = &$x[$sub_key];
				}
				$x[$key_real] = $value;
				unset($attrs[$key]);
			}
		}

		$data['events'][] = [
			'event_time' => $time,
			'event_time_numeric' => $timeNumeric,
			'event_type' => $event,
			'event_data' => $attrs,
		];
		$lastEvent_timeNumeric = $timeNumeric;
	}

	echo Json::encode($data/*, Json::PRETTY*/);
	die();
}
