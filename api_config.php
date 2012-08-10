<?PHP
class CPS360_config{

	/********************************* CPS 信息 *********************************/

	//合作编号,由360CPS分配
	const BID				= '20120822';

	//密钥,由360CPS系统
	const CP_KEY			= '302416069AC85129553707D5A10F1718';

	//Cookie的有效期长,由商务上确定，一般是30天
	const RD				= '30';

	/*
	 * 佣金比例计算
	 * 
	 * 注意：自行开发请参考下方例子
	 *
	 * $product object 商品对象
	 * $order object 订单对象
	 *
	 * return float 返回佣金比例
	 *
	 */
	static public function COMMRATE($product,$order){
		$rate = false;

		/*
		注意：
		1.在此处可以通过 $order->attr('extraparam') 方法调用从Plugin传递的extraparam参数
		*/

		/*
		方法一
		佣金比例按商品分类制定*/
		switch($product->attr('cateid')){
			case 1:
				$rate = 0.1;
				break;
			default:
				$rate = 0.8;
		}
		
		
		/*
		方法二
		佣金比例按订单总价制定
		if($order->attr('total_price') >= 0 && $order->attr('total_price') < 100){
			$rate = 0.01;
		}elseif($order->attr('total_price') >= 100 && $order->attr('total_price') < 1000){
			$rate = 0.1;
		}elseif($order->attr('total_price') >= 1000){
			$rate = 0.2;
		}
		*/
		
		/*
		方法三
		固定金额佣金
		$comm = 100;
		$rate = CPS360_api::round($comm / $order->attr('total_price'),6);
		*/
		

		return $rate;
	}

	/********************************* CPS 接口设置 *********************************/

	//插件名称，路径为"plugins/{PLUGIN_NAME}"，类名为"{PLUGIN_NAME}"
	const PLUGIN_NAME		= 'CPS360_plugin_api';

	//CPS表名
	const TABLE_CPS			= '360cps';

	//Cookie名称
	const COOKIE_NAME		= 'cpsinfo';

	//Cookie所在域
	const COOKIE_DOMAIN		= 'example.com';

	//默认跳转地址
	const REDIRECT_DEFAULT	= 'http://www.example.com';
	
	//时区设置
	const TIME_ZONE			= 'Asia/Chongqing';

}