<?php
	include_once(dirname(__FILE__) . "/../.config.php");
	include_once(dirname(__FILE__) . "/../.library.php");

	// 檢查輸入的參數
	$msg = "說明：php query.php 時間 關鍵字 頁數\n範例：php query.php y1 白紗 1\n時間格式：d1, w1, m1, y1\n";
	if(!isset($argv[1]) || empty($argv[1]) || !preg_match("/^[d|w|m|y][0-9]+/", $argv[1])) exit("請輸入時間\n" . $msg);
	if(!isset($argv[2]) || empty($argv[2])) exit("請輸入關鍵字\n" . $msg);
	if(!isset($argv[3]) || empty($argv[3]) || intval($argv[3]) == 0) exit("請輸入頁數\n" . $msg);

	// 設定 collection 空間
	$MongoColl1 = $MongoDB->gss_xml; // 給 Hub 的資料
	$MongoColl2 = $MongoDB->gss_xml_raw_data; // 保留原生資料
	$MongoColl3 = $MongoDB->log; // 執行的指令資料

	$category = get_category($MongoDB, $argv[2]);

	// 開始執行
	get_xml_gss($argv[2], $argv[2], (($argv[3] - 1) * RESULTLIMIT), $argv[1]);

	function get_xml_gss($category = null, $q, $start = 0, $date = "y1", $url = "") {
		GLOBAL $MongoColl1, $MongoColl2, $MongoColl3;

		if($url == "") {
			// $url = "http://www.google.com/cse?cx=" . CX_PAID . "&client=google-csbe&output=xml_no_dtd&q=\"". $q . "\"&start=" . $start . "&searchtype=image&as_filetype=jpg&imgsz=large&as_qdr=" . $date;
			$url = "http://www.google.com/cse?cx=" . CX_PAID . "&client=google-csbe&output=xml_no_dtd&q=". $q . "&start=" . $start . "&searchtype=image&as_filetype=jpg&imgsz=medium&as_qdr=" . $date;
		} else if(preg_match("/^\/images\?q=/", $url)) {
			$url = "http://www.google.com" . $url;
		}

		echo "開始取得 {$category} - {$q} {$date} 的第 {$start} 筆索引開始的資料\nURL：". $url . "\n";
		insert_mongodb($MongoColl3, array("url"=>$url, "datetime"=>date("YmdHis")));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 3); // times out after 4s
		// curl_setopt($ch, CURLOPT_GET, 1); // set POST method
		// curl_setopt($ch, CURLOPT_POSTFIELDS, "postparam1=postvalue"); // add POST fields
		// submit the xml request and get the response
		$result = curl_exec($ch);
		curl_close($ch);

		// now parse the xml with 
		$xml = simplexml_load_string($result);

		if($xml->RES->M == 0) exit("- 總筆數: 0\n");
		echo "- 總共：" . $xml->RES->M . " 筆\n";

		if(isset($xml->RES->R)) {
			foreach($xml->RES->R as $item) {

				$row = array();
				$row["link"] = (string) $item->U;
				$row["contextLink"] = (string) $item->RU;
				$row["kind"] = "sitesearch#result";

				if($item->attributes()->count() > 0) {
					foreach($item->attributes() AS $name => $attrib) $item->$name = $attrib;
					$row["RK"] = (int) $item->N;
					$row["mime"] = (string) $item->MIME;
				}

				if($item->IMG->attributes()->count() > 0) {
					foreach($item->IMG->attributes() AS $name => $attrib) $item->IMG->$name = $attrib;
					$row["height"] = (int) $item->IMG->HT;
					$row["width"] = (int) $item->IMG->WH;
					$row["byteSize"] = (int) $item->IMG->SZ;
				}

				if($item->TBN->attributes()->count() > 0) {
					foreach($item->TBN->attributes() AS $name => $attrib) $item->TBN->$name = $attrib;
					$row["thumbnailLink"] = (string) $item->TBN->URL; 
					$row["thumbnailHeight"] =  (int) $item->TBN->HT;
					$row["thumbnailWidth"] = (int) $item->TBN->WH;
				}

				$row["title"] = html2text($item->T);
				$row["htmlTitle"] = (string) $item->T;
				$row["snippet"] = html2text($item->S);
				$row["htmlSnippet"] = (string) $item->S;
				$row["timeStamp"] = get_timestamp_date($item->TIMESTAMP);
				if(isset($item->BYLINEDATE) && $item->BYLINEDATE) { 
					$row["bylineDate"] = (int) $item->BYLINEDATE;
					$row["date"] = (string) date("Y/m/d", $row["bylineDate"]);
				} else {
					$row["bylineDate"] = null; 
					$row["date"] = (string) fetch_date($row["link"]);
				}
				$row["category"] = $category;
				$row["tag"] = $q;

				echo "-- Rank: "  . $row["RK"] . "\n";
				echo "--- " . $row["link"] . "\n";
				echo "--- " . $row["contextLink"] . "\n";

				$chk_data = array("link"=>$row["link"], "contextLink"=>$row["contextLink"], "tag"=>$row["tag"]);
				if(!is_exists($MongoColl1, $chk_data)) {
					echo "--- insert1: " . (insert_mongodb($MongoColl1, $row) ? true : false) . "\n";
					$row["json"] = json_encode($item);
					echo "--- insert2: " . (insert_mongodb($MongoColl2, $row) ? true : false) . "\n";
				} else {
					echo "--- 已經有資料了\n";
				}
 			}
		}

		if(isset($xml->RES->NB->NU)) {
			$next = get_xml_next($xml->RES->NB->NU);
			if($next > $start) {
				echo "- 下一頁：" . $next . "\n"; 
				echo "-- url: " . $xml->RES->NB->NU . "\n";
				sleep(1);
				get_xml_gss($category, $q, $next, $date, $xml->RES->NB->NU);
			}
		}
	}
?>
