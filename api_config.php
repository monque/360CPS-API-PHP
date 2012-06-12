<?PHP
//FIXME
error_reporting(E_ALL);

class CPS360_config{

	/********************************* CPS 信息 *********************************/

	//合作编号,由360CPS分配
	const BID				= '2000285';

	//密钥,由360CPS系统
	const CP_KEY			= '10BC5D3FBF881322484F3932BCA52583';

	//Cookie的有效期长,由商务上确定，一般是30天
	const RD				= '30';

	//佣金比例：Array( 商品分类id => 佣金比例 )，商品分类id为0时为默认。
	static public function COMMRATE(){

		$rate = 0.1;

		return $rate;
	}

	/********************************* CPS 接口设置 *********************************/

	//插件名称，路径为"plugins/{PLUGIN_NAME}"，类名为"{PLUGIN_NAME}"
	const PLUGIN_NAME		= 'CPS360_plugin_ecshop';

	//Cookie名称
	const COOKIE_NAME		= 'cpsinfo';

	//Cookie所在域
	const COOKIE_DOMAIN		= 'example.com';

	//默认跳转地址
	const REDIRECT_DEFAULT	= 'http://www.REDIRECT_DEFAULT.com';

}