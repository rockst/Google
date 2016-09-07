<?php

	/**
	** 這支程式主要的工作是負責消化 ga_data 中未處理的資料 (is_done = 0)
	** 並且計算不重覆會員裡的瀏覽週數中該週所決定是婚前還是或婚後狀態
	** Rock Lin 12/26-12
	**/

	include_once(dirname(__FILE__) . "/db_connect.php"); 	// 取得資料庫連線
	include_once(dirname(__FILE__) . "/.library.php"); 	// 常用函數

	// 從資料庫中的 Google Analytics 取回來的資料取出來開始判斷會員身份
	foreach($dbh->query("SELECT DISTINCT `user` FROM `ga_data` WHERE `is_done` = 0 ORDER BY `user`") as $i=>$row) {
		if(!empty($row["user"]) && intval($row["user"]) > 0) {

			$user = intval($row["user"]);
			echo "會員: " . $user . "\n";

			// 取得某會員曾經參訪過的不重覆週次
			foreach($dbh->query("SELECT DISTINCT `tag` FROM `ga_data` WHERE `user` = '" . $user . "' and `is_done` = 0 ORDER BY `tag`") as $row) {
				if(!empty($row["tag"]) && intval($row["tag"]) > 0) {

					$tag = intval($row["tag"]);
					echo "- 週次: " . $tag . "\n";

					// 加總某會員在某週瀏覽的總頁數
					$sql = "SELECT SUM(`cnt`) AS 'cnt' FROM `ga_data` WHERE `is_done` = 0 and `user` = :user and `tag` = :tag and `site` = :site";
					$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

					// 婚前(site=1)的總頁數
					$sth->execute(array(":user" => $user, ":tag" => $tag, ":site" => 1));
					$result = $sth->fetch();
					$wed_cnt = intval($result["cnt"]);
					echo "-- 婚前瀏覽頁數: " . $wed_cnt . "\n";

					// 婚後(site=2)的總頁數
					$sth->execute(array(":user" => $user, ":tag" => $tag, ":site" => 2));
					$result = $sth->fetch();
					$fam_cnt = intval($result["cnt"]);
					echo "-- 婚後瀏覽頁數: " . $fam_cnt . "\n";

					// 決定該會員在本週所屬哪一個族群 1 = 婚前; 2 = 婚後
					$status = ($wed_cnt > $fam_cnt) ? 1 : 2;
					echo "-- 該週所屬族群: " . (($status == 1) ? "婚前" : "婚後") . "\n";

					// 將結果新增到資料庫
					$sql = "INSERT INTO `log` (`id`, `tag`, `user`, `status`) VALUES (0, :tag, :user, :status)";
					$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
					if($sth->execute(array(":tag" => $tag, ":user" => $user, ":status" => $status))) {
						echo "-- 寫入 log 成功\n";
						// 設定該筆資料已經處理過
						$sql = "UPDATE `ga_data` SET `is_done` = 1 WHERE `tag` = :tag and `user` = :user";
						$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
						echo "-- 處理 ga_data is_done " . (($sth->execute(array(":tag" => $tag, ":user" => $user))) ? "成功" : "失敗: " . get_db_error_msg($sth)) . "\n";
					} else {
						echo "-- 寫入 log 失敗: " . get_db_error_msg($sth) . "\n"; 
					}
				} else {
					echo "- 沒有任何週次資料需要被處理\n";
					break;
				}
			}
		}
	}
?>
