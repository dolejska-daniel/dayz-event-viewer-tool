<?php

use Nette\Utils\DateTime;

/** @var \Nette\Utils\ArrayHash $map */
/** @var \Nette\Utils\ArrayHash $service */
/** @var \Nette\Http\Request $httpRequest */
require_once __DIR__ . "/../bootstrap.php";

$serverId = $_GET['server'];
$filename = $_GET['file'];

switch ($httpRequest->getQuery('action', 'Front/Home'))
{
	//================================================================dd==
	//  SERVER LIST
	//================================================================dd==
	case 'servers':
	{
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
		break;
	}

	//================================================================dd==
	//  LOG LIST
	//================================================================dd==
	case 'logs':
	{
		try
		{
			$server = $ServerSources->getServer($serverId);
			$server = $server->server;

			$files = [];
			$logFiles = $ServerSources->getLogFiles($serverId);
			foreach ($logFiles as $filepath => $file)
				$files[$filepath] = $file['name'];

			$result = [
				'server' => $server,
				'files' => $files,
			];

			json_setData($result);
		}
		catch (\Throwable $ex)
		{
			json_setError($ex->getMessage(), $ex->getCode());
		}
		json_finish();
		break;
	}

	//================================================================dd==
	//  EVENT LIST
	//================================================================dd==
	case 'events':
	{
		try
		{
			$events = $EventParser->getEvents($serverId, $filename);

			$timeFrom = reset($events)['event_time'];
			$timeFilterFrom = null;
			if ($timeFrom)
				$timeFilterFrom = $timeFrom + ($service->limits->filters->timeIntervals - $timeFrom % $service->limits->filters->timeIntervals);

			$timeTo = end($events)['event_time'];
			$timeFilterTo = null;
			if ($timeTo)
				$timeFilterTo = $timeTo - ($timeTo % $service->limits->filters->timeIntervals);

			$timeFilter = [];
			if ($timeFilterFrom && $timeFilterTo)
			{
				$timeFilterKeys = range($timeFilterFrom, $timeFilterTo, $service->limits->filters->timeIntervals);
				$timeFilter[$timeFrom - 1] = "From first event";
				foreach ($timeFilterKeys as $timestamp)
					$timeFilter[$timestamp] = DateTime::from($timestamp)->format('d-m-Y H:i');
				$timeFilter[$timeTo + 1] = "To last event";
			}

			$result = [
				'time' => [
					'first' => $timeFrom,
					'from'  => $timeFrom
						? DateTime::from($timeFrom)->format('d-m-Y H:i')
						: null,
					'last' => $timeTo,
					'to'   => $timeTo
						? DateTime::from($timeTo)->format('d-m-Y H:i')
						: null,
					'filter' => $timeFilter,
				],
				'events' => $events,
			];

			json_setData($result);
		}
		catch (\Throwable $ex)
		{
			json_setError($ex->getMessage(), $ex->getCode());
		}
		json_finish();
		break;
	}

	//================================================================dd==
	//  ZONE LIST
	//================================================================dd==
	case 'zones':
	{
		try
		{
			$serverConfig = $ServerSources->getServer($serverId);

			$zones = $serverConfig->map->zones;

			$result = [
				'zones' => $zones,
			];

			json_setData($result);
		}
		catch (\Throwable $ex)
		{
			json_setError($ex->getMessage(), $ex->getCode());
		}
		json_finish();
		break;
	}

	//================================================================dd==
	//  RENDER: HOME
	//================================================================dd==
	default:
	case 'Front/Home':
		latte_setView("Front/home", [
			'service' => $service,
			'map' => $map,
		]);
		latte_finish();
}
