<?php

/** @var \Nette\Utils\ArrayHash $map */
/** @var \Nette\Utils\ArrayHash $service */
require_once __DIR__ . "/../bootstrap.php";

latte_setView("home", [
	'service' => $service,
	'map' => $map,
]);
latte_finish();
