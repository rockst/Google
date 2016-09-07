<?php
	include_once(dirname(__FILE__) . "/.config.php");
	include_once(dirname(__FILE__) . "/.library.php");
	include_once(dirname(__FILE__) . "/SimpleXMLEX.class.php");

	$MongoColl = $MongoDB->gss_xml;

	$Dom = new DOMDocument("1.0");
	$Dom->preserveWhiteSpace = false;
	$Dom->formatOutput = true;
	$source = XMLROOT . $argv[2];
	$fp = fopen($source, "w");
	fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?><rawfeed version="1.0"></rawfeed>');
	fclose($fp);
	$XML  = new ExSimpleXMLElement($source, null, true);
	$cursor = $MongoColl->find(array("tag"=>$argv[1]))->sort(array("RK" => 1));
	foreach($cursor as $row) {
		$Thread = $XML->addChild("addArticle");
		while(list($key, $value) = each($row)) {
			if(gettype($value) == "array") {
				while(list($key2, $value2) = each($value)) {
						$Thread->addChildCData($key2, $value2);
				}
			} else {
				$Thread->addChildCData($key, $value);
			}
		}
	}
	$Dom->loadXML($XML->asXML());
	$fp = fopen($source, "w");
	fwrite($fp, $Dom->saveXML());
	fclose($fp);
?>
