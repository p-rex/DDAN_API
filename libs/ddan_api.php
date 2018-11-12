<?php

require_once(dirname(__FILE__) . '/io.php');
require_once(dirname(__FILE__) . '/header.php');
require_once(dirname(__FILE__) . '/object.php');


//----------------------------------------------------------------------//
// 親クラス
//----------------------------------------------------------------------//
class DDAN_API
{
	//URLs
	const URL_PREFIX = '/web_service/sample_upload/';
	const URL_TEST_CON = 'test_connection';
	const URL_REG = 'register';
	const URL_UNREG = 'unregister';
	const URL_CHK_DUP = 'check_duplicate_sample';
	const URL_UPLOAD = 'upload_sample';
	const URL_GET_REP = 'get_report';
	const URL_GET_OPEN_IOC_REP = 'get_openioc_report';
	const URL_GET_ALL_BL = 'get_black_lists';
	const URL_GET_BL_SHA1 = 'get_black_list_by_sha1'; // DDAN v5.5.1.1193のソースに存在しない
	const URL_GET_SAMPLE_LIST_BY_INTERVAL = 'get_sample_list';
	const URL_GET_SAMPLE_BY_SHA1 = 'get_sample';
	const URL_QUERY_SAMPLE_INFO = 'query_sample_info';
	const URL_GET_PCAP = 'get_pcap';
	const URL_GET_BRIEF_REP = 'get_brief_report';
	const URL_GET_SB_SSHOT_BY_SHA1 = 'get_sandbox_screenshot';
	const URL_GET_EV_LOG_BY_SHA1 = 'get_event_log';
	
	//common vars
	const SOURCEID = '10002';
	
	//Objects
	protected $hm; // Instance of class HeaderManagement.
	
	//Others
	private $ip;
	private $register_resp; //Register()のレスポンスが入る

	
	function __construct($ip, $apikey, $custom_header = array())
	{
		$this->ip = $ip;
		$this->hm = new HeaderManagement($apikey, $custom_header);

		//先に実施してしまう
		$this->Register();
	}

	protected function mkURL($path){
		return 'https://' . $this->ip . self::URL_PREFIX . $path;
	}

	//Registerはコンストラクタのみからアクセスさせる
	private function Register()
	{
		$hr = new HTTPReq($this->mkURL(self::URL_REG));
		$this->register_resp = $hr->Request($this->hm->genHeader()); //debug用にレスポンスを保存
		unset($hr);
	}
	
	//debug用。Registerに失敗する場合に使用。
	public function getResisterResp()
	{
		return $this->register_resp;
	}
	
	private function UnRegister()
	{
		$this->hm->resetHeader();
		$hr = new HTTPReq($this->mkURL(self::URL_UNREG));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
		
	}
	
	function __destruct()
	{
		//$this->UnRegister(); //この処理が必要なのか不明
	}
}



//----------------------------------------------------------------------//
// 各Web APIにアクセスするメソッドをまとめたクラス
//----------------------------------------------------------------------//

class DDAN_Reqs extends DDAN_API
{
	//5.4	Check Duplicate Sample
	//引数: $hash_ar === hashの配列
	//      $last_min === 検索対象の分
	//HTTP Response = matchしたハッシュがセミコロン区切りで返ってくる
	public function chkDup($hash_ar, $last_min = 0)
	{
		$put_body = implode(';', $hash_ar);
		
		$this->hm->resetHeader();
		$this->hm->addHeader(array('X-DTAS-LastMinute' => $last_min));
		$this->hm->setUploadFileBody($put_body);

		$hr = new HTTPReq($this->mkURL(self::URL_CHK_DUP));
		$hr->setPutData($put_body, 'text/plain');
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}
	
	

	//5.5	Upload Sample
	//この関数はFileとURLで両方使えるが使いにくいためprivateとし、File用、URL用に分離する
	private function SampleUpload($file, $sample_type = 'file', $zip_pass = FALSE)
	{
		//アップロードするtgz生成
		$so = new SampleObject($file, $sample_type, $this->hm->getClientUUID(), $zip_pass);
		$tgz_filename = $so->mkTGZ();
		
		$upload_file_body = file_get_contents($tgz_filename);
		$upload_file_sha1 = sha1($upload_file_body);
		$date_str = date('Ymd-His');
		$upload_file_name = $date_str . '_' . $upload_file_sha1 . '.tgz';
	
		//ヘッダ調整
		$this->hm->resetHeader();
		$this->hm->addHeader(array(
			'X-DTAS-Archive-SHA1' => $upload_file_sha1,
			'X-DTAS-Archive-Filename' => $upload_file_name)
			);
		$this->hm->setUploadFileBody($upload_file_body);
		
		//Request
		$hr = new HTTPReq($this->mkURL(self::URL_UPLOAD));
		$hr->setPutData($upload_file_body, 'application/x-compressed');
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
		
	}
	
	//5.5	Upload Sample のFile専用関数
	public function uploadFile($file, $zip_pass = FALSE)
	{
		return $this->SampleUpload($file, 'file', $zip_pass);
	}
	
	//5.5	Upload Sample のURL専用関数
	public function uploadURL($url)
	{
		return $this->SampleUpload($url, 'url');
	}


	// 5.6	Get Report by SHA1
	// $hash === Target Hash
	// $report_type === 0 for Single Image (default), 1 for Multiple Images. 
	//		If the sample was analyzed by multiple image types, choose the report having the highest ROZ rating. 
	//		If the ROZ ratings are all the same, choose the report having the lowest image type ID. (optional)
	public function getReport($hash, $report_type = 0)
	{
		$this->hm->resetHeader();
		$this->hm->addHeader(array('X-DTAS-SHA1' => $hash));
		$this->hm->addHeader(array('X-DTAS-ReportType' => $report_type));
		$hr = new HTTPReq($this->mkURL(self::URL_GET_REP));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}
	
