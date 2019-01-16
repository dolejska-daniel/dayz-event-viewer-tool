<?php

use Nette\Neon\Neon;
use Nette\Utils\ArrayHash;
use Sabre\DAV\Client;

require_once __DIR__ . '/vendor/autoload.php';

$service = ArrayHash::from(Neon::decode(file_get_contents(__DIR__ . "/config/service.neon")));

/*
$LogClient = new Client([
	'baseUri'   => "http://logs.dayz-sa.cz/webdav/anarchy/",
	'userName'  => "admin",
	'password'  => "QdEgEP9FKvkQ45QN",
	'authType'  => Client::AUTH_DIGEST,
]);
echo "<pre>";
var_dump($LogClient->request("GET"));
die();

/*
$LogClient = new Sabre\DAV\Client([
	'baseUri'   => $service->webdav->baseUri,
	'userName'  => $service->webdav->user,
	'password'  => $service->webdav->pass,
]);

$LogClient->request("GET");

$LogClient->propfind('collection', array(
	'{DAV:}displayname',
	'{DAV:}getcontentlength',
));

var_dump($LogClient->options());
*/
