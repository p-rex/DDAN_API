<?php

class HTTPReq
{
	private $ch; //curl instance
	private $opt_header_ar; //通常のHeaderに追加するヘッダ
	
	function __construct($url)
	{
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $url);//URLをセット
		//	curl_setopt($this->ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE); //サーバ証明書検証を無視
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE); //サーバ証明書検証を無視
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE); //これが無い場合は標準出力に出力する。
		curl_setopt($this->ch, CURLINFO_HEADER_OUT,TRUE); //Request Header
		curl_setopt($this->ch, CURLOPT_HEADER, TRUE); //Response Header
	}
	
	function setPutData($put_data, $content_type = FALSE)
	{
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $put_data);
		$this->opt_header_ar[] ='Content-Length: ' . strlen($put_data);
		
		if($content_type)
			$this->opt_header_ar[] = 'Content-Type:' . $content_type;//'Content-Type:application/x-compressed';
	}
	
	function Request($header_ar)
	{
		if($this->opt_header_ar)
			$header_ar = array_merge($header_ar, $this->opt_header_ar);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header_ar);
	
		$resp = curl_exec($this->ch);
		
		// 接続ができない場合の処理（エラー処理)
		if(curl_errno($this->ch) != 0)
		{
			$error_msg = curl_error($this->ch);
			curl_close($this->ch);
//			throw new Exception($error_msg);
			return array(FALSE, $error_msg);
		}

		$stats_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE); //Status Code
		$req_header = curl_getinfo($this->ch, CURLINFO_HEADER_OUT); //Reuest Header 
		$resp_header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
		$resp_header = substr ($resp, 0, $resp_header_size);	//Response Header
		$body = substr ($resp, $resp_header_size);	//Response Body
		curl_close($this->ch);

		return array($stats_code, $body, $req_header, $resp_header);
	}
}



function writeStr2File($file_name, $str, $mode='w')
{
	$fp = fopen($file_name, $mode);
	if($fp === FALSE)
		return FALSE;
	flock($fp, LOCK_EX);
        fwrite($fp, $str);
	flock($fp, LOCK_UN);
        fclose($fp);
	return TRUE;
}


