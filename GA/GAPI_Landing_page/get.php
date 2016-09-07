<?php
	include_once(dirname(__FILE__) . "/.config.php");
	include_once(dirname(__FILE__) . "/.library.php");
	include_once(dirname(__FILE__) . "/library/VW_GAPI.class.php"); // Google Analytics PHP Interface

	/* 日期可以不用輸入，預設今天 */
	if(!empty($argv[1]) && !empty($argv[2])) {
		if(!_chkDate($argv[1]) || !_chkDate($argv[2]) || !_compDate($argv[1], $argv[2])) {
			echo <<<EOD
Please Input Argv:
Example: php {$argv[0]} 2012-01-01 2012-02-02 1000
- argv1: 開始日期 YYYY-MM-DD 
- argv2: 結束日期 YYYY-MM-DD 
- argv3: 一次拿到幾筆 \n
EOD;
			exit();
		} else {
			$date1 = $argv[1]; // 從輸入的 YYYY-MM-DD 開始抓資料
			$date2 = $argv[2]; // 從輸入的 YYYY-MM-DD 結束抓資料
		}
	} else { // 預設今天
		$date1 = date("Y-m-d");
		$date2 = date("Y-m-d");
	}
	echo "日期：" . $date1 . " ~ " . $date2 . "\n";
	$limit = (!empty($argv[3]) && intval($argv[3]) > 0) ? intval($argv[3]) : GALIMIT; // argv3: 預設一次拿幾筆資 

	// GAPI Object
	$GA = new VW_GAPI();
	if(is_string($GA->msg) == 1) exit($GA->msg . "\n");

	// 設定維度、指標、排序、過濾條件 $Config from .config.php
	while(list($key, $value) = each($Config)) $$key = $value;

	echo "- 處理 " . $subject . "：\n";
	if(!isset($profile) || !isset($dimensions) || !isset($metrics) || !isset($sort)) { exit("-- 傳入的設定檔錯誤\n");}

	$getDimension = "get" . $dimensions; // 取得指標值的方程式
	$getMetric = "get" . $metrics; // 取得維度的方程式

	$results = array(); // 預設從 GA 拿回來的資料陣列
	$data 	= array(); // 給程式寫回資料庫的資料陣列

	// 取得 GA 傳回來的資料給 $results 
	$msg = $GA->results($results, $profile, $dimensions, $metrics, $sort, $filter, $date1, $date2, 1, $limit);

	if(is_string($msg) && $msg != "") { exit($msg . "\n"); }
	else {
		foreach($results as $result) {
			$uri = $result->$getDimension(); // 取得維度資料
			$pv  = $result->$getMetric(); 	// 取得指標資料
			echo "--- " . $uri . " " . $pv . "\n";
			// if(preg_match("/\/landingpage\/([0-9]+)/i", $uri, $matches) && !empty($matches[1]) && intval($matches[1]) > 0) {
			if(preg_match("/\/magazine\/vo([0-9]+).html/i", $uri, $matches) && !empty($matches[1]) && intval($matches[1]) > 0) {
				$data[$matches[1]]["date"] = $date1;
				// if(preg_match("/vwp_source/i", $uri)) { // 來自 veryWed 流量
				if(preg_match("/from/i", $uri)) { // 來自 veryWed 流量
					$data[$matches[1]]["verywed"] = (isset($data[$matches[1]]["verywed"])) ? $data[$matches[1]]["verywed"] + $pv : $pv; 
				} else { // 來自其他
					$data[$matches[1]]["others"] = (isset($data[$matches[1]]["others"])) ? $data[$matches[1]]["others"] + $pv : $pv; 
				}
			}
		}
		unset($results);
	}
	ksort($data);
	while(list($key, $value) = each($data)) {
		echo $key . ": ";
		if(!empty($value["verywed"])) echo "- verywed: " . $value["verywed"] . " ";
		if(!empty($value["others"]))	echo "- others: " . $value["others"];
		echo "\n";
	}
?>
