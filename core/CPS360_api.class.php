<?PHP
require_once('CPS360_models.class.php');
require_once('CPS360_plugin.class.php');

class CPS360_api{

	/********************************* API Inner Config *********************************/

	const VERSION				= '0.1.0';
	const BUILD					= '201206121745';
	const REPORT_URL			= 'http://open.union.360.cn/gofailed';
	const ACTIVE_PERIOD			= 900;
	const MAXNUM				= 2000;
	static private $DEBUG		= false;

	/********************************* API Inner Param *********************************/

	static private $_API_PARAMS = array(
		'redirect' => array('bid','qihoo_id','url','from_url','active_time','ext','qid','qmail','qname','sign','verify'),
		'order' => array('bid','order_ids','start_time','end_time','updstart_time','updend_time','last_order_id','active_time','sign'),
		'check' => array('bid','bill_month','last_order_id','active_time','sign'),
	);

	/********************************* CPS API *********************************/

	static public function redirect(){
		$params = self::_param_get('redirect');
		$plugin = self::_plugin_load();
		
		//Cookie
		$cookies = array(
			'id' => '360cps',
			'qihoo_id' => $params['qihoo_id'],
			'ext' => $params['ext'],
			'qid' => $params['qid'],
			'qmail' => $params['qmail'],
			'qname' => $params['qname'],
		);
		$cur_domainroot = self::_domainroot();
		$cookie_domainroot = self::_domainroot(CPS360_config::COOKIE_DOMAIN);
		$cookie_param = array(
			'name' => CPS360_config::COOKIE_NAME,
			'value' => self::serialize($cookies),
			'domain' => ($cookie_domainroot == $cur_domainroot ? CPS360_config::COOKIE_DOMAIN : $cur_domainroot),
			'expires' => time() + (86400 * CPS360_config::RD),
			'path' => '/',
		);
		if(self::$DEBUG){
			self::_debug_output('Cookie Set',$cookie_param);
		}else{
			setcookie($cookie_param['name'],$cookie_param['value'],$cookie_param['expires'],$cookie_param['path'],$cookie_param['domain']);
		}

		//Activetime & Sign
		$check_activetime = self::_check_activeTime($params['active_time']);
		$check_sign = self::_check_sign($params,'redirect');
		if(!$check_activetime['isfine'] || !$check_sign['isfine']){
			//错误报告
			$data_report = $params;
			$data_report['userip'] = self::_ip_get();
			$data_report['useragent'] = $_SERVER['HTTP_USER_AGENT'];
			self::_http_request(self::REPORT_URL,'post',$data_report);
			if(self::$DEBUG){
				self::_debug_output('Error Report',$data_report);
			}
			
			//Clear Userinfo
			$params['qid'] = $params['qmail'] = $params['qname'] = false;
		}elseif($params['verify']){
			self::_verify();
		}

		//Auto login
		if($params['qid'] > 0){
			$plugin->login_auto($params['qid'],$params['qmail'],$params['qname']);
		}

		//Redirect
		if($params['url'] && self::_domainroot($params['url']) == $cur_domainroot){
			$url = $params['url'];
		}else{
			$url = CPS360_config::REDIRECT_DEFAULT;
		}
		if(self::$DEBUG){
			self::_debug_output('Header','Location:'.$url);
		}else{
			header('Location:'.$url);
		}
	}

	static public function order(){
		$params = self::_param_get('order');
		$plugin = self::_plugin_load();

		//Activetime & Sign
		$check_activetime = self::_check_activeTime($params['active_time']);
		$check_sign = self::_check_sign($params,'order');
		if(!$check_activetime['isfine'] || !$check_sign['isfine']){
			self::_output($check_activetime['message'].$check_sign['message']);
		}

		//调用用户方法
		if($params['order_ids']){
			$result = $plugin->order_by_ids($params['order_ids']);
		}elseif($params['start_time'] && $params['end_time']){
			$result = $plugin->order_by_time($params['start_time'],$params['end_time'],$params['last_order_id']);
		}elseif($params['updstart_time'] && $params['updend_time']){
			$result = $plugin->order_by_updtime($params['updstart_time'],$params['updend_time'],$params['last_order_id']);
		}

		//Output
		$xmldoc = self::_xml_generate($result);
		if(self::$DEBUG){
			self::_debug_output('XML',$xmldoc);
		}else{
			self::_output($xmldoc);
		}
	}

	static public function check(){
		$params = self::_param_get('check');
		$plugin = self::_plugin_load();

		//Activetime & Sign
		$check_activetime = self::_check_activeTime($params['active_time']);
		$check_sign = self::_check_sign($params,'check');
		if(!$check_activetime['isfine'] || !$check_sign['isfine']){
			self::_output($check_activetime['message'].$check_sign['message']);
		}

		//调用用户方法
		$result = $plugin->check_by_month($params['bill_month'],$params['last_order_id']);

		//Output
		$xmldoc = self::_xml_generate($result);
		if(self::$DEBUG){
			self::_debug_output('XML',$xmldoc);
		}else{
			self::_output($xmldoc);
		}
	}

	/********************************* CPS Utility *********************************/

	static private function _param_get($type = ''){
		$paramsneeds = isset(self::$_API_PARAMS[$type]) ? self::$_API_PARAMS[$type] : self::$_API_PARAMS['order'];

		$params = array();
		foreach($paramsneeds as $key){
			if(isset($_POST[$key])){
				$params[$key] = $_POST[$key];
			}elseif(self::$DEBUG && isset($_GET[$key])){
				$params[$key] = $_GET[$key];
			}else{
				$params[$key] = null;
			}
		}

		if(self::$DEBUG){
			self::_debug_output('Params Get',$params);
		}

		return $params;
	}

