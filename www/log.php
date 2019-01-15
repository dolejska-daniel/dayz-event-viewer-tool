<?php

require_once __DIR__ . '/../bootstrap.php';

use Nette\Utils\Json;


$filepath = "../logs/{$_GET['file']}";
if (!is_file($filepath))
	echo Json::encode([], Json::PRETTY);

//TODO: Response cache
//filemtime($filepath);
$log = file_get_contents($filepath);
preg_match_all('/(?<time>[0-9\:]{8}) \| DAYZ-SA --> (?<event>[A-Z\_]+):(?<attrs>.+)/', $log, $matches);

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
	preg_match_all('/(\s(?<keys>[a-z0-9]+)=\"(?<values>.+?)\")/', $matches['attrs'][$entry_id], $attrs);

	$time = $matches['time'][$entry_id];
	$event = $matches['event'][$entry_id];
	$attrs = array_combine($attrs['keys'], $attrs['values']);

	if (isset($attrs['position']))
	{
		preg_match('<(?<x>[\-0-9.]+), (?<y>[\-0-9.]+), (?<z>[\-0-9.]+)>', $attrs['position'], $position);
		$attrs['position'] = [
			'x' => $position['x'],
			'y' => $position['y'],
			'z' => $position['z'],
		];
	}

	$data['events'][] = [
		'event_time' => $time,
		'event_time_numeric' => strtotime($time) - strtotime('today'),
		'event_type' => $event,
		'event_data' => $attrs,
	];
}


echo Json::encode($data/*, Json::PRETTY*/);