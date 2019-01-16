<?php

require_once __DIR__ . '/../bootstrap.php';

use Nette\Utils\Json;
use Nette\Utils\ArrayHash;
use Nette\Neon\Neon;
use Sabre\DAV\Client;


$data = [];

// TODO: preg match validation
$serverId = $_GET['server'];
$serverConfig = file_get_contents(__DIR__ . "/../config/servers/$serverId.neon");
if (!$serverConfig)
	echo Json::encode([ 'error' => 'Server not found!' ]);

$serverConfig = ArrayHash::from(Neon::decode($serverConfig));
$data['server'] = $serverConfig->server;

if ($serverConfig->webdav->enabled)
{
	$LogClient = new Client([
		'baseUri'   => $serverConfig->webdav->baseUri,
		'userName'  => $serverConfig->webdav->user,
		'password'  => $serverConfig->webdav->pass,
	]);

	try
	{
		$files = $LogClient->propfind($serverConfig->webdav->directoryPath, array(
			'{DAV:}displayname',
			//'{DAV:}getcontentlength',
		), 1);
		krsort($files);

		$data['files']["DayZServer_x64.ADM"] = "Current";
		foreach ($files as $filepath => $fileinfo)
		{
			if (strpos($filepath, '.ADM') === false)
				continue;
			if (strpos($filepath, 'DayZServer_x64_') === false)
				continue;

			$filepath = explode('/', $filepath);
			$filename = end($filepath);

			$nameFrom = strpos($filename, '_x64_') + 5;
			$name = substr($filename, $nameFrom, strlen($filename) - $nameFrom - 4);
			$data['files'][$filename] = $name;
		}
	}
	catch (\Exception $ex)
	{
		var_dump($ex);
	}
}

echo Json::encode($data/*, Json::PRETTY*/);