	//IMDL 動作未確認
	//5.7	Get OpenIOC Report
	public function getIOCRep($hash)
	{
		$this->hm->resetHeader();
		$this->hm->addHeader(array('X-DTAS-SHA1' => $hash));
		$hr = new HTTPReq($this->mkURL(self::URL_GET_OPEN_IOC_REP));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}
	
	
	//5.8	Get All Black Lists
	public function getAllBL($query_id = FALSE)
	{
		$this->hm->resetHeader();
//		$this->hm->delHeader(array('X-DTAS-ProductName', 'X-DTAS-ClientHostname', 'X-DTAS-SourceID', 'X-DTAS-SourceName'));
		if($query_id)
			$this->hm->addHeader(array('X-DTAS-LastQueryID' => $query_id));
		$hr = new HTTPReq($this->mkURL(self::URL_GET_ALL_BL));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}
	
	// DDAN v5.5.1.1193のソースに存在しない
	// 5.9	Get Black List By SHA1
	public function getBL($hash)
	{
		$this->hm->resetHeader();
		$this->hm->delHeader(array('X-DTAS-ProductName', 'X-DTAS-ClientHostname', 'X-DTAS-SourceID', 'X-DTAS-SourceName'));
		$this->hm->addHeader(array('X_DTAS_SHA1' => $hash)); //資料ではここだけアンダーバー。？？
		$hr = new HTTPReq($this->mkURL(self::URL_GET_BL_SHA1));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}
	
	// 5.10	Get Sample List by Interval
	public function getSampleListByInterval($type = 0, $start_utime = FALSE, $end_utime = FALSE)
	{
		$this->hm->resetHeader();
//		$this->hm->delHeader(array('X-DTAS-ProductName', 'X-DTAS-ClientHostname', 'X-DTAS-SourceID', 'X-DTAS-SourceName'));

		if($start_utime && $end_utime)
		{
			$new_header_ar = array(
				'X-DTAS-IntervalType' => $type,
				'X-DTAS-IntervalStartingPoint' => $start_utime,
				'X-DTAS-IntervalEndPoint' => $end_utime,
				);
			$this->hm->addHeader($new_header_ar);
		}
		$hr = new HTTPReq($this->mkURL(self::URL_GET_SAMPLE_LIST_BY_INTERVAL));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}

	//5.11	Get Sample by SHA1
	public function getSampleListBySha1($sha1)
	{
		$this->hm->resetHeader();
		$this->hm->addHeader(array('X-DTAS-SHA1' => $sha1));

		//下記2つのオプションはよくわからないので実装しない。
//	X-DTAS-ArchiveType: Specify export data type (either zip or tgz), default value is tgz.
//	X-DTAS- ArchiveEncrypted: 0(not encrypted) or 1(Encrypted with password “virus”) (Optional field. Default 0. ArchiveType must be zip when this field set to 1) 
		$hr = new HTTPReq($this->mkURL(self::URL_GET_SAMPLE_BY_SHA1));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}
	
	
	//5.12	Get Report Summary Info By Sample SRID
	//以下の意味が不明なため、実装せず
	//X-DTAS-SRID: value of srid queried from DDAN by interface: query_sample_list
	
	
	//5.13	Get Pcap by SHA1	
	public function getPcap($sha1, $archive_type = FALSE, $image_type_id = NULL)
	{
		$this->hm->resetHeader();
		
		$new_header_ar['X-DTAS-SHA1'] = $sha1;
		if($archive_type)
			$new_header_ar['X-DTAS-ArchiveType'] = $archive_type; //デフォルト tgzらしい
		
		if(isset($image_type_id)) //$image_type_idは文字列か数値か不明で、0が来るかも知れないので、issetでNULLチェックをする
			$new_header_ar['X-DTAS-ImageTypeID'] = $image_type_id;
		
		$this->hm->addHeader($new_header_ar);

		//下記2つのオプションはよくわからないので実装しない。
//	X-DTAS-ArchiveType: Specify export data type (either zip or tgz), default value is tgz.
//	X-DTAS- ArchiveEncrypted: 0(not encrypted) or 1(Encrypted with password “virus”) (Optional field. Default 0. ArchiveType must be zip when this field set to 1) 
		$hr = new HTTPReq($this->mkURL(self::URL_GET_PCAP));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}
	
	
	//5.14	Get Brief Report
	public function getBriefRep($sha1_ar)
	{

		$put_body = implode(';', $sha1_ar);
		
		$this->hm->resetHeader();
		$this->hm->setUploadFileBody($put_body);

		$hr = new HTTPReq($this->mkURL(self::URL_GET_BRIEF_REP));
		$hr->setPutData($put_body, 'text/plain');
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}
	

	//5.15	Get Sandbox Screenshot by SHA1
	public function getSbSshotBySha1($sha1, $image_type_id = NULL)
	{
		$this->hm->resetHeader();
		
		$new_header_ar['X-DTAS-SHA1'] = $sha1;
		if(isset($image_type_id))
			$new_header_ar['X-DTAS-ImageTypeID'] = $image_type_id;
			
		$this->hm->addHeader($new_header_ar);
		$hr = new HTTPReq($this->mkURL(self::URL_GET_SB_SSHOT_BY_SHA1));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}

	//5.16	Get Event Log by SHA1
	public function getEvLogBySha1($sha1)
	{
		$this->hm->resetHeader();
		$this->hm->addHeader(array('X-DTAS-SHA1' => $sha1)); 
		$hr = new HTTPReq($this->mkURL(self::URL_GET_EV_LOG_BY_SHA1));
		$result = $hr->Request($this->hm->genHeader());
		unset($hr);
		return $result;
	}
}





