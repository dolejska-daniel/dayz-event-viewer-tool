<?php

require_once __DIR__ . '/../bootstrap.php';

use Nette\Utils\Json;


$data = [
	"DayZ-SA.cz" => [
		"donor" => "DONOR",
	],
];


echo Json::encode($data/*, Json::PRETTY*/);