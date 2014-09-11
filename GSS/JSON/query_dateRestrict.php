<?php
	include_once(dirname(__FILE__) . "/../.config.php");
	include_once(dirname(__FILE__) . "/../.library.php");

	if(!isset($argv[1]) || empty($argv[1]) || !preg_match("/^[d|w|m|y][0-9]+/", $argv[1]))
		exit("請輸入時間\n說明：php query.php 時間 關鍵字 頁數\n範例：php query.php y1 白紗 1\n時間格式：d1, w1, m1, y1\n");
	if(!isset($argv[2]) || empty($argv[2])) 
		exit("請輸入關鍵字\n說明：php query.php 時間 關鍵字 頁數\nexp：php query.php y1 白紗 1\n");
	if(!isset($argv[3]) || empty($argv[3]) || intval($argv[3]) == 0) 
		exit("請輸入頁數\n說明：php query.php 時間 關鍵字 頁數\nexp：php query.php y1 白紗 1\n");

	$dateRestrict = $argv[1];
	$q = $argv[2];
	$start = (($argv[3] - 1) * FREERESULTLIMIT + 1);

	$key = $token[0]["key"]; 

	$url = "https://www.googleapis.com/customsearch/v1?key=". $key . "&cx=" . CX . "&q=\"". $q . "\"&searchType=image&start=" . $start . "&dateRestrict=" . $dateRestrict. "&imgSize=large";
	echo $url . "\n";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$body = curl_exec($ch);
	curl_close($ch);
	$json = json_decode($body);
	set_token_limit($MongoDB, $key);

	print("Queries...\n");
	print_r($json->queries->request);

	$i = 0;
	foreach($json->items as $item) {
		echo ($i + 1) . "- " . $item->link . "\n";
		echo "-- " . $item->image->contextLink . "\n";
		$i++;	
	}

	if(isset($json->queries->nextPage)) {
		print("Next...\n");
		print_r($json->queries->nextPage);
	}
?>
