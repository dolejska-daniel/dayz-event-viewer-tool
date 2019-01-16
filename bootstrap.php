<?php

use Nette\Neon\Neon;
use Nette\Utils\ArrayHash;

require_once __DIR__ . '/vendor/autoload.php';

$service = ArrayHash::from(Neon::decode(file_get_contents(__DIR__ . "/config/service.neon")));
