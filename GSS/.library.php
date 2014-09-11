<?php
	function get_category(&$MongoDB, $tag) {

		$MongoColl = $MongoDB->tag;
		$document = $MongoColl->find(array("tag"=>$tag), array("category"=>1))->getNext();
		return (isset($document["category"])) ? $document["category"] : null;

	}
	
	function fetch_date($link) {

		if(preg_match("/^http:\/\/s.verywed.com\/s1/i", $link)) {
			if(preg_match("/[0-9]{4}\/[0-9]{2}\/[0-9]{2}/i", $link, $matches) && isset($matches[0])) {
				return $matches[0];
			}
		}
		return null;

	}

	function is_exists(&$MongoColl, $data) {

		$cursor = $MongoColl->find($data);
		$document = $cursor->getNext();
		return (isset($document["_id"])) ? true : false;

	}

	function get_timestamp_date($a) {

		return (int) substr($a, 0, 4) . substr($a, 4, 2) . substr($a, 6, 2);

	}

	function get_xml_next($str) {

		if(preg_match("/start=([0-9]+)/", $str, $matches) && isset($matches[1])) {
			return $matches[1];
		}
		return 0;

	}

	function set_token_limit(&$MongoDB, $key) {

		$MongoColl = $MongoDB->token;

		$cursor   = $MongoColl->find(array("key"=>$key));
		$document = $cursor->getNext();
		if(isset($document["_id"])) {
			return update_mongodb($MongoColl, $document["_id"], array("limit"=>(int)((int) $document["limit"] - 1)));
		}

	}

	function get_token(&$MongoDB) {

		$MongoColl = $MongoDB->token;
		$cursor = $MongoColl->find(array("limit" => array('$gt' => 0)))->limit(1);
		$document = $cursor->getNext();
        	if(!isset($document["key"])) exit("no token to use\n");

		return $document["key"];

	}

	function lst_gss(&$MongoColl) {
		$cursor = $MongoColl->find()->limit(10);
		$rows = array();
		foreach($cursor as $document) {
			array_push($rows, $document);
		}
		return $rows;
	}

	function get_gss($key, $cx, $q) {

		$url = "https://www.googleapis.com/customsearch/v1?key=". $key . "&cx=" . $cx . "&q=". $q . "&searchType=image&imgSize=large";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$body = curl_exec($ch);
		curl_close($ch);
		return json_decode($body);

	}

	function insert_mongodb(&$MongoColl, $data) {

		//$cursor   = $MongoColl->find(array("link"=>$data->link));
		//$document = $cursor->getNext();
		//if(!$document["_id"]) {
			return ($MongoColl->insert($data)) ? true : false;
		//} else {
		//	return update_mongodb($MongoColl, $document["_id"], $data); 
		//}

	}

	function update_mongodb(&$MongoColl, &$MongoIDObj, $data) {

		$status = $MongoColl->update(array("_id" => $MongoIDObj), array('$set' => $data));
		return ($status["ok"]) ? true : false;

	}

	/**
	* 確認是否安裝 PHP Modules
	*
	* @param String $name
	* @return boolean
	**/
	function chkModules($name) {
		if(extension_loaded($name)) {
			return true;
		} else {
			echo "Install " . $name . " PHP Modules? No\n";
			return false;
		}
	}

	/** 
	* The same thing than implode function, but return the keys so 
	* 
	* <code> 
	* $_GET = array('id' => '4587','with' => 'key'); 
	* ... 
	* echo shared::implode_with_key('&',$_GET,'='); // Resultado: id=4587&with=key 
	* ... 
	* </code> 
	* 
	* @param string $glue Oque colocar entre as chave => valor 
	* @param array $pieces Valores 
	* @param string $hifen Separar chave da array do valor 
	* @return string 
	* @author memandeemail at gmail dot com 
	*/ 
	function implode_with_key($glue = null, $pieces, $hifen = ',') { 
		$return = null; 
		foreach ($pieces as $tk => $tv) $return .= $glue.$tk.$hifen.$tv; 
		return substr($return,1); 
	}

	/** 
	* Return unique values from a tree of values 
	* 
	* @param array $array_tree 
	* @return array 
	* @author memandeemail at gmail dot com 
	*/
	function array_unique_tree($array_tree) { 
		$will_return = array(); $vtemp = array(); 
		foreach ($array_tree as $tkey => $tvalue) $vtemp[$tkey] = implode_with_key('&',$tvalue,'='); 
		foreach (array_keys(array_unique($vtemp)) as $tvalue) $will_return[$tvalue] = $array_tree[$tvalue]; 
		return $will_return; 
	}

	/**
	* 取得現在的日期時間
	*
	* @return W3C Datetime
	**/
	function getNow() {
		$D = new DateTime("NOW");
		return $D->format(DateTime::W3C);
	}

	/**
	* 執行 PHP 檔案取得 URL
	*
	* @param Array &$rows：抓取到網址的資料陣列
	* @param String $path：工具程式位置
	* @param String $name：工具程式名稱
	* @return boolean
	**/
	function execPHP($file) {
		$file = dirname(__FILE__) . "/" . $file;
		exec(escapeshellcmd("/usr/bin/php " . $file), $output);
		return $output[0];
	}

	// strip javascript, styles, html tags, normalize entities and spaces
	// based on http://www.php.net/manual/en/function.strip-tags.php#68757
	function html2text($html) {
		$text = $html;
		static $search = array(
			'@<script.+?</script>@usi',  // Strip out javascript content
			'@<style.+?</style>@usi',    // Strip style content
			'@<!--.+?-->@us',            // Strip multi-line comments including CDATA
			'@</?[a-z].*?\>@usi',         // Strip out HTML tags
		);
		$text = preg_replace($search, ' ', $text);
		// normalize common entities
		$text = normalizeEntities($text);
		// decode other entities
		$text = html_entity_decode($text, ENT_QUOTES, 'utf-8');
		// normalize possibly repeated newlines, tabs, spaces to spaces
		$text = preg_replace('/\s+/u', ' ', $text);
		$text = preg_replace('//u', '', $text);
		$text = preg_replace('//u', '', $text);
		$text = trim($text);
		// we must still run htmlentities on anything that comes out!
		// for instance:
		// <<a>script>alert('XSS')//<<a>/script>
		// will become
		// <script>alert('XSS')//</script>
		return $text;
	} 

	// replace encoded and double encoded entities to equivalent unicode character
	// also see /app/bookmarkletPopup.js
	function normalizeEntities($text) {
		static $find = array();
		static $repl = array();
		if (!count($find)) {
			// build $find and $replace from map one time
			$map = array(
				array('\'', 'apos', 39, 'x27'), // Apostrophe
				array('\'', '‘', 'lsquo', 8216, 'x2018'), // Open single quote
				array('\'', '’', 'rsquo', 8217, 'x2019'), // Close single quote
				array('"', '“', 'ldquo', 8220, 'x201C'), // Open double quotes
				array('"', '”', 'rdquo', 8221, 'x201D'), // Close double quotes
				array('\'', '‚', 'sbquo', 8218, 'x201A'), // Single low-9 quote
				array('"', '„', 'bdquo', 8222, 'x201E'), // Double low-9 quote
				array('\'', '′', 'prime', 8242, 'x2032'), // Prime/minutes/feet
				array('"', '″', 'Prime', 8243, 'x2033'), // Double prime/seconds/inches
				array(' ', 'nbsp', 160, 'xA0'), // Non-breaking space
				array('-', '‐', 8208, 'x2010'), // Hyphen
				array('-', '–', 'ndash', 8211, 150, 'x2013'), // En dash
				array('--', '—', 'mdash', 8212, 151, 'x2014'), // Em dash
				array(' ', ' ', 'ensp', 8194, 'x2002'), // En space
				array(' ', ' ', 'emsp', 8195, 'x2003'), // Em space
				array(' ', ' ', 'thinsp', 8201, 'x2009'), // Thin space
				array('*', '•', 'bull', 8226, 'x2022'), // Bullet
				array('*', '‣', 8227, 'x2023'), // Triangular bullet
				array('...', '…', 'hellip', 8230, 'x2026'), // Horizontal ellipsis
				array('°', 'deg', 176, 'xB0'), // Degree
				array('€', 'euro', 8364, 'x20AC'), // Euro
				array('¥', 'yen', 165, 'xA5'), // Yen
				array('£', 'pound', 163, 'xA3'), // British Pound
				array('©', 'copy', 169, 'xA9'), // Copyright Sign
				array('®', 'reg', 174, 'xAE'), // Registered Sign
				array('™', 'trade', 8482, 'x2122'), // TM Sign
			);
			foreach ($map as $e) {
				for ($i = 1; $i < count($e); ++$i) {
					$code = $e[$i];
					if (is_int($code)) { // numeric entity
						$regex = "/&(amp;)?#0*$code;/";
					} elseif (preg_match('/^.$/u', $code)/* one unicode char*/) { // single character
						$regex = "/$code/u";
					} elseif (preg_match('/^x([0-9A-F]{2}){1,2}$/i', $code)) { // hex entity
						$regex = "/&(amp;)?#x0*" . substr($code, 1) . ";/i";
					} else { // named entity
						$regex = "/&(amp;)?$code;/";
					}
					$find[] = $regex;
					$repl[] = $e[0];
				}
			}
		} // end first time build
		return preg_replace($find, $repl, $text);	
	}
?>
