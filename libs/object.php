<?php

class SampleObject
{
	private $sample_file; //sample file name
	private $sample_file_sha1; //sha1 of $tgt_file body.
	private $sample_type_num;
	private $clientuuid;
	private $sourceid;
	private $zip_pass;
	private $tmp_path;
	private $tmp_file_prefix = 'ddan_api_';
	private $url_tmp_file = '/tmp/ddan_api_url_tmp.txt';
		
	function __construct($sample_file, $sample_type, $clientuuid, $zip_pass)
	{
		//各種パスの設定
		$this->tmp_path = sys_get_temp_dir() . '/';
		$this->url_tmp_file = $this->tmp_path . $this->tmp_file_prefix . 'url_tmp.txt';
		
		//前回のゴミが残っていると邪魔くさいので削除
		$this->unlinkFiles();
	

		$this->chkFileType($sample_file, $sample_type);
		$this->sample_file_sha1 = sha1(file_get_contents($this->sample_file));

		$this->clientuuid = $clientuuid;
		$this->sourceid = DDAN_API::SOURCEID;
		$this->zip_pass = $zip_pass;
	}
	
	private function chkFileType($sample_file, $sample_type)
	{
		if($sample_type === 'file')
		{
			$this->sample_type_num = 0;
			$this->sample_file = $sample_file;
		}
		else if($sample_type === 'url')
		{
			$this->sample_type_num = 1;
			$fh = fopen($this->url_tmp_file, "w");
			fwrite($fh, $sample_file);
			fclose($fh);
			$this->sample_file = $this->url_tmp_file; //上書き。ダサい
		}
		else
		{
			exit('Unknown file type');
		}
	}
	
	
	private function mkMetaStr()
	{
//		SampleType=[SampleType]&ClientUUID=[ClientUUID]&SourceID=[SourceID]&SampleFileSHA1=[SampleFileSHA1]&SampleFileExist=[SampleFileExist]&OrigFileName=[OrigFileName]&EventID=[EventID]&SkipPrefilter=[SkipPrefilter]&Archpassword=[Archpassword]&Docpassword=[Docpassword]
	
		$meta_ar = array(
			'SampleType' => $this->sample_type_num,
			'ClientUUID' => $this->clientuuid,
			'SourceID' => $this->sourceid,
			'SampleFileSHA1' => $this->sample_file_sha1,
			'SampleFileExist' => 1, // 1: The sample file [SHA1].dat exists. 0: The sample file [SHA1].dat doesn’t exist. Used when client want to upload a new log but the sample file is duplicated on server. If the value is 1 and the file [SHA1].dat does not exist, a 420 Bad Request error code will return.
			// 'OrigFileName' //Optional
			//'EventID'//optional
			//SkipPrefilter //optional
			//Archpassword=[Archpassword] //optional
			//Docpassword=[Docpassword] //optional
//			'Archpassword' => 'virus' //optional
			);
		if($this->zip_pass)
			$meta_ar['Archpassword'] = $this->zip_pass;

		$str = '';
		foreach($meta_ar as $key => $val)
		{
			$str .= $key . '=' . $val . '&';
		}
		
		return substr($str, 0, -1); //最後の&を消して返す
	}
	
	private function mkLogStr()
	{
		return 'Date=' . gmdate('m/d/Y H:i:s'); //その他のフィールドはoptionみたい
	}
	
	
	
	function mkTGZ()
	{
//		$this->unlinkFiles();
		
	
		$new_file = tempnam($this->tmp_path, $this->tmp_file_prefix);
//var_dump($new_file);exit;
		//古いファイルの削除。念のため
//		@unlink($new_file . '.tar');
//		@unlink($new_file . '.tgz');
		
		//Meta
		$meta_str = $this->mkMetaStr();
		$meta_file = $this->tmp_path . $this->sample_file_sha1 . '.meta';
		writeStr2File($meta_file, $meta_str);
		
		//log
		$log_str = $this->mkLogStr();
		$log_file = $this->tmp_path . $this->sample_file_sha1 . '.log';
		writeStr2File($log_file, $log_str);
		
		//dat
		$dat_file = $this->tmp_path . $this->sample_file_sha1 . '.dat';
		copy($this->sample_file, $dat_file); //IMDL ファイルがディレクトリ内にある場合
		
		
		//compress
		$tarPath = $new_file . '.tar';
		$phar = new PharData($tarPath);
		$phar->addFile($meta_file, basename($meta_file)); //tarファイル内にパスを含めないように、2つ目の引数でファイル名を入れる
		$phar->addFile($log_file, basename($log_file));
		$phar->addFile($dat_file, basename($dat_file));
		$phar->compress(Phar::GZ, '.tgz');

		//delete
//		@unlink($tarPath);
		@unlink($meta_file);
		@unlink($log_file);
		@unlink($dat_file);
		
		return $new_file . '.tgz';
	}
	
	public function unlinkFiles()
	{	// ワイルドカードを使って削除したいファイルを指定
		$fileName = $this->tmp_path . $this->tmp_file_prefix . '*';

		foreach(glob($fileName) as $val)// glob() でヒットしたファイルのリストを取得
		{
			@unlink($val);
		}
	}
	
	function __destruct()
	{
		$this->unlinkFiles();
	}
}
