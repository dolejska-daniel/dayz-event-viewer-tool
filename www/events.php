<?php

use Nette\Utils\DateTime;
use Nette\Utils\Json;

/** @var \App\Control\EventParser $EventParser */
require_once __DIR__ . '/../bootstrap.php';

try
{
	$events = $EventParser->getEvents($_GET['server'], $_GET['file']);

	$timeFrom = reset($events)['event_time'];
	$timeFilterFrom = null;
	if ($timeFrom)
		$timeFilterFrom = $timeFrom + (300 - $timeFrom % 300);

	$timeTo = end($events)['event_time'];
	$timeFilterTo = null;
	if ($timeTo)
		$timeFilterTo = $timeTo - ($timeTo % 300);

	$timeFilter = [];
	if ($timeFilterFrom && $timeFilterTo)
	{
		$timeFilterKeys = range($timeFilterFrom, $timeFilterTo, 300);
		$timeFilter[$timeFrom - 1] = "First event";
		foreach ($timeFilterKeys as $timestamp)
			$timeFilter[$timestamp] = DateTime::from($timestamp)->format('d-m-Y H:i');
		$timeFilter[$timeTo + 1] = "Last event";
	}

	echo Json::encode([
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
	]/*, Json::PRETTY*/);
}
catch (\Throwable $ex)
{
	echo json_encode([
		'error' => true,
		'message' => $ex->getMessage(),
		'code' => $ex->getCode(),
	]);
}
die();
