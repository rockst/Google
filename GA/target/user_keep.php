<?php
	/**
	** 這支程式用來計算每一週轉換成功的婚後族群在幾週後還繼續停留在婚後的停留率
	** Rock Lin 12/26-12
	**/

	include_once(dirname(__FILE__) . "/db_connect.php"); // 取得資料庫連線
	include_once(dirname(__FILE__) . "/.library.php"); // 常用函數

	if(intval($argv[1]) >= 2 && intval($argv[1]) <= 4) {
		$inc_num = intval($argv[1]);
	} else {
		exit("請您重新輸入停留週次(2 ~ 4週) ex. user_keep.php 2\n");
	}

	echo "試算 " . $inc_num . " 週後的停留率\n";

	// 取得目前工作中的所有週次
	$sql = "SELECT `tag` FROM `report` ORDER BY tag";
	$rows = $dbh->query($sql)->fetchall();

	if(count($rows) == 0) {
		exit("目前沒有任何週次資料\n");
	}

	for($i = 0; $i < count($rows); $i++) {

		$tag = intval($rows[$i]["tag"]);
		echo "週次 " . $tag . "\n";

		// 取得該週的轉換次數
		$sql = "SELECT COUNT(1) as 'cnt' FROM `user` WHERE `is_w2f` = 1 and `w2f_tag` = '" . $tag . "'";
		$result = $dbh->query($sql)->fetch();
		$change_cnt = intval($result["cnt"]);
		echo "- 轉換數量：" . $change_cnt . "\n";

		// 取得幾週後的週次資料
		$tags = array();
		foreach($dbh->query("SELECT CONCAT(tag, '-2') as 'tag' FROM report WHERE tag > '" . $tag. "' ORDER BY tag") as $j=>$tmp) {
			if($j >= ($inc_num - 1)) {
				$tags[] = $tmp["tag"];
			}
			$j++;
		}
		// 取得幾週後的停留次數
		if(count($tags) > 0) {
			$inc_weeks = str_replace("-2", "", join(",", $tags));
			$sql = "SELECT COUNT(1) as 'cnt' FROM `user` WHERE `is_w2f` = 1 and `w2f_tag` = '" . $tag . "'";
			$sql.= " AND `history` REGEXP '" . join("|", $tags) . "'";
			$result = $dbh->query($sql)->fetch();
			$keep_cnt = intval($result["cnt"]);
		} else {
			$inc_weeks = "";
			$keep_cnt = 0;
		}
		echo "- 試算週次：" . (($inc_weeks) ? $inc_weeks : "-") . "\n";; 
		echo "- 停留數量：" . $keep_cnt . "\n";

		// 計算停留率
		$keep_rate = ($change_cnt > 0 && $keep_cnt > 0) ? sprintf("%1.2f", (($keep_cnt / $change_cnt) * 100)) : 0;
		echo "- 停留率：" . $keep_rate . "%\n";

		// 寫入資料庫前的準備
		$data_array = array(
			":tag"=>$tag,					// 週次
			":inc_num"=>$inc_num,		// 幾週後
			":inc_weeks"=>$inc_weeks,	// 試算週次
			":change_cnt"=>$change_cnt,// 轉換次數
			":keep_cnt"=>$keep_cnt,		// 停留次數
			":keep_rate"=>$keep_rate	// 停留率
		);

		// 檢查是否有資料
		$sql = "SELECT COUNT(1) as 'cnt' FROM `report_keep` WHERE `tag` = '" . $tag . "' and `inc_num` = '" . $inc_num . "'";
		$result = $dbh->query($sql)->fetch();
		if(!empty($result["cnt"]) && intval($result["cnt"]) > 0) {
			// 修改資料
			$sql = <<<EOD
UPDATE `report_keep` 
SET `inc_weeks` = :inc_weeks,
	 `change_cnt` = :change_cnt,
	 `keep_cnt` = :keep_cnt, 
	 `keep_rate` = :keep_rate
WHERE tag = :tag
and inc_num = :inc_num
EOD;
			$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			echo "- 修改資料" . (($sth->execute($data_array)) ? "成功" :  "失敗: " . get_db_error_msg($sth)) . "\n";
		} else {
			// 新增資料
			$sql = <<<EOD
INSERT INTO `report_keep` 
(`tag`, `inc_num`, `inc_weeks`, `change_cnt`, `keep_cnt`, `keep_rate`)
VALUES
(:tag, :inc_num, :inc_weeks, :change_cnt, :keep_cnt, :keep_rate)
EOD;
			$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			echo "- 新增資料" . (($sth->execute($data_array)) ? "成功" :  "失敗: " . get_db_error_msg($sth)) . "\n";
		}
	}
?>
