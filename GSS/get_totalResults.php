<?php
	include_once(dirname(__FILE__) . "/.config.php");
	include_once(dirname(__FILE__) . "/.library.php");

	$cursor1 = $MongoDB->tag->find();
	foreach($cursor1 as $document1) {
		echo $document1["tag"] . ": ";
		$cursor2 = $MongoDB->gss_xml->find(array("tag"=>$document1["tag"]))->count();
		print_r($cursor2);
		echo "\n";
	}
?>
