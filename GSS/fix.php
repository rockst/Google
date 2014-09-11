<?php
	include_once(dirname(__FILE__) . "/.config.php");
	include_once(dirname(__FILE__) . "/.library.php");

	$MongoColl = $MongoDB->gss_xml;
/*
	$cursor = $MongoColl->find(array("category"=>"風格", "tag"=>"造型"));
	foreach($cursor as $document) {
		print_r($document);
		$data = array("category"=>"元素");
		$MongoColl->update(array("_id" => $document["_id"]), array('$set' => $data));
		$cursor2 = $MongoColl->find(array("_id" => $document["_id"]));
		$document2 = $cursor2->getNext();
		print_r($document2);
	}
*/
/*
	$cursor = $MongoColl->find(array("date"=>"1970/01/01"));
	foreach($cursor as $document) {
		print_r($document);
		$data = array("date"=>fetch_date($document["link"]));
		$MongoColl->update(array("_id" => $document["_id"]), array('$set' => $data));
		$cursor2 = $MongoColl->find(array("_id" => $document["_id"]));
		$document2 = $cursor2->getNext();
		print_r($document2);
	}
*/
/*
	$cursor = $MongoColl->find(array("bylineDate"=>0));
	foreach($cursor as $document) {
		print_r($document);
		$data = array("bylineDate"=>null);
		$MongoColl->update(array("_id" => $document["_id"]), array('$set' => $data));
		$cursor2 = $MongoColl->find(array("_id" => $document["_id"]));
		$document2 = $cursor2->getNext();
		print_r($document2);
	}
*/
/*
	$cursor = $MongoColl->find(array(
		"tag" => new MongoRegex("/[白紗|婚紗照|造型]/"),
		"category" => array('$ne' => "元素")
	))->limit(1);
	foreach($cursor as $document) {
		print_r($document);
		$data = array("category"=>"元素");
		$MongoColl->update(array("_id" => $document["_id"]), array('$set' => $data));
		$cursor2 = $MongoColl->find(array("_id" => $document["_id"]));
		$document2 = $cursor2->getNext();
		print_r($document2);
	}
*/
/*
	$cursor = $MongoColl->find(array(
		"tag" => new MongoRegex("/[海邊|花海|湖邊]/"),
		"category" => array('$ne' => "場景")
	))->limit(1);
	foreach($cursor as $document) {
		print_r($document);
	}
*/
/*
	$cursor = $MongoColl->find(array(
		"tag" => new MongoRegex("/[復古|甜蜜|韓風]/"),
		"category" => array('$ne' => "風格")
	))->limit(1);
	foreach($cursor as $document) {
		print_r($document);
	}
*/
?>
