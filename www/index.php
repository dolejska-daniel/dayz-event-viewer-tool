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

use Nette\Utils\DateTime;

/** @var \Nette\Utils\ArrayHash $map */
/** @var \Nette\Utils\ArrayHash $service */
/** @var \Nette\Http\Request $httpRequest */
require_once __DIR__ . "/../bootstrap.php";

$serverId = $httpRequest->getQuery('server');
$filename = $httpRequest->getQuery('file');

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
