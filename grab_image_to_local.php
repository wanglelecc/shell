<?php
/**
 * 抓取图片到本地
 */
class image{

	private $url = null;
	private $local_path = './images/';

	private $content = null;


	public function __construct($url,$local_path){
		$this->url = $url;
		$this->local_path = $local_path;
	}

	// 执行
	public function run(){
		$this->download_file($this->get_images_url());
	}

	// 获取内容
	private function get_content(){
		 if($this->content){
		 	return $this->content;
		 }

		 if($this->url == null){
		 	throw new Exception("Error Processing Request By 'url'", 1);
		 }

		 if(!is_file($this->url)){
		 	$site_content = $this->curlRequest($this->url);
		 }else{
		 	$site_content = file_get_contents($this->url);
		 }

		 file_put_contents('test.log', $site_content);

		 return $this->content = $site_content;
	}

	// 抓取图片URL
	private function get_images_url(){
		/*利用正则表达式得到图片链接*/ 
		 $reg_tag = '/<img.*?\"([^\"]*(jpg|bmp|jpeg|gif|png)).*?>/'; 
		 $ret = preg_match_all($reg_tag, $this->get_content(), $match_result); 
		 return $this->revise_site($match_result[1]); 
	}

	// 对图片链接进行修正 
	private function revise_site($site_list){
		 foreach($site_list as $site_item) { 
			  if (preg_match('/^http/', $site_item)) { 
			   $return_list[] = $site_item; 
			  }elseif(preg_match('/^\/\//', $site_item)){
			  	$return_list[] = preg_match('/^https/', $this->url ) ? 'https:'.$site_item : 'http:'.$site_item; 
			  }else{ 
			   $return_list[] = $this->url."/".$site_item; 
			}
		 } 
		 return array_unique($return_list); 
	}

	// 将图片保存到本地
	private function download_file($pic_url_array){
		if($this->local_path == null){
		 	throw new Exception("Error Processing Request By 'local_path'", 1);
		 }
		 $this->mkdir($this->local_path);
		 $reg_tag = '/.*\/(.*?)$/'; 
		 $count = 0; 
		 foreach($pic_url_array as $pic_item){ 
		  $ret = preg_match_all($reg_tag,$pic_item,$t_pic_name); 
		  $pic_name = $this->local_path.$t_pic_name[1][0]; 
		  $pic_url = $pic_item; 
		  print("Downloading ".$pic_url." "); 

		  $fileInfo = $this->curl_download_file($pic_url);
          $this->save_file($pic_name, $fileInfo['body']);
		  print("[OK] "."\r\n"); 
		 }

		 print("Finish... "."\r\n"); 

		 return 0; 
	} 


	// 创建多级文件夹[支持中文]
	private function mkdir($path){
		if(is_dir($path)) return true;
		return mkdir(iconv("UTF-8", "GBK", $path),0777,true); 
	}

	private function curl_download_file($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $imageAll = array_merge(array('header'=>$httpinfo), array('body'=>$package));

        return $imageAll;
    }

    private function save_file($filename, $filecontent){
        $local_file = fopen($filename, 'w');
        if(false !== $local_file){
            if( false !== fwrite($local_file, $filecontent) ){
                return fclose($local_file);
            }
        }

        return false;
    }

    public function curlRequest($url = '', $info = array(), $timeout = 30) { // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环
        if (stripos ( $url, "https://" ) !== FALSE) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        }
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        if(!empty($info)) {
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $info); // Post提交的数据包
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            error_log('Errno:' . curl_error($curl)); //捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }


}

// 使用方法
//(new image('https://www.raiing.com/','./images/taobao/'))->run();
// (new image('./images.data','./images/taobao/'))->run();
// (new image('https://www.baidu.com/','./images/baidu/'))->run();
// (new image('https://www.jd.com/','./images/jd/'))->run();
(new image('https://www.taobao.com/','./images/taobao/'))->run();
