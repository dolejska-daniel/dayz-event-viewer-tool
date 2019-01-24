<?php

namespace App\Control;


use Nette\Http\Session;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class EventParser
{
	/** @var Session $session */
	public $session;

	/** @var ArrayHash $serviceConfig */
	public $serviceConfig;

	/** @var LogLoader $logLoader */
	protected $logLoader;

	public function __construct( LogLoader $logLoader )
	{
		$this->logLoader = $logLoader;
		$this->serviceConfig = $logLoader->serviceConfig;
		$this->session = $logLoader->session;
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
			// Necessary due to in-game bug
			if (!mb_check_encoding($eventEntry, 'UTF-8'))
				continue;

			$event = [
				'event_time'    => null,
				'event_type'    => null,
				'event_data'    => [],
				'event_tooltip' => null,
			];
			if ($this->serviceConfig->debug)
			{
				$event['debug']['match'] = $eventEntry;
			}

			$timestamp = $matches['timestamp'][$eventId];
			$type = $matches['event'][$eventId];

			preg_match_all($this->serviceConfig->regex->attribute_entry, $matches['attrs'][$eventId], $attrs);
			$attrs = array_combine($attrs['keys'], $attrs['values']);
			$attrs['datetime'] = DateTime::from($timestamp)->format('d-m H:i:s');

			$attrWhitelist = array_flip((array)$this->serviceConfig->limits->attributes->whitelist);
			$attrBlacklist = array_flip((array)$this->serviceConfig->limits->attributes->blacklist);

			foreach ($attrs as $key => $value)
			{
				// Create array of group keys
				$key_array = explode($this->serviceConfig->regex->attribute_group_delimiter, $key);
				$key_real = end($key_array);

				// Attribute whitelist filter
				if ($attrWhitelist
					&& !isset($attrWhitelist[$key_real])
					&& !isset($attrWhitelist[$key]))
				{
					unset($attrs[$key]);
					continue;
				}

				// Attribute blacklist filter
				if ($attrBlacklist
					&& (isset($attrBlacklist[$key_real])
						|| isset($attrBlacklist[$key])))
				{
					unset($attrs[$key]);
					continue;
				}

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


			$event['event_time'] = (int)$timestamp;
			$event['event_type'] = $type;
			$event['event_data'] = $attrs;
			$this->postprocessEvent($events, $event);
			$this->processEventTooltip($event);
			$events[] = $event;
		}

		$typeWhitelist = array_flip((array)$this->serviceConfig->limits->events->whitelist);
		$typeBlacklist = array_flip((array)$this->serviceConfig->limits->events->blacklist);
		foreach ($events as $eventId => $event)
		{
			// Whitelist filter
			if ($typeWhitelist && !isset($typeWhitelist[$event['event_type']]))
				unset($events[$eventId]);

			// Blacklist filter
			if ($typeBlacklist && isset($typeBlacklist[$event['event_type']]))
				unset($events[$eventId]);

			// Steam Account filter
			if ($this->serviceConfig->steam->login->enabled
				&& $this->serviceConfig->steam->login->showOnlyPlayerEvents)
			{
				$steamAccount = $this->session->getSection($this->serviceConfig->steam->login->sessionName);
				if ($steamAccount->id)
				{
					// User is logged in
					if (@$event['event_data']['steamid64'] != $steamAccount->id)
						unset($events[$eventId]);
				}
			}
		}

		return array_values($events);
	}

	protected function postprocessEvent(array &$events, array &$event)
	{
		$data = $event['event_data'];
		if ($event['event_type'] === "KILLED_BY_PLAYER" && isset($data['killer']))
		{
			unset($data['killer']);

			if (isset($event['event_data']['killer']['name']))
				$data['name'] = $event['event_data']['killer']['name'];
			if (isset($event['event_data']['killer']['steamid64']))
				$data['steamid64'] = $event['event_data']['killer']['steamid64'];
			if (isset($event['event_data']['killer']['position']))
				$data['position'] = $event['event_data']['killer']['position'];
			if (isset($event['event_data']['killer']['hands']))
				$data['hands'] = $event['event_data']['killer']['hands'];

			if (isset($event['event_data']['name']))
				$data['victim']['name'] = $event['event_data']['name'];
			if (isset($event['event_data']['steamid64']))
				$data['victim']['steamid64'] = $event['event_data']['steamid64'];
			if (isset($event['event_data']['position']))
				$data['victim']['position'] = $event['event_data']['position'];
			if (isset($event['event_data']['hands']))
				$data['victim']['hands'] = $event['event_data']['hands'];

			$newEvent = [
				'event_time' => $event['event_time'],
				'event_type' => 'KILLED_PLAYER',
				'event_data' => $data,
			];
			$this->processEventTooltip($newEvent);
			$events[] = $newEvent;
		}
	}

	protected function processEventTooltip(&$event )
	{
		$tooltip = null;
		$eventConfig = @$this->serviceConfig->events[$event['event_type']];
		if ($eventConfig)
		{
			$tooltip = $eventConfig->tooltip;
			if (!$tooltip)
				$tooltip = $this->serviceConfig->behaviour->tooltips->default;

			if ($this->serviceConfig->limits->tooltips->enabled
				&& $tooltip)
			{
				if ($this->serviceConfig->behaviour->tooltips->prefix)
					$tooltip = $this->serviceConfig->behaviour->tooltips->prefix . $tooltip;

				$patterns = [];
				$replacements = [];
				preg_match_all($this->serviceConfig->regex->tooltip_variables, $tooltip, $tooltipMatches);

				foreach ($tooltipMatches['variable'] as $var)
				{
					$var_keys = explode('.', $var);
					$x = self::getValueByKeyArray($event['event_data'], $var_keys);

					$patterns[] = '/{' . $var . '}/';
					$replacements[] = $x;
				}
				if ($this->serviceConfig->debug)
				{
					$event['debug']['tooltip']['source'] = $tooltip;
					$event['debug']['tooltip']['match'] = $tooltipMatches['variable'];
					$event['debug']['tooltip']['pattern'] = $patterns;
					$event['debug']['tooltip']['replacement'] = $replacements;
				}
				$tooltip = preg_replace($patterns, $replacements, $tooltip, 1);
			}
		}
		$event['event_tooltip'] = $tooltip;
	}

	static function getValueByKeyArray( $array, $keys, $index = 0 )
	{
		if (isset($array[$keys[$index]]))
		{
			if ($index == count($keys) - 1)
				return $array[$keys[$index]];

			return self::getValueByKeyArray($array[$keys[$index]], $keys, $index + 1);
		}
		return null;
	}
}