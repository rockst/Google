<?php

	include_once(dirname(__FILE__) . "/db_connect.php"); // 取得資料庫連線
	include_once(dirname(__FILE__) . "/.library.php"); // 常用函數

	// 取得現在是第幾週 
	$tw = intval(get_today_week());
	if($tw == 0) { exit("本週錯誤\n"); }
	echo "現在週次：" . $tw . " 週\n";

	// 算出從上一週開始
	$bw = intval(get_befor_week(substr($tw, 0, 4), substr($tw, 4, 2), 1));
	if($bw == 0) { exit("上一週錯誤\n"); }
	echo "開始計算：" . $bw . " 週的轉換率\n";

	$wed_cnt = 0; // 婚前族群數量
	$w2f_cnt = 0; // 婚前轉婚後族群數量
	$fam_cnt = 0; // 婚後族群數量
	$f2w_cnt = 0; // 婚後轉婚前族群數量

	// 取得上一週開始的不重覆會員資料
	foreach($dbh->query("SELECT DISTINCT `user`, `status` FROM `log` WHERE `tag` = '" . $bw . "' ORDER BY `user`") as $i=>$row) {
		if(!empty($row["user"]) && intval($row["user"]) > 0) {

			$user 	= intval($row["user"]);		// 會員主鍵
			$status 	= intval($row["status"]);	// 當週狀態

			echo "會員：" . $user . "\n";
			echo "- " . $bw . " 週的狀態：" . (($status == 1) ? "婚前" : (($status == 2) ? "婚後" : "")) . "\n";

			// 加總婚前或婚後族群數量
			($status == 1) ? $wed_cnt++ : (($status == 2) ? $fam_cnt++ : NULL);

			// 取得該會員上一次瀏覽的週次與狀態
			$sql = "SELECT tag, status FROM `log` WHERE `user` = '" . $user . "' and tag < '" . $bw . "' ORDER BY tag DESC LIMIT 1";
			$result = $dbh->query($sql)->fetch();
			if(!empty($result)) {
				echo "- 上一次瀏覽週次：" . $result["tag"] . " 狀態：" . (($result["status"] == 1) ? "婚前" : (($result["status"] == 2) ? "婚後" : "")) . "\n";
			} else {
				echo "- 上一次沒有來\n";
			}

			$is_t = 0;
			if($status == 2) { // 目前是婚後
				if(!empty($result["tag"]) && !empty($result["status"]) && $result["status"] == 1) { // 上一次瀏覽狀態是婚前
					$w2f_cnt++;
					$is_t = 1; // 婚前轉換為婚後成功
					$new_status = 2;
					$tag = intval($result["tag"]);
				} else if(!empty($result["tag"]) && !empty($result["status"]) && $result["status"] == 2) { // 上一次瀏覽狀態是婚後
					$new_status = 2;
					$tag = $bw;
				} else { // 沒有來過
					$new_status = $status;
					$tag = $bw;
				}
			} else if($status == 1) { // 目前是婚前
				if(!empty($result["tag"]) && !empty($result["status"]) && $result["status"] == 1) { // 上一次瀏覽狀態是婚前
					$new_status = 1;
					$tag = $bw;
				} else if(!empty($result["tag"]) && !empty($result["status"]) && $result["status"] == 2) { // 上一次瀏覽狀態是婚後
					$f2w_cnt++;
					$is_t = 1; // 婚後轉換婚前成功
					$new_status = 1;
					$tag = intval($result["tag"]);
				} else { // 沒有來過
					$new_status = $status;
					$tag = $bw;
				}
			}

			echo "- 是否從婚前轉換為婚後：" . (($is_t == 1 && $new_status == 2) ? "是" : "否") . "\n";
			echo "- 是否從婚後轉換為婚前：" . (($is_t == 1 && $new_status == 1) ? "是" : "否") . "\n";
			echo "- 最新的狀態：" . (($new_status == 1) ? "婚前" : (($new_status == 2) ? "婚後" : "")) . "\n";

			// 寫入資料庫的準備
			$data_array = array(
				":user"=>$user, 																// 會員主鍵 
				":status"=>$new_status, 													// 族群狀態
				":history"=>$bw . "-" . $new_status, 									// 歷史記錄，格式 YYYYWW-族群狀態 
				":is_w2f"=>(($is_t == 1 && $new_status == 2) ? 1 : 0), 			// 是否從婚前轉換為婚後
				":w2f_tag"=>(($is_t == 1 && $new_status == 2) ? $bw : NULL), 	// 從婚前轉換為婚後的週次
				":is_f2w"=>(($is_t == 1 && $new_status == 1) ? 1 : 0), 			// 是否從婚後轉換為婚前
				":f2w_tag"=>(($is_t == 1 && $new_status == 1) ? $bw : NULL) 	// 從婚後轉換為婚前的週次
			);

			// 取得該會員的使用者資料，用來前置修改和判斷是否新增新使用者資料
			$sql = "SELECT `history`, `is_w2f`, `w2f_tag`, `is_f2w`, `f2w_tag` FROM `user` WHERE `user` = '" . $user . "'";
			$result = $dbh->query($sql)->fetch();
			if(!empty($result)) { // 修改
				// 累加歷史記錄
				$data_array[":history"] = join(",", array_unique(explode(",", $result["history"] . "," . $bw . "-" . $new_status)));
				// 保留曾經從婚前轉換到婚後的紀錄
				if($result["is_w2f"] == 1 && $data_array[":is_w2f"] == 0) {
					$data_array[":is_w2f"]  = $result["is_w2f"];
					$data_array[":w2f_tag"] = $result["w2f_tag"];
				}
				// 保留曾經從婚後轉換到婚前的紀錄
				if($result["is_f2w"] == 1 && $data_array[":is_f2w"] == 0) {
					$data_array[":is_f2w"]  = $result["is_f2w"];
					$data_array[":f2w_tag"] = $result["f2w_tag"];
				}
				// 修改使用者資料
				$sql = <<<EOD
UPDATE `user`
SET `status` = :status,
	 `history` = :history,
	 `is_w2f` = :is_w2f,
	 `w2f_tag` = :w2f_tag,
	 `is_f2w` = :is_f2w,
	 `f2w_tag` = :f2w_tag
WHERE `user` = :user
EOD;
				$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
				echo "- 修改資料" . (($sth->execute($data_array)) ? "成功" :  "失敗: " . get_db_error_msg($sth)) . "\n";
			} else {
				// 新增使用者資料
				$sql = <<<EOD
INSERT INTO `user` 
(`user`, `status`, `history`, `is_w2f`, `w2f_tag`, `is_f2w`, `f2w_tag`) 
VALUES 
(:user, :status, :history, :is_w2f, :w2f_tag, :is_f2w, :f2w_tag)
EOD;
				$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
				echo "- 寫入資料" . (($sth->execute($data_array)) ? "成功" :  "失敗: " . get_db_error_msg($sth)) . "\n";
			}
		}
		echo "\n";
	}

	echo "總數: " . ($i + 1) . "\n";

	$w2f_rate = ($w2f_cnt > 0 && $wed_cnt > 0) ? sprintf("%01.2f", (($w2f_cnt / $wed_cnt) * 100)) : 0;
	$f2w_rate = ($f2w_cnt > 0 && $fam_cnt > 0) ? sprintf("%01.2f", (($f2w_cnt / $fam_cnt) * 100)) : 0;

	echo "婚前族群數量: " . $wed_cnt . "\n";
	echo "婚前轉婚後數量: " . $w2f_cnt . "\n";
	echo "婚前轉婚後轉換率: " . $w2f_rate . "%\n";

	echo "婚後族群數量: " . $fam_cnt . "\n";
	echo "婚後轉婚前數量: " . $f2w_cnt . "\n";
	echo "婚後轉婚前轉換率: " . $f2w_rate . "%\n";

	// 準備寫入報表
	$data_array = array(
		":tag"=>$bw, 
		":w2f_rate"=>$w2f_rate,
		":f2w_rate" =>$f2w_rate
	);
	$sql = "SELECT COUNT(1) as 'cnt' FROM `report` WHERE `tag` = '" . $bw . "'";
	$result = $dbh->query($sql)->fetch();
	if(!empty($result["cnt"])) {
		$sql = "UPDATE `report` SET `w2f_rate` = :w2f_rate, `f2w_rate` = :f2w_rate WHERE `tag` = :tag";
		$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		echo "- 修改資料" . (($sth->execute($data_array)) ? "成功" :  "失敗: " . get_db_error_msg($sth)) . "\n";
	} else {
		$sql = "INSERT INTO `report` (`tag`, `w2f_rate`, `f2w_rate`) VALUES (:tag, :w2f_rate, :f2w_rate)";
		$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		echo "- 新增資料" . (($sth->execute($data_array)) ? "成功" :  "失敗: " . get_db_error_msg($sth)) . "\n";
	}
?>
