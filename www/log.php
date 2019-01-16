<?php

require_once __DIR__ . '/../bootstrap.php';

use Nette\Utils\Json;
use Nette\Utils\ArrayHash;
use Nette\Neon\Neon;
use Sabre\DAV\Client;


// TODO: preg match validation
$serverId = $_GET['server'];
$serverConfig = file_get_contents(__DIR__ . "/../config/servers/$serverId.neon");
if (!$serverConfig)
{
	echo Json::encode([ 'error' => 'Server not found!' ]);
	die();
}

$serverConfig = ArrayHash::from(Neon::decode($serverConfig));
$data['server'] = $serverConfig->server;

if ($serverConfig->webdav->enabled)
{
	$LogClient = new Client([
		'baseUri'   => $serverConfig->webdav->baseUri,
		'userName'  => $serverConfig->webdav->user,
		'password'  => $serverConfig->webdav->pass,
	]);

	//  TODO: Cache
	$data = $LogClient->request('GET', "{$serverConfig->webdav->directoryPath}$_GET[file]");
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
	}

	foreach (array_keys($matches[0]) as $entry_id)
	{
		preg_match_all($service->regex->attribute_entry, $matches['attrs'][$entry_id], $attrs);

		$time = $matches['time'][$entry_id];
		$event = $matches['event'][$entry_id];
		$attrs = array_combine($attrs['keys'], $attrs['values']);

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
			'event_time_numeric' => strtotime($time) - strtotime('today'),
			'event_type' => $event,
			'event_data' => $attrs,
		];
	}

	echo Json::encode($data/*, Json::PRETTY*/);
	die();
}
