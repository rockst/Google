<?php

	/**
	** 這支程式主要的用途是從 GA 取得資料
	** 您必須透過維度與指標的組合
	** Rock Lin 12/27-12
	**/

	include_once(dirname(__FILE__) . "/.account.php"); // 設定 Google Analytics 帳號與密碼
	include_once(dirname(__FILE__) . "/.config.php"); // 設定 Google Analytics 網站設定檔
	include_once(dirname(__FILE__) . "/.library.php"); // 引用常用的函式
	include_once(dirname(__FILE__) . "/library/gapi.class.php"); // Google Analytics PHP Interface
	include_once(dirname(__FILE__) . "/db_connect.php"); // 取得資料庫連線

	/* 可以不用輸入，預設拿取上週資料 */
	if(!empty($argv[1]) && !empty($argv[2])) {
		if(!_chkDate($argv[1]) || !_chkDate($argv[2]) || !_compDate($argv[1], $argv[2])) {
			echo <<<EOD
Please Input Argv:
Example: ga.php 2012-01-01 2012-02-02 1000
- argv1: 開始日期 YYYY-MM-DD 
- argv2: 結束日期 YYYY-MM-DD 
- argv3: 一次拿到幾筆 \n
EOD;
			exit();
		} else {
			$date1 = $argv[1]; // 從輸入的 YYYY-MM-DD 開始抓資料
			$date2 = $argv[2]; // 從輸入的 YYYY-MM-DD 結束抓資料
		}
	} else { // 預設上週開始抓
		list($date1, $date2) = get_befor_week_date(); // 取得上週日期
		// 檢查日期格式
		if(!_chkDate($date1) || !_chkDate($date2) || !_compDate($date1, $date2)) {
			exit();
		}
	}
	echo "日期：" . $date1 . " ~ " . $date2 . "\n";

	// argv3: 預設一次拿幾筆資 
	$limit = (!empty($argv[3]) && intval($argv[3]) > 0) ? intval($argv[3]) : LIMIT;

	// GAPI Object
	$ga = new gapi(USER, PAWD);
 
 	// 設定維度、指標、排序、過濾條件
	$dimensions = array("customVarValue2", "week");
	$metrics    = array("pageviews");
	$sort 		= array("customVarValue2");

	// prepare insert into ga_data sql
	$sql = "INSERT INTO `ga_data` (`id`, `site`, `tag`, `user`, `cnt`) VALUES (0, :site, :tag, :user, :cnt)";
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

	for($i = 0; $i < count($GA_Profile); $i++) 
	{
		$item = $GA_Profile[$i]; 								// 網站設定檔
		$ga_id = $item["id"]; 									// GA ID
		$ga_name = $item["name"]; 								// 自定變數名稱
		$site = $item["site"]; 									// 1 婚前 2 婚後 
		$offset = 1; 												// 從第一個位置開始抓資料
		$filter = "ga:customVarName2 == " . $ga_name; 	// 過濾條件 

		echo "- 開始從 GA 抓 " . $ga_name . " 的資料\n";
		sleep(1);

		// 預設從 GA 拿回來的變數
		$totalCount = NULL; 		// 總筆數
		$gaResults = array(); 	// GA 資料陣列

		// 遞迴使用
		$cnt = 0;
		$counter = 0;
		$result_cnt = 0;

		// 取得 GA Server 傳回來的資料給 $gaResults 
		getGAResults($ga, $totalCount, $gaResults, $ga_id, $dimensions, $metrics, $sort, $filter, $date1, $date2, $offset, $limit);

		if($totalCount > 0 && count($gaResults) > 0) { // 有資料
			$j = 1;
			echo "結果：\n";
			foreach($gaResults as $result) {
				// 寫入資料庫
				$tag 	= substr($date1, 0, 4) . $result->getWeek(); // 週次 YYYYWW
				$user = intval($result->getCustomVarValue2());		// 會員主鍵
				$cnt 	= intval($result->getPageviews());				// 瀏覽頁數
				if(preg_match("/^[0-9]{6}$/", $tag) && $user > 0 && $cnt > 0) {
					$status = "寫入資料庫" . ($sth->execute(array(":site" => $site, ":tag" => $tag, ":user" => $user, ":cnt" => $cnt)) ? "成功" : "失敗");
				} else {
					$status = "格式錯誤 tag:" . $tag . " user:" . $user . " cnt:" . $cnt;
				}
				printf("%-4d %8d %8d %5d %10s\n", $j++, $user, $tag, $cnt, $status);
			}
			echo "\n-----------------------------------------\n";
			echo "總筆數 : " . $totalCount . "\n";    
			sleep(3);
		} else {
			echo "GA 沒有任何資料\n";
		}
	}

?>
