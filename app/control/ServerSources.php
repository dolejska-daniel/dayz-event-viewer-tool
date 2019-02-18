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
use Nette\Neon\Neon;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Sabre\DAV\Client;

class ServerSources
{
	/** @var Session $session */
	public $session;

	/** @var ArrayHash $serviceConfig */
	public $serviceConfig;

	/** @var array $serverData */
	protected $serverData;

	/** @var array $serverGroups */
	protected $serverGroups;

	/** @var array $logData */
	protected $logData;

	public function __construct( Session $session, ArrayHash $serviceConfig )
	{
		$this->serviceConfig = $serviceConfig;
		$this->session = $session;

		$this->loadServers();
	}

	/**
	 * Loads and processes server configs based on service configuration.
	 */
	protected function loadServers()
	{
		// Select and find server configs
		$serverConfigPaths = [];
		switch ($this->serviceConfig->behaviour->server)
		{
			case 'preselected':
			{
				$selection = $this->serviceConfig->behaviour->serverSelection;
				if (is_object($selection))
					$selection = (array)$selection;
				else
					$selection = [$selection];

				foreach ($selection as $serverId)
					$serverConfigPaths[] = CFGDIR . "/servers/{$serverId}.neon";
				break;
			}

			case 'all':
				// Logs will be aggregated when loaded
			case 'selectable':
			case 'group':
				// Servers will be filtered when loaded
			default:
			{
				$serverConfigPaths = glob(CFGDIR . "/servers/*.neon");
				break;
			}
		}

		// Process server config files
		foreach ($serverConfigPaths as $path)
		{
			// Load config
			/** @var object $config */
			$config = ArrayHash::from(Neon::decode(file_get_contents($path)));

			// Group filter
			if ($this->serviceConfig->behaviour->server == "group")
				if ($config->server->group != $this->serviceConfig->behaviour->serverSelection)
					continue;

			// String array key fix
			if ($config->map->zones)
			{
				foreach ($config->map->zones as $zone)
				{
					$bounds = $zone->bounds;
					$zone->bounds = [];
					foreach ($bounds as $bound)
						$zone->bounds[] = array_values((array)$bound);
				}
			}

			// Save processed servers
			$this->serverData[$config->server->group][$config->server->id] = $config;
			$this->serverGroups[$config->server->id] = $config->server->group;
		}
	}

	/**
	 * For each loaded server configuration tries to load its log files.
	 */
	protected function loadLogFiles()
	{
		foreach ($this->serverData as $serverGroup => $servers)
		{
			foreach ($servers as $serverId => $server)
			{
				// TODO: Implement caching for logfiel list

				if ($server->webdav->enabled)
				{
					// WebDAV connection is enabled

					// Initialize WebDAV client
					$server->webdav->client = $webdavClient = new Client([
						'baseUri'   => $server->webdav->baseUri,
						'userName'  => $server->webdav->user,
						'password'  => $server->webdav->pass,
					]);

					// List existing log files
					$files = $webdavClient->propfind($server->webdav->directoryPath, array(
						'{DAV:}displayname',
						//'{DAV:}getcontentlength',
					), 1);

					// Reverse sorting by key
					krsort($files);

					// Process file list
					$this->logData[$serverId]["DayZServer_x64.ADM"] = [
						"name" => "Current",
						"path" => "{$server->webdav->directoryPath}DayZServer_x64.ADM",
						"datetime" => DateTime::from(strtotime("now")),
					];
					foreach ($files as $filepath => $fileinfo)
					{
						if (substr($filepath, -4) !== '.ADM')
							continue;
						if (strpos($filepath, 'DayZServer_x64_') === false)
							continue;

						preg_match('/DayZServer_x64_(?<Y>[0-9]{4})_(?<m>[0-9]{2})_(?<d>[0-9]{2})_(?<H>[0-9]{2})(?<i>[0-9]{2})(?<s>[0-9]{2})(?<v>[0-9]{3}).ADM/', $filepath, $m);
						$name = "$m[d]-$m[m]-$m[Y] $m[H]:$m[i]:$m[s].$m[v]";
						$datetime = new DateTime("$m[Y]-$m[m]-$m[d] $m[H]:$m[i]:$m[s].$m[v]");

						// Save processed log file
						$this->logData[$serverId][$m[0]] = [
							"name" => $name,
							"path" => $filepath,
							"datetime" => $datetime,
						];
					}
				}
			}
		}
	}

	protected function preprocessLogFile( $contents ): string
	{
		preg_match('/AdminLog started on (?<date>[0-9\-]{10}) at (?<time>[0-9\:]{8})/', $contents, $matches);
		$lastDatetime = new DateTime("$matches[date] $matches[time]");

		$newContents = preg_replace_callback('/(?<time>[0-9\:]{8})(?<rest> \| \[DAYZ-SA\])/', function (array $matches) use (&$lastDatetime) {
			$datetime = new DateTime("{$lastDatetime->format('Y-m-d')} $matches[time]");
			if ($datetime->getTimestamp() < $lastDatetime->getTimestamp())
				$datetime->add(date_interval_create_from_date_string('1 day'));

			$lastDatetime = $datetime;
			return $datetime->getTimestamp() . $matches['rest'];
		}, $contents);

		return $newContents;
	}

	/**
	 * Tries to downloads given file from given server.
	 *
	 * @param $serverId
	 * @param $filepath
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function loadLogFile( $serverId, $filepath ): string
	{
		if (!isset($this->serverGroups[$serverId]))
			return null;

		$cache = true;
		$filepathCached = null;
		if (CACHEDIR)
		{
			foreach ($this->serviceConfig->behaviour->dontCache as $regex)
			{
				if (preg_match($regex, $filepath))
				{
					$cache = false;
					break;
				}
			}

			if ($cache)
			{
				$filepathCached = CACHEDIR . "/$serverId/" . md5($filepath);
				if (is_file($filepathCached) && filemtime($filepathCached) > strtotime("-{$this->serviceConfig->behaviour->cacheInterval}"))
					return file_get_contents($filepathCached);
			}
		}

		$server = $this->serverData[$this->serverGroups[$serverId]][$serverId];
		if ($server->webdav->enabled && $server->webdav->client)
		{
			/** @var Client $webdavClient */
			$webdavClient = $server->webdav->client;
			$data = $webdavClient->request('GET', $filepath);
			if ($data['statusCode'] != 200)
				throw new \Exception("Failed to download requested file.");

			$contents = $this->preprocessLogFile($data['body']);
			if ($cache && $filepathCached)
			{
				@mkdir(dirname($filepathCached), 0755, true);
				file_put_contents($filepathCached, $contents);
			}
			return $contents;
		}

		return null;
	}

	public function getServers(): array
	{
		return $this->serverData;
	}

	public function getServer($serverId): ArrayHash
	{
		if (!isset($this->serverGroups[$serverId]))
			return null;

		return $this->serverData[$this->serverGroups[$serverId]][$serverId];
	}

	public function getServerList(): array
	{
		$list = [];
		foreach ($this->serverData as $serverGroup => $servers)
		{
			foreach ($servers as $serverId => $serverConfig)
			{
				$list[$serverGroup][$serverId] = $serverConfig->server->name;
			}
		}
		return $list;
	}

	public function getLogFiles( $serverId = null ): array
	{
		if (!$this->logData)
			$this->loadLogFiles();

		$result = $this->logData;
		if ($serverId)
			$result = $result[$serverId];

		return $result;
	}
}