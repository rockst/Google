<?php
	date_default_timezone_set("Asia/Taipei");
	
	include_once(dirname(__FILE__) . "/../../.gss.php");

	// 定義常數
	Define("DOMAIN", "verywed.com"); // domain
	Define("XMLROOT", dirname(__FILE__) . "/output/"); // 放 XML 實體檔案的位置
	Define("CX", "006096874967814663953:_dusg5n8gow");
	Define("FREERESULTLIMIT" , 10);
	Define("RESULTLIMIT" , 20);
	Define("FREEPAGELIMIT" , 10);

	$Mongo = new MongoClient();
	$MongoDB = $Mongo->vwLink;

	$cx = CX; 
?>
