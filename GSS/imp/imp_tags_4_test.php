<?php
	include_once(dirname(__FILE__) . "/../.config.php");
	include_once(dirname(__FILE__) . "/../.library.php");

	$MongoColl = $MongoDB->tag;
	$tags = array(
		array("category"=>"元素", "tag"=>"白紗"),
		array("category"=>"元素", "tag"=>"婚紗照"),
		array("category"=>"元素", "tag"=>"造型"),
		array("category"=>"場景", "tag"=>"海邊"),
		array("category"=>"場景", "tag"=>"花海"),
		array("category"=>"場景", "tag"=>"湖畔"),
		array("category"=>"風格", "tag"=>"復古"),
		array("category"=>"風格", "tag"=>"甜美"),
		array("category"=>"風格", "tag"=>"韓風")
	);

	foreach($tags as $data) {
		echo $data["tag"] . (($MongoColl->insert($data)) ? true : false) . "\n";

	}
?>
