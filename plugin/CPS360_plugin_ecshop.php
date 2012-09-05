<?PHP
class CPS360_plugin_ecshop extends CPS360_plugin{

	public $db = null;

	const VERSION				= '0.0.1';
	const BUILD					= '201207021800';
	const WEB_HOST				= 'http://www.xxx.com';

	public function __construct(){
	}

	/********************************* User Define *********************************/

	private function _order($method,$params){
		$extrasql = array('1');
		if($method == 'time'){
			$extrasql[] = 'add_time > '.$params['start_time'];
			$extrasql[] = 'add_time < '.$params['end_time'];
		}elseif($method == 'updtime'){
			$extrasql[] = 'o.update_time > '.$params['updstart_time'];
			$extrasql[] = 'o.update_time < '.$params['updend_time'];
		}elseif($method == 'ids'){
			$order_ids = explode(',', $params['order_ids']);
			$order_str = $nod ='';
			foreach($order_ids as $order_id) {
				$order_str .= $nod . mysql_escape_string($order_id);
				$nod = ',';
			}
			$extrasql[] = 'cps.order_id IN ('.$order_str.')';
		}elseif($method == 'check'){
			$timestamp = strtotime($params['bill_month']);
			$extrasql[] = 'add_time > '.mktime(0, 0 , 0,date("m",$timestamp),1,date("Y",$timestamp));
			$extrasql[] = 'add_time < '.mktime(23,59,59,date("m",$timestamp),date("t",$timestamp),date("Y",$timestamp));
			$extrasql[] = $this->order_check_sql_where('o');
		}
		if(in_array($method,array('time','updtime','check')) && $params['last_order_id']){
			$extrasql[] = 'cps.order_id > '.$params['last_order_id'];
		}
		$extrasql = implode(' AND ',$extrasql);

		$query_sql = 'SELECT cps.*, 
		o.order_id as id,o.add_time as create_time,o.update_time,o.order_status,o.shipping_status,o.pay_status,o.goods_amount,o.shipping_fee,o.discount,o.integral_money,o.bonus,
		o.consignee, p.region_name province, c.region_name city, d.region_name district, o.address, o.zipcode, o.tel, o.mobile
		FROM `'.CPS360_config::TABLE_CPS.'` cps
		JOIN '. $GLOBALS['ecs']->table('order_info') .' o
		LEFT JOIN ' . $GLOBALS['ecs']->table('region') . ' p on (o.province = p.region_id) 
		LEFT JOIN ' . $GLOBALS['ecs']->table('region') . ' c on (o.city = c.region_id) 
		LEFT JOIN ' . $GLOBALS['ecs']->table('region') . ' d on (o.district = d.region_id) 
		WHERE cps.order_id = o.order_sn AND '.$extrasql.'
		ORDER BY cps.order_id ASC LIMIT '.CPS360_api::MAXNUM;
		$order_rows = $GLOBALS['db'] -> getAll($query_sql);
		unset($query_sql);
		$orderlist = $order_ids = array();
		if($order_rows) {
			foreach ($order_rows as $pre_order) {
				$order_ids[] = $pre_order['id'];
				if($pre_order['pay_status'] == PS_PAYED) {
					$status = 4;
				}elseif($pre_order['shipping_status'] == SS_SHIPPED) {
					$status = 3;
				}elseif(in_array($pre_order['order_status'], array(OS_CONFIRMED,OS_SPLITED,OS_SPLITING_PART))) {
					$status = 2;
				}elseif(in_array($pre_order['order_status'], array(OS_RETURNED, OS_CANCELED, OS_INVALID))) {
					$status = 6;
				}else{
					$status = 1;
				}
				$orderlist[$pre_order['id']] = array (
					'qid'				=> $pre_order['qid'],													//CPS信息：360用户ID（来自跳转时传递的数据）
					'qihoo_id'			=> $pre_order['qihoo_id'],											//CPS信息：360业务编号（来自跳转时传递的数据）
					'ext'				=> $pre_order['ext'],													//CPS信息：360CPS扩展字段（来自跳转时传递的数据）
					'order_id'			=> $pre_order['order_id'],											//订单Id
					'order_link'		=> self::WEB_HOST . '/user.php?act=order_detail&order_id='. $pre_order['id'],					//订单页Url
					'order_time'		=> $pre_order['create_time'],											//订单下单时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
					'order_updtime'		=> $pre_order['update_time'],	//订单最后更新时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
					'server_price'		=> $pre_order['shipping_fee'],												//订单服务费、运费、手续费等附加费用
					'coupon'			=> $pre_order['discount'] + $pre_order['integral_money'] + $pre_order['bonus'], //优惠劵、积分、活动优惠金额总和
					'total_price'		=> $pre_order['goods_amount'],												//订单总价（不含服务费用，不含优惠劵金额）
					'status'			=> $status,														//订单状态（1新订单；2已确认尚未发货和支付；3已发货；4已支付；5已完成；6已取消）
					'products'			=> array(),
					'delivery'			=> array(												//收货人信息
						'isdefault'		=> false,						//是否为默认地址
						'name'			=> $pre_order['consignee'],						//收货：姓名
						'nation'		=> '中国',						//收货：国家(默认:中国)
						'state'			=> $pre_order['province'],					//收货：省份
						'city'			=> $pre_order['city'],					//收货：市
						'district'		=> $pre_order['district'],					//收货：区
						'address'		=> $pre_order['address'],	//收货：详细地址
						'zip'			=> $pre_order['zipcode'],					//收货：邮编
						'telphone'		=> $pre_order['tel'],				//收货：固定电话
						'mobile'		=> $pre_order['mobile'],				//收货：手机
					),
				);
				unset($order_rows);
			}
			if($order_ids) {
				$order_ids_str = implode(',', $order_ids);
				unset($order_ids);
				$goods_sql = 'select order_id, product_id,goods_name, goods_id,goods_price, goods_number from '
				 . $GLOBALS['ecs']->table('order_goods') . 'where order_id in (' . $order_ids_str . ') ';
				$order_goods = $GLOBALS['db'] -> getAll($goods_sql);
				if($order_goods) {
					foreach($order_goods as $pre_good) {
						if(isset($orderlist[$pre_good['order_id']])) {
							$orderlist[$pre_good['order_id']]['products'][] = array(
								'id'		=> $pre_good['goods_id'],	 //商品Id
								'name'		=> $pre_good['goods_name'],   //商品名称
								'url'		=> self::WEB_HOST . '/goods.php?id=' . $pre_good['goods_id'],	//商品URL
								'cateid'	=> $pre_good['product_id'],	 //商品分类Id
								'catename'	=> '',						//商品分类名称
								'price'		=> $pre_good['goods_price'], //商品单价
								'quantity'	=> $pre_good['goods_number'],  //商品数量
							);
						}
					}
					unset($order_goods);
				}
			}
			
		}

		//订单实例化
		$extraparam = array();
		foreach($orderlist as $order){
			if($method == 'check'){
				$result[] = new CPS360_model_check($order,$extraparam);
			}else{
				$result[] = new CPS360_model_order($order,$extraparam);
			}
		}

		return $result;
	}
	
