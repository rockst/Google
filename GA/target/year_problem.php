<?php
	include_once(dirname(__FILE__) . '/db_connect.php'); 	// 取得資料庫連線
	include_once(dirname(__FILE__) . "/.library.php");    // 常用函數
	include_once(dirname(__FILE__) . "/.config.php");		// 設定檔 

	for($i = 0; $i < count($GA_Profile); $i++) {
		$site = $GA_Profile[$i]["site"];
		echo "Site: " . $site . "\n";
		Sleep(3);
		foreach($dbh->query("SELECT `user`,`cnt` FROM `ga_data` WHERE `tag` = '201253' and `site` = " . $site . " and `is_done` = 0 ORDER BY `user`") as $row) {
			if(!empty($row)) {
				$user = $row["user"];
				echo "- User: " . $user . " - ";
				$sql = "SELECT sum(cnt) as cnt FROM ga_data WHERE tag in (201253, 201301) and site = " . $site . " and is_done = 0 and user = " . $user;
				$result = $dbh->query($sql)->fetch();
				if(!empty($result)) {
					$cnt = $result["cnt"];
					echo "cnt: " . $cnt;
					$sql = "UPDATE `ga_data` SET `cnt` = :cnt WHERE `tag` = '201301' and `site` = :site and is_done = 0 and `user` = :user";
					$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
					if($sth->execute(array(":cnt" => $cnt, ":site" => $site, ":user" => $user))) {
						echo " - 寫入成功" . "\n";
					} else {
						echo " - 寫入失敗: " . get_db_error_msg($sth) . "\n";
					}
					unset($result);
				}
			}
			unset($row);
		}
	}

?>
