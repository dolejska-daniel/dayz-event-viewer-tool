<?php

namespace App\Control;


use Nette\Utils\ArrayHash;

class EventParser
{
	/** @var ArrayHash $serviceConfig */
	protected $serviceConfig;

	/** @var LogLoader $logLoader */
	protected $logLoader;

	public function __construct( ArrayHash $serviceConfig, LogLoader $logLoader )
	{
		$this->serviceConfig = $serviceConfig;
		$this->logLoader = $logLoader;
	}

	/**
	 * @param null $serverId
	 * @param null $logFile
	 * @return array
	 */
	public function getEvents( $serverId = null, $logFile = null ): array
	{
		$events = [];
		$contents = $this->logLoader->getContents($serverId, $logFile);

		preg_match_all($this->serviceConfig->regex->log_entry, $contents, $matches);

		foreach ($matches[0] as $eventId => $eventEntry)
		{
			$timestamp = $matches['timestamp'][$eventId];
			$type = $matches['event'][$eventId];

			preg_match_all($this->serviceConfig->regex->attribute_entry, $matches['attrs'][$eventId], $attrs);
			$attrs = array_combine($attrs['keys'], $attrs['values']);

			$skipEvent = false;
			foreach ($attrs as $key => $value)
			{
				// Necessary due to in-game bug
				if (!mb_check_encoding($value, 'UTF-8'))
				{
					$skipEvent = true;
					break;
					/*
					$attrs[$key] = "#MALFORMED#";
					continue;
					*/
				}

				// Create array of group keys
				$key_array = explode($this->serviceConfig->regex->attribute_group_delimiter, $key);
				$key_real = end($key_array);

				// Process complex attributes (Eg. position)
				if (isset($this->serviceConfig->regex->attributes[$key_real]))
				{
					$regex = $this->serviceConfig->regex->attributes[$key_real];

					preg_match($regex, $attrs[$key], $attr_matches);
					$attrs[$key] = [];
					foreach ($attr_matches as $match_key => $match_value)
						if (!is_numeric($match_key))
							$attrs[$key][$match_key] = $match_value;
					$value = $attrs[$key];
				}

				// Parse attribute groups
				if (count($key_array) > 1)
				{
					// For each group key, recursively enter/create arrays
					$x = &$attrs;
					foreach ($key_array as $sub_key)
					{
						if (!isset($x[$sub_key]))
							$x[$sub_key] = [];

						$x = &$x[$sub_key];
					}
					$x = $value;
					unset($attrs[$key]);
				}
			}
			if ($skipEvent)
				continue;

			// Whitelist filter
			if ($this->serviceConfig->limits->events->whitelist
				&& !in_array($type, (array)$this->serviceConfig->limits->events->whitelist))
			{
				continue;
			}

			// Blacklist filter
			if ($this->serviceConfig->limits->events->blacklist
				&& in_array($type, (array)$this->serviceConfig->limits->events->blacklist))
				continue;

			$events[] = [
				'event_time' => (int)$timestamp,
				'event_type' => $type,
				'event_data' => $attrs,
			];
		}

		return $events;
	}
}