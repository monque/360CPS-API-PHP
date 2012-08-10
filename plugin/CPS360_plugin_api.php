<?
class CPS360_plugin_api extends CPS360_plugin{

	/********************************* User Define *********************************/

	private function _order($method,$params){

		//TODO:获取订单数据
		$orderlist = array(

			//订单数据格式示例
			array (
				'qid'				=> '',													//CPS信息：360用户ID（来自跳转时传递的数据）
				'qihoo_id'			=> 36000,												//CPS信息：360业务编号（来自跳转时传递的数据）
				'ext'				=> '1339039506|0|0|0|152917633|36000|c4392',			//CPS信息：360CPS扩展字段（来自跳转时传递的数据）
				'order_id'			=> '2012060712465',										//订单Id
				'order_link'		=> 'http://example.com/order/index',					//订单页Url
				'order_time'		=> '1339036840',										//订单下单时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
				'order_updtime'		=> '1339036840',										//订单最后更新时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
				'server_price'		=> 10.00,												//订单服务费、运费、手续费等附加费用
				'coupon'			=> 100,													//优惠劵、代金卷金额
				'total_price'		=> 100,													//订单总价（不含服务费用，不含优惠劵金额）
				'status'			=> 1,													//订单状态（1新订单；2已确认尚未发货和支付；3已发货；4已支付；5已完成；6已取消）
				'products'			=> array (												//订单内商品详情
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
				'delivery'			=> array(												//收货人信息
											'isdefault'		=> true,						//是否为默认地址
											'name'			=> '自己',						//收货：姓名
											'nation'		=> '中|国',						//收货：国家(默认:中国)
											'state'			=> '北京市',					//收货：省份
											'city'			=> '北京市',					//收货：市
											'district'		=> '朝阳区',					//收货：区
											'address'		=> '建国路71号惠通时代广场',	//收货：详细地址
											'zip'			=> '100025',					//收货：邮编
											'telphone'		=> '010-60000000',				//收货：固定电话
											'mobile'		=> '13800138000',				//收货：手机
										),
			)
			//......

		);


		//附加参数，可供佣金计算时使用
		$extraparam = '';

		//订单实例化
		foreach($orderlist as $order){
			if($method == 'check'){
				$result[] = new CPS360_model_check($order,$extraparam);
			}else{
				$result[] = new CPS360_model_order($order,$extraparam);
			}
		}

		return $result;
	}

	/********************************* Abstract *********************************/

    /*
     * 保存CPS订单，订单保存完毕后调用该函数，如果有CPS的Cookie信息，则将相应CPS信息记录
     *
     * $order_id Integer 订单号
     * $data Array 订单数据
     *
     * return String 反馈状态
     */
	public function order_save($order_id,$data){
	    if(!$_COOKIE[CPS360_config::COOKIE_NAME]) return false;
		$cpsinfo = CPS360_api::unserialize($_COOKIE[CPS360_config::COOKIE_NAME]);
		if(!$cpsinfo) return false;

		//TODO 创建一个订单扩展表，将这些信息记录到订单中
		//save_to_db($data);
	}

	/*
	 * 用户自动登录，如果用户尚未注册，则通过用户信息自动注册账号
	 * 自动注册时，密码可随机产生，切勿使用简单密码
	 *
	 * $qid string 360用户id
	 * $qname string 360用户名称
	 * $qmail string 360用户邮箱
	 *
	 * return void
	 */
	public function login_auto($qid,$mail,$name){
		//TODO:执行登录逻辑
	}

    /*
     * 通过订单Id返回查询订单的数据
     *
     * $order_ids array 请求参数
     *
     * return Array 返回订单列表
     */
	public function order_by_ids($order_ids){
		return self::_order('ids',array(
			'order_ids' => $order_ids
		));
	}

    /*
     * 通过下单时间返回查询订单的数据，查询结果需要按照订单号排序
     *
     * $start_time datetime 查询开始时间
	 * $end_time datetime 查询开始时间
	 * $last_order_id string 查询订单条件，如果该参数不为空则需要返回大于该订单的订单
     *
     * return Array 返回订单列表
     */
	public function order_by_time($start_time,$end_time,$last_order_id = ''){
		return self::_order('time',array(
			'start_time' => strtotime($start_time),
			'end_time' => strtotime($end_time),
			'last_order_id' => intval($last_order_id),
		));
	}

    /*
     * 按照订单最后更新时间返回查询订单的数据，查询结果需要按照订单号排序
     *
	 * $updstart_time datetime 查询开始时间
	 * $updend_time datetime 查询开始时间
	 * $last_order_id string 查询订单条件，如果该参数不为空则需要返回大于该订单的订单
     *
     * return Array 返回订单列表
     */
	public function order_by_updtime($updstart_time,$updend_time,$last_order_id = ''){
		return self::_order('updtime',array(
			'updstart_time' => strtotime($updstart_time),
			'updend_time' => strtotime($updend_time),
			'last_order_id' => intval($last_order_id),
		));
	}

    /*
     * 返回对账订单的数据，查询结果需要按照订单号排序
     *
	 * $month date 查询开始时间
	 * $last_order_id string 查询订单条件，如果该参数不为空则需要返回大于该订单的订单
     *
     * return Array 返回订单列表
     */
	public function check_by_month($bill_month,$last_order_id = ''){
		return self::_order('check',array(
			'bill_month' => $bill_month,
			'last_order_id' => intval($last_order_id),
		));
	}

}