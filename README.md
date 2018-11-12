# DDAN_API
PHP Class Library for DDAN Web API.

# Supported Versions
PHP5.3  
DDAN v5.5.1.1193


# Installation
libs配下の4つのファイルを同じディレクトリに保存し、ddan_api.phpを読み込む。

    <?php
    require_once('libs/ddan_api.php');





# Usage
## インスタンス生成
    $ip = '10.0.0.1';
    $apikey = 'xxxxxxxxxxxxx';
    
    //Option Header. If needed.
    $opt_header = array(
    	'X-DTAS-SourceName' => 'Submitter', //GUIの「サブミッター」に表示される
    	'X-DTAS-ClientHostname' => 'SubmitterName'); //GUIの「サブミッター名」として表示される
    
    $dd = new DDAN_Reqs($ip, $apikey, $opt_header); //$opt_headerは無しでも良い。


Notes:  
インスタンス生成時に'https://ddan_ip/web_service/sample_upload/register' にアクセスし、クライアント登録を行っている。
そのアクセス時の内容を把握したい場合は、以下のメソッドを使用。

    $result = $dd->getResisterResp();




## メソッド
### Upload File
    $dd->uploadFile(string $malware_file_name, [string $zip_pass]); //If you upload Password-ZIP, specify $zip_pass.

### Upload URL
    $dd->uploadURL(string $url);

### Get Report
    $dd->getReport(string $hash, [string $report_type]);

### Check Dupplicate Hash
    $dd->chkDup(array $sha1_ar, [int $last_minutes]);

### Get Open IOC Report
    $dd->getIOCRep(string $sha1);

### Get All Black Lists
    $dd->getAllBL([string $query_id]);

### Get Sample List by Interval
    $dd->getSampleListByInterval([int $type], [int $start_unix_time], [int $end_unix_time])

### Get Sample by SHA1
    $dd->getSampleListBySha1(string $sha1);

### Get Pcap by SHA1
    $dd->getPcap(string $sha1, [string $archive_type], [string $image_type_id]);

### Get Brief Report
    $dd->getBriefRep(array $sha1_ar);

### Get Sandbox Screenshot by SHA1
    $dd->getSbSshotBySha1(string $sha1, [string $image_type_id]);

### Get Event Log by SHA1
    $dd->getEvLogBySha1(string $sha1);

Notes:  
各メソッドは連続して実行しても良い。（メソッド毎にインスタンス生成不要）

## 各メソッドの戻り値
配列で戻ります。

DDAN APIと通信できた場合

    $result[0]; //HTTP ステータスコード
    $result[1]; //レスポンスボディ
    $result[2]; //リクエストヘッダ
    $result[3]; //レスポンスヘッダ

異常時

    $result[0]; //FALSE
    $result[1]; //Error Message
