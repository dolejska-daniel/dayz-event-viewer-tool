<?php

namespace App\Control;


use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class LogLoader
{
	/** @var ArrayHash $serviceConfig */
	protected $serviceConfig;

	/** @var ServerSources $serverSources */
	protected $serverSources;

	public function __construct( ArrayHash $serviceConfig, ServerSources $serverSources )
	{
		$this->serviceConfig = $serviceConfig;
		$this->serverSources = $serverSources;
	}

	protected function loadContents( $serverId = null, $logFile = null ): string
	{
		// Select servers from which will log files be taken from
		$serverIds = [];
		switch ($this->serviceConfig->behaviour->server)
		{
			case 'preselected':
				$serverIds[] = $this->serviceConfig->behaviour->serverSelection;
				break;

			case 'list':
				$serverIds = array_values($this->serviceConfig->behaviour->serverSelection);
				break;

			case 'all':
				foreach ($this->serverSources->getServers() as $serverGroup => $servers)
					$serverIds = array_merge($serverIds, array_keys($servers));
				break;

			case 'selectable':
			case 'group':
			default:
				$serverIds[] = $serverId;
				break;
		}

		// Select logs from which will events be parsed
		$logs = [];
		foreach ($serverIds as $serverId)
		{
			$serverFiles = $this->serverSources->getLogFiles($serverId);
			switch ($this->serviceConfig->behaviour->log)
			{
				case 'preselected':
				{
					if (!isset($serverFiles[$this->serviceConfig->behaviour->logSelection]))
						continue;

					$file = $serverFiles[$this->serviceConfig->behaviour->logSelection];
					$logs[] = $this->serverSources->loadLogFile($serverId, $file['path']);
					break;
				}

				case 'list':
				{
					foreach ($this->serviceConfig->behaviour->logSelection as $filename)
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
					$today = strtotime('today');
					foreach ($serverFiles as $file)
					{
						if ($file['datetime']->getTimestamp() < $today)
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

		return $this->mergeContents($logs);
	}

	protected function mergeContents( array $logs ): string
	{
		if (count($logs) == 0)
			return "";
		if (count($logs) == 1)
			return end($logs);

		$timestampEntries = [];
		foreach ($logs as $log)
		{
			preg_match_all('/(?<timestamp>[0-9]{10}) \|.+/', $log, $matches);
			foreach ($matches['timestamp'] as $index => $timestamp)
			{
				$timestampEntries[$timestamp][] = $matches[0][$index];
			}
		}

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