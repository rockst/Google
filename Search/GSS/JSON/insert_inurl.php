<?php
	include_once(dirname(__FILE__) . "/../.config.php");
	include_once(dirname(__FILE__) . "/../.library.php");

	$msg = "說明：php script.php 日期 關鍵字\n範例：php insert_inurl.php 2014/03/01 白紗\n";
	if(!isset($argv[1]) || empty($argv[1])) exit("請輸入日期\n" . $msg);
	if(!isset($argv[2]) || empty($argv[2])) exit("請輸入關鍵字\n" . $msg);

	$MongoColl = $MongoDB->gss_inurl;

	$key = $token[0]["key"]; 
	$q = "inurl:/" . $argv[1] . "+" . $argv[2];
	$category = get_category($MongoDB, $argv[2]);

	echo $q . "\n";

	for($p = 1; $p <= 10; $p++) {

		echo "page: " . $p . "\n";

		$start = (($p - 1) * 10 + 1);
		$url = "https://www.googleapis.com/customsearch/v1?key=". $key . "&cx=" . $cx . "&q=". $q . "&searchType=image&start=" . $start . "&sort=date&c2coff=1";
		echo $url . "\n";
		sleep(1);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$body = curl_exec($ch);
		curl_close($ch);
		$json = json_decode($body);
		set_token_limit($MongoDB, $key);

                if(isset($json->error)) {
			print_r($json->error);
			exit();
		} else {
			if(isset($json->items) && gettype($json->items) == "array" && count($json->items) > 0) {
				$i = 0;
				foreach($json->items as $data) {
					$data->link = preg_replace("/_sml/i", "_big", $data->link);
					echo ($i + 1) . "- " . $data->link . "\n";
					echo "-- " . $data->image->contextLink. "\n";
					$data->date 	= $argv[1];
					$data->category = (($category) ? $category : null);
					$data->tag 	= $argv[2];
					$chk_data = array("link"=>$data->link, "contextLink"=>$data->image->contextLink, "tag"=>$data->tag);
					if(!is_exists($MongoColl, $chk_data)) {
						echo "-- " . insert_mongodb($MongoColl, $data) . "\n";
					} else {
						echo "-- 已經資料了\n";
					}
					$i++;
				}
				if($json->queries->request[0]->count < 10) { 
					exit(); 
				}
			} else {
				print_r($json->error);
				exit();
			}
		}
		sleep(1);
	}
?>
