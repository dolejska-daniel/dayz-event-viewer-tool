<?php

/** @var \Latte\Engine $Latte */
/** @var \Nette\Utils\ArrayHash $map */
/** @var \Nette\Utils\ArrayHash $service */
require_once __DIR__ . "/../bootstrap.php";

try
{
	$Latte->render(TEMPLATEDIR . "/home.latte", [
		'service' => $service,
		'map' => $map,
	]);
}
catch (\Throwable $ex)
{
	echo <<<HTML
<h1>Internal server error occured.</h1>
<p>I am sorry, but something really BAD happend.
I cannot really help you, the only thing I am able to provide you with is this error message:</p>
<code>{$ex->getMessage()}</code>
HTML;
}
