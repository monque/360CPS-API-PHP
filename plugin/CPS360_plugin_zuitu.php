<?
class CPS360_plugin_zuitu extends CPS360_plugin{

	public function __construct(){
		require_once('/home/wangyuchen/workspace/tuan_zuitu/app.php');
				
	}

	/********************************* User Define *********************************/

	private function _order($method,$params){
		
		$unions = DB::LimitQuery('user', array(
			'condition' => '',
			'order' => '',
			'size' => 1000,
		));
		var_dump($unions);
		
		
		//TODO:获取订单数据
		$orderlist = array(		
			
			//订单数据格式示例
			array (
				'qid'				=> '',													//CPS信息：360用户ID（来自跳转时传递的数据）
				'qihoo_id'			=> 36000,												//CPS信息：360业务编号（来自跳转时传递的数据）
				'ext'				=> '1339039506|0|0|0|152917633|36000|c4392',			//CPS信息：360CPS扩展字段（来自跳转时传递的数据）
				'order_id'			=> '2012060712465',										//订单Id
				'order_time'		=> '1339036840',										//订单下单时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
				'order_updtime'		=> '1339036840',										//订单最后更新时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
				'server_price'		=> 10.00,												//订单服务费、运费、手续费等附加费用
				'coupon'			=> 100,													//优惠劵、代金卷金额
				'total_price'		=> 100,													//订单总价（不含服务费用，不含优惠劵金额）
				'status'			=> 1,													//订单状态（1新订单；2已确认尚未发货和支付；3已发货；4已支付；5已完成；6已取消）
				//订单内商品详情
				'products'			=> array (
											0 => array (
												'id'		=> 'ECS000001',					//商品Id
												'name'		=> '望远镜',					//商品名称
												'url'		=> 'http://domain.com/?id=1',	//商品URL
												'cateid'	=> '1',							//商品分类Id
												'catename'	=> '测试',						//商品分类名称
												'price'		=> 100.00,						//商品单价
												'quantity'	=> 2,							//商品数量
											),
											//......
										),
			)
			//......
			
		);


		//附加参数，可供佣金计算时使用
		$extraparam = '';

		//订单实例化
		foreach($orderlist as $order){
			$result[] = new CPS360_model_order($order,$extraparam);
		}

		return $result;
	}

	private function _check($params){
		
		//TODO:获取订单数据
		$orderlist = array(		
			
			//订单数据格式示例
			array (
				'order_id'			=> '2012060712465',										//订单Id
				'order_time'		=> '1339036840',										//订单下单时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
				'order_updtime'		=> '1339036840',										//订单最后更新时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
				'server_price'		=> 10.00,												//订单服务费、运费、手续费等附加费用
				'coupon'			=> 100,													//优惠劵、代金卷金额
				'total_price'		=> 100,													//订单总价（不含服务费用，不含优惠劵金额）
				'status'			=> 1,													//订单状态（1新订单；2已确认尚未发货和支付；3已发货；4已支付；5已完成；6已取消）
				//订单内商品详情
				'products'			=> array (
											0 => array (
												'id'		=> 'ECS000001',					//商品Id
												'name'		=> '望远镜',					//商品名称
												'url'		=> 'http://domain.com/?id=1',	//商品URL
												'cateid'	=> '1',							//商品分类Id
												'catename'	=> '测试',						//商品分类名称
												'price'		=> 100.00,						//商品单价
												'quantity'	=> 2,							//商品数量
											),
											//......
										),
			)
			//......
			
		);


		//附加参数，可供佣金计算时使用
		$extraparam = '';

		//订单实例化
		foreach($orderlist as $order){
			$result[] = new CPS360_model_check($order,$extraparam);
		}

		return $result;
	}

	/********************************* Abstract *********************************/

	public function order_save($order_id,$data){
	    if(!$_COOKIE[CPS360_config::COOKIE_NAME]) return false;
		$cpsinfo = CPS360_api::unserialize($_COOKIE[CPS360_config::COOKIE_NAME]);
		if(!$cpsinfo) return false;

		//TODO 创建一个订单扩展表，将这些信息记录到订单中
		//save_to_db($data);
	}

	public function login_auto($qid,$mail,$name){
		//TODO:执行登录逻辑
	}

	public function order_by_ids($order_ids){
		return self::_order('ids',array(
			'order_ids' => $order_ids
		));
	}

	public function order_by_time($start_time,$end_time,$last_order_id = ''){
		return self::_order('time',array(
			'start_time' => $start_time,
			'end_time' => $end_time,
			'last_order_id' => $last_order_id,
		));
	}

	public function order_by_updtime($updstart_time,$updend_time,$last_order_id = ''){
		return self::_order('updtime',array(
			'updstart_time' => $updstart_time,
			'updend_time' => $updend_time,
			'last_order_id' => $last_order_id,
		));
	}

	public function check_by_month($month,$last_order_id = ''){
		return self::_check(array(
			'month' => $month,
			'last_order_id' => $last_order_id,
		));
	}

}