	static private function _check_activeTime($active_time = 0){
		if(abs(time() - $active_time) > self::ACTIVE_PERIOD){
			$result = array('isfine' => false,'message' => '参数已过期');
		}else{
			$result = array('isfine' => true,'message' => '');
		}

		return $result;
	}

	static private function _check_sign($params,$type = ''){
		if($type == 'redirect'){
			$resign = CPS360_config::BID
					.'#'.$params['active_time']
					.'#'.CPS360_config::CP_KEY
					.'#'.$params['qid']
					.'#'.$params['qmail']
					.'#'.$params['qname'];
		}else{
			$resign = CPS360_config::BID
					.'#'.$params['active_time']
					.'#'.CPS360_config::CP_KEY;
		}
		$resign = md5($resign);

		if ($params['sign'] && $params['sign'] !== $resign){
			$result = array('isfine' => false,'message' => '验证失败');
		}else{
			$result = array('isfine' => true,'message' => '');
		}

		return $result;
	}

	static private function _xml_generate($list){
		$list = is_array($list) ? $list : array();
		$xmldoc = $xmldoc_order = '';

		$i = 0;
		foreach($list as $obj){
			if(++$i > self::MAXNUM) break;
			$xmldoc_order .= $obj->to_xml()."\n";
		}

		$xmldoc = '<?xml version="1.0" encoding="utf-8"?>'."\n".'<orders>'."\n".$xmldoc_order.'</orders>';

		if(!mb_check_encoding($xmldoc,'UTF-8')){
			$xmldoc = mb_convert_encoding($xmldoc,'UTF-8','GBK');
		}

		return $xmldoc;
	}

	static private function _domainroot($url = ''){
		$url = $url ? $url : $_SERVER['SCRIPT_URI'];
		$url = 'http://'.str_replace(array('http://','https://'),'',$url);
		$parsed_url = parse_url($url);
		$host_array = array_reverse(explode('.',$parsed_url['host']));
		if(count($host_array) >= 2){
			return $host_array['1'].'.'.$host_array['0'];
		}else{
			return false;
		}
	}

	static private function _http_request($url,$method = 'get',$data = array()){
		$ch = curl_init();
		curl_setopt_array($ch,array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_USERAGENT => '',
				CURLOPT_TIMEOUT => 10,
				CURLOPT_URL => $url,
			)
		);

		//Post
	    if($method == 'post') {
	        $postdata = array();
	        foreach($data as $key => $value){
	            $postdata[] = urlencode($key).'='.urlencode($value);
	        }
			curl_setopt_array($ch,array(
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => implode('&',$postdata),
				)
			);
	    }

		curl_exec($ch);
		curl_close($ch);
	}

	static private function _ip_get(){
		$ip = '';
		if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
			$ip = getenv('HTTP_CLIENT_IP');
		} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
			$ip = getenv('REMOTE_ADDR');
		} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	static private function _plugin_load(){
		require('plugin/'.CPS360_config::PLUGIN_NAME.'.php');
		$classname = CPS360_config::PLUGIN_NAME;
		return new $classname;
	}

	static private function _output($content){
		if(!self::$DEBUG && stripos($content,'<?xml') !== false){
			header("Content-type: text/xml; charset=utf-8");
		}
		echo $content;
		exit;
	}

	static private function _debug_output($title,$data){
		$data = htmlspecialchars(print_r($data,true));
		echo '<h3>'.$title.'</h3>';
		echo '<pre>'.$data.'</pre>';
	}
	
	static private function _verify(){
		$pathroot = dirname(__FILE__);
		$content = 
'<?xml version="1.0" encoding="utf-8"?>
<verify>
<version>'.self::VERSION.'</version>
<build>'.self::BUILD.'</build>
<report_url>'.self::REPORT_URL.'</report_url>
<active_period>'.self::ACTIVE_PERIOD.'</active_period>
<maxnum>'.self::MAXNUM.'</maxnum>
<sign>
<api>'.md5(file_get_contents($pathroot.'/CPS360_api.class.php')).'</api>
<model>'.md5(file_get_contents($pathroot.'/CPS360_model.class.php')).'</model>
<plugin>'.md5(file_get_contents($pathroot.'/CPS360_plugin.class.php')).'</plugin>
</sign>
</verify>
';
		self::_output($content);
	}

	/********************************* CPS Global *********************************/

	static public function round($val,$precision = 0){
		/*
		 * BUGFIX: Round() 在 PHP 5.27 前规范不正确
		 * http://www.php.net/manual/en/function.round.php
		 */
		if(version_compare(PHP_VERSION,'5.3.0','>')){
			$val = round($val,$precision);
		}else{
			$val = floatval(sprintf('%f',$val));
			$precision = intval($precision);

			$pow = pow(10,$precision + 1);
			$val = round($val * $pow,-1);
			$val = sprintf('%.'.$precision.'f',$val / $pow);
		}

		return $val;
	}

	static public function serialize($var){
	   $str = serialize($var);
	   $md5 = md5(CPS360_config::CP_KEY.$str);

	   return urlencode($md5.$str);
	}

	static public function unserialize($str){
	    if(!$str) return false;
		$str = urldecode($str);

		$md5 = substr($str,0,32);
		$str = substr($str,32);

		$remd5 = md5(CPS360_config::CP_KEY.$str);
		if($md5 !== $remd5) return false;

		return unserialize($str);
	}

	static public function debug($val){
		self::$DEBUG = $val;
	}

}