	/**
	 * 对账SQL查询条件定义
	 * @param string $alias sql查询中表的别名
	 */
	private function order_check_sql_where($alias = 'o') {
		$sql_where = $alias . '.pay_status = ' . PS_PAYED . ' or ' . $alias . '.shipping_status = ' . SS_SHIPPED;
		return $sql_where;
	}

	/********************************* Abstract *********************************/

	public function order_save($order_id, $data = array()){
		if(!$order_id) return false;
		$cpsinfo = CPS360_api::unserialize($_COOKIE[CPS360_config::COOKIE_NAME]);
		if(!$cpsinfo) return false;

		//CPS信息
		$cpsdata = array(
			'order_id' => $order_id,
			'qid' => $cpsinfo['qid'],
			'qihoo_id' => $cpsinfo['qihoo_id'],
			'ext' => $cpsinfo['ext'],
		);
		
		//检查重复
		$where = ' order_id = ' . mysql_escape_string($order_id);
		$sql = "SELECT 1 FROM  " . CPS360_config::TABLE_CPS. ' WHERE ' . $where;
		$cpsdata_exists = $GLOBALS['db']->getOne($sql);
		
		//入库
		if(empty($cpsdata_exists)){
			$result = $GLOBALS['db']->autoExecute(CPS360_config::TABLE_CPS, $cpsdata, 'INSERT');
		}else{
			$result = true;
		}

		return $result;
	}

	public function login_auto($qid,$qmail,$qname){
		//TODO
		/**
		 * 根据用户名来判断  qname<span>360_$qid</span> 存在登陆
		 */
		$username = '360_'.($qname ? $qname : $qid);
//		$username = iconv("UTF-8","gbk//TRANSLIT",$username);		//编码转译
		$password=(time()+rand(100,1000));//创建密码，无用，为了表中不为空
		//用户存在直接登陆
		if($this->check_user($username)){
			$GLOBALS['user']->set_session($username);/* 设置登录session */
			$GLOBALS['user']->set_cookie($username);
			setcookie('user_type','360',time()+(3600*6),'/','');
		} else {
		//不存在直接写入数据库
			$reg_date = time();
			$ip = real_ip();	//获得用户的真实ip		
			$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table("users") . "(`email`, `user_name`, `password`, `reg_time`, `last_login`, `last_ip`) VALUES ('$qmail', '$username', '$password', '$reg_date', '$reg_date', '$ip')";
			/*插入数据库*/
			$GLOBALS['db']->query($sql);
			$user_id = mysql_insert_id();
			$GLOBALS['user']->set_session($username);/* 设置登录session */
			$GLOBALS['user']->set_cookie($username);/* 设置登录cookie */
			setcookie('user_type','360',time()+(3600*6),'/','');
		}
	}
	
	private function check_user($username){
		$sql = "SELECT user_id FROM  " . $GLOBALS['ecs']->table('users'). " WHERE user_name ='$username'";
		$row = $GLOBALS['db']->getRow($sql);
		   if (!empty($row)){
			return true;
		   }else{
			return false;
		   }
	}
	
	public function order_by_ids($order_ids){
		return self::_order('ids',array(
			'order_ids' => $order_ids
		));
	}

	public function order_by_time($start_time,$end_time,$last_order_id = ''){
		return self::_order('time',array(
			'start_time' => strtotime($start_time),
			'end_time' => strtotime($end_time),
			'last_order_id' => $last_order_id,
		));
	}

	public function order_by_updtime($updstart_time,$updend_time,$last_order_id = ''){
		return self::_order('updtime',array(
			'updstart_time' => strtotime($updstart_time),
			'updend_time' => strtotime($updend_time),
			'last_order_id' => $last_order_id,
		));
	}

	public function check_by_month($bill_month,$last_order_id = ''){
		return self::_order('check',array(
			'bill_month' => $bill_month,
			'last_order_id' => $last_order_id,
		));
	}

}