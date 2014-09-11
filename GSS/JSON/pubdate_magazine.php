<?php
	include_once(dirname(__FILE__) . "/../.config.php");
	include_once(dirname(__FILE__) . "/../.library.php");

	$key = $token[rand(0, (count($token) - 1))]["key"];
	$q = "inurl:/magazine";

	$url = "https://www.googleapis.com/customsearch/v1?key=". $key . "&cx=" . $cx . "&q=\"". $q . "\"&start=1&sort=metatags-pubdate";
	echo $url . "\n";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$body = curl_exec($ch);
	curl_close($ch);
	$json = json_decode($body);

	print_r($json);
?>
