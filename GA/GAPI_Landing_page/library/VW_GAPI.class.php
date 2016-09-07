<?php
	/**
	* yourdomain`s Google 帳號與 GA 串連並取得流量資料之物件
	*
	* @version 1.0.0
	* @author rockst <rockst@gmail.com>
	*
	*/
	include_once(dirname(__FILE__) . "/gapi.class.php");

	class VW_GAPI extends gapi {

		const USER 	= "";
   	const PWD 	= "";

		public $auth_token;
		public $msg;

		// 建構子
		public function __construct() {

			$this->msg = $this->connect();

		} // end of __construce

		/**
		* 連結 GA Server
		*
		* @param String 從 ./token.txt 取得 token
		* @param String VW_GAPI::USER 登入帳號
		* @param String VW_GAPI::PWD 登入密碼
		* @return NULL success 
		* @return String error message
		*/
		public function connect() {

			// 從檔案中取得 token
			$file = dirname(__FILE__) . "/token.txt";
			if(file_exists($file) && @filesize($file) > 0) {
				$resource = @fopen($file, "r");
				$this->auth_token = @fread($resource, @filesize($file));
				@fclose($resource);
				return $this->connect_with_token(); // connect by token
			} else { // 無 token 則使用帳密連結
				$this->auth_token = null;
				return $this->connect_with_user(); // connect by user
			}


		} // end of connect

		/**
		* 透過 token 取得 GA 連結
		*
		* @param String $this->auth_token
		* @return NULL success 
		* @return String error message 
		*/
		private function connect_with_token() {

			try {

				parent::__construct(null, null, $this->auth_token);
				$rep = $this->requestAccountData();
				return (!empty($rep)) ? null : "透過 token 取得帳號資料失敗";

			} catch (Exception $e) { // token 失效，則改使用帳密取得連結

				return $this->connect_with_user();

			}

		} // end of connect_with_token

		/**
		* 透過帳密取得 GA 連結
		*
		* @param String VW_GAPI::USER 登入帳號
		* @param String VW_GAPI::PWD 登入密碼
		* @return NULL success 
		* @return String error message 
		**/
		private function connect_with_user() {

			try {

				parent::__construct(VW_GAPI::USER, VW_GAPI::PWD);
				$rep = $this->requestAccountData();
				if(!empty($rep) && !empty($this->auth_token)) { // 成功 

					// 寫入 token 到檔案中
					$file = dirname(__FILE__) . "/token.txt";
					$resource = @fopen($file, "w");
					@fwrite($resource, $this->auth_token);
					@fclose($resource);

					return null;

				} else {
					return "透過帳密取得帳號資料失敗";
				}
			} catch (Exception $e) {
				return "GA 連結失敗: " . $e->getMessage();
			}

		} // end of connect_with_user

		/**
		* 從 Google Analytics 取得資料
		*
		* @param Object $ga: Google Analy PHP Insterface Object
		* @param Array $results: 從 GA 取回資料的變數
		* @param Int $ga_id: GA 帳號 ID
		* @param String $dimensions: 維度
		* @param String $metrics: 指標
		* @param String $sort: 排序指標
		* @param String $filter: 篩選資料指標
		* @param String $date1: 開始日期
		* @param String $date2: 結束日期
		* @param Int $offset: 從哪一個位置開始抓資料
		* @param Int $limit: 一次取得幾筆資料
		* @return $results（傳址變數）	
		*/
		public function results(&$results, $ga_id, $dimensions, $metrics, $sort, $filter, $date1, $date2, $offset, $limit) {

			try {

				// 產生 request 給 Google Analytics 取得資料
				$this->requestReportData($ga_id, $dimensions, $metrics, $sort, $filter, $date1, $date2, $offset, $limit);

				// 傳送 request 給 Google Analytics 取得資料
				$results = $this->getResults();
				return (!empty($results) && count($results) > 0) ? null : "沒有任何資料";

			} catch(exception $e) {
				return "Caught exception: " . $e->getMessage();
			}

		} // end of results

	} // end of class VW_GAPI

?>
