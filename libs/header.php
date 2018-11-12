<?php

class HeaderManagement
{
	private $apikey;
	private $header_ar_def;
	private $header_ar;
	private $upload_file_body;

	function __construct($apikey, $custom_header)
	{
		$this->apikey = $apikey;
		
		$this->header_ar_def = array( //�w�b�_�͕�����g�����炱�̃f�t�H���g�̂��R�s�[���Ďg��
			'X-DTAS-ProtocolVersion' => '1.4',
			'X-DTAS-ProductName' => 'APIClient',
			'X-DTAS-ClientHostname' => 'APIClientHost',
			'X-DTAS-ClientUUID' => $this->mkUUID(),//$clientuuid,
			'X-DTAS-SourceID' => DDAN_API::SOURCEID,
			'X-DTAS-SourceName' => 'UserUpload',
			'X-DTAS-Time' => time(),
			'X-DTAS-Challenge' => $this->mkUUID(), //$challenge,
			);
			
		//�ύX�������w�b�_������ꍇ
		foreach($custom_header as $key => $val)
		{
			$this->header_ar_def[$key] = $val;
		}
		$this->header_ar = $this->header_ar_def;
	}
	
	//�����񃊃N�G�X�g���o���ۂ͂��̊֐��Ńw�b�_��reset����
	function resetHeader()
	{
		$this->header_ar = $this->header_ar_def; //�����ŏ㏑�����ׂ����E�E
		$this->upload_file_body = NULL;
	}
	
	function getClientUUID()
	{
		return $this->header_ar['X-DTAS-ClientUUID'];
	}

	function delHeader($del_header_ar)
	{
		foreach($del_header_ar as $val)
		{
			unset($this->header_ar[$val]);
		}
	}
	
	//�w�b�_�ǉ��B�d������ꍇ�͏㏑��
	function addHeader($new_header)
	{
		foreach($new_header as $key => $val)
		{
			$this->header_ar[$key] = $val;
		}
	}

	function setUploadFileBody($upload_file_body)
	{
		$this->upload_file_body = $upload_file_body;
	}

	function genHeader()
	{
		//�w�b�_�̏��Ԃ��Ō�ɂ������̂ŁA��Uunset���Ă���ēo�^
		unset($this->header_ar['X-DTAS-ChecksumCalculatingOrder']);
		unset($this->header_ar['X-DTAS-Checksum']);
	
		list($order, $checksum) = $this->mkHeaderOrder_and_Checksum();
		$this->header_ar['X-DTAS-ChecksumCalculatingOrder'] = $order; //��L�w�b�_��Order�ǉ�
		$this->header_ar['X-DTAS-Checksum'] = $checksum; //��L�w�b�_��checksum�ǉ�


		//�z��̃t�H�[�}�b�g�ϊ�
		foreach($this->header_ar as $key => $val)
		{
			$h[] = $key . ':' . $val;
		}
		return $h;
	}
	
	
	//Calcurate sha1 from All Header string
	private function mkHeaderOrder_and_Checksum()
	{
		$header_val_sum = $this->apikey;
		$header_name_sum = '';
		foreach($this->header_ar as $header_name => $header_val)
		{
			$header_name_sum .= $header_name . ','; //�Ō�ɂ��u,�v���܂܂��
			$header_val_sum .= $header_val;
		}
		if($this->upload_file_body)
			$header_val_sum .= $this->upload_file_body;
	
		return array(substr($header_name_sum, 0, -1), sha1($header_val_sum)); //substr�ōŌ�́u,�v���폜
	}
	
	// UUID must be the following length.
	// 8-4-4-4-12
	private function mkUUID()
	{
		$r8 = rand(10000000, 99999999);
		$r4 = rand(1000, 9999);
		$r12 = rand(100000000000, 999999999999);
		return $r8 . '-' . $r4 . '-' . $r4 . '-' . $r4 . '-' . $r12;
	}
}

