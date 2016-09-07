<?php
	include_once(dirname(__FILE__) . "/../.config.php");
	include_once(dirname(__FILE__) . "/../.library.php");

	$MongoColl = $MongoDB->token;
	foreach($token as $data) {
		echo $data["key"] . (($MongoColl->insert($data)) ? true : false) . "\n";

	}
?>
