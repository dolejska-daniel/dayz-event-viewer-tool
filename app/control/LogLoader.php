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

namespace App\Control;


use Nette\Http\Session;
use Nette\Utils\ArrayHash;

class LogLoader
{
	/** @var Session $session */
	public $session;

	/** @var ArrayHash $serviceConfig */
	public $serviceConfig;

	/** @var ServerSources $serverSources */
	protected $serverSources;

	public function __construct( ServerSources $serverSources )
	{
		$this->serverSources = $serverSources;
		$this->serviceConfig = $serverSources->serviceConfig;
		$this->session = $serverSources->session;
	}

	protected function loadContents( $serverId = null, $logFile = null ): string
	{
		// Select servers from which will log files be taken from
		$serverIds = [];
		switch ($this->serviceConfig->behaviour->server)
		{
			case 'all':
			case 'preselected':
			case 'group':
				foreach ($this->serverSources->getServers() as $serverGroup => $servers)
					$serverIds = array_merge($serverIds, array_keys($servers));
				break;
			case 'selectable':
			default:
				$serverIds[] = $serverId;
				break;
		}

		// Select logs from which will events be parsed
		$logs = [];
		$timestamp = null;
		foreach ($serverIds as $serverId)
		{
			$serverFiles = $this->serverSources->getLogFiles($serverId);
			switch ($this->serviceConfig->behaviour->log)
			{
				case 'preselected':
				{
					$selection = $this->serviceConfig->behaviour->logSelection;
					if (is_object($selection))
						$selection = (array)$selection;
					else
						$selection = [$selection];

					foreach ($selection as $filename)
					{
						if (!isset($serverFiles[$filename]))
							continue;

						$file = $serverFiles[$filename];
						$logs[] = $this->serverSources->loadLogFile($serverId, $file['path']);
					}
					break;
				}

				case 'all':
				{
					foreach ($serverFiles as $file)
					{
						$logs[] = $this->serverSources->loadLogFile($serverId, $file['path']);
					}
					break;
				}

				case 'today':
				{
					$timestamp = strtotime('today');
					foreach ($serverFiles as $file)
					{
						if ($file['datetime']->getTimestamp() < $timestamp)
							continue;

						$logs[] = $this->serverSources->loadLogFile($serverId, $file['path']);
					}
					break;
				}

				case 'time':
				{
					$timestamp = strtotime("-{$this->serviceConfig->behaviour->logSelection}");
					foreach ($serverFiles as $file)
					{
						if ($file['datetime']->getTimestamp() < $timestamp)
							continue;

						$logs[] = $this->serverSources->loadLogFile($serverId, $file['path']);
					}
					break;
				}

				case 'selectable':
				default:
				{
					$file = $serverFiles[$logFile];
					$logs[] = $this->serverSources->loadLogFile($serverId, $file['path']);
					break;
				}
			}
		}

		return $this->mergeContents($logs, $timestamp);
	}

	protected function mergeContents( array $logs, $timestampFrom = null ): string
	{
		$timestampEntries = [];
		foreach ($logs as $log)
		{
			preg_match_all('/(?<timestamp>[0-9]{10}) \|.+/', $log, $matches);
			foreach ($matches['timestamp'] as $index => $timestamp)
			{
				if ($timestampFrom && $timestamp < $timestampFrom)
					continue;
				$timestampEntries[$timestamp][] = $matches[0][$index];
			}
		}
		ksort($timestampEntries);

		$result = "";
		foreach ($timestampEntries as $timestamp => $entries)
			foreach ($entries as $entry)
				$result.= "$entry\n";
		return $result;
	}

	public function getContents( $serverId = null, $logFile = null ): string
	{
		$contents = $this->loadContents($serverId, $logFile);
		return $contents;
	}
}