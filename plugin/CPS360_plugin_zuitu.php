<?PHP
class CPS360_plugin_zuitu extends CPS360_plugin{

	const VERSION				= '0.0.5';
	const BUILD					= '201207241200';

	public function __construct(){
		require_once(dirname(__FILE__).'/../../app.php');
	}

	/********************************* User Define *********************************/

	private function _order($method,$params){

		$extrasql = array('1');
		if($method == 'time'){
			$extrasql[] = 'o.create_time > '.$params['start_time'];
			$extrasql[] = 'o.create_time < '.$params['end_time'];
		}elseif($method == 'updtime'){
			$extrasql[] = 'o.update_time > '.$params['updstart_time'];
			$extrasql[] = 'o.update_time < '.$params['updend_time'];
		}elseif($method == 'ids'){
		    $order_ids = explode(',', $params['order_ids']);
            foreach($order_ids as &$order_id) {
                $order_id = addslashes($order_id);
            }
            $order_ids = implode(',',$order_ids);
			$extrasql[] = 'cps.order_id IN ('.$order_ids.')';
		}elseif($method == 'check'){
			$timestamp = strtotime($params['bill_month']);
			$extrasql[] = 'o.state = "pay"';
			$extrasql[] = 'o.create_time >= '.mktime(0, 0 , 0,date("m",$timestamp),1,date("Y",$timestamp));
			$extrasql[] = 'o.create_time <= '.mktime(23,59,59,date("m",$timestamp),date("t",$timestamp),date("Y",$timestamp));
		}
		if(in_array($method,array('time','updtime','check')) && $params['last_order_id']){
			$extrasql[] = 'cps.order_id > '.$params['last_order_id'];
		}
		$extrasql = implode(' AND ',$extrasql);

		$orderlist = array();
		$query = DB::Query('SELECT cps.*
		,o.id order_id,o.create_time,o.pay_time,o.fare,o.card,o.origin,o.state,o.rstate
		,o.team_id,t.product,t.group_id,t.team_price
		,c.name group_name
		FROM `'.CPS360_config::TABLE_CPS.'` cps
		LEFT JOIN `order` o ON cps.order_id = o.id
		LEFT JOIN `team` t ON t.id = o.team_id
		LEFT JOIN `category` c ON t.group_id = c.id
		WHERE 1 AND '.$extrasql.'
		ORDER BY cps.order_id ASC LIMIT '.CPS360_api::MAXNUM);
		while($row = DB::NextRecord($query)){
			
			//Product
			$products = unserialize($row['products']);
			
			//Status
			$status = 1;
			if($row['state'] == 'unpay'){
				if($row['rstate'] == 'berefund'){
					$status = 6;
				}else{
					$status = 1;
				}
			}elseif($row['state'] == 'pay'){
				if(time() - $row['pay_time'] > 86400*15){
					$status = 5;
				}else{
					$status = 4;
				}
			}
			
			$orderlist[] = 			array (
				'qid'				=> $row['qid'],													//CPS信息：360用户ID（来自跳转时传递的数据）
				'qihoo_id'			=> $row['qihoo_id'],											//CPS信息：360业务编号（来自跳转时传递的数据）
				'ext'				=> $row['ext'],													//CPS信息：360CPS扩展字段（来自跳转时传递的数据）
				'order_id'			=> $row['order_id'],											//订单Id
				'order_time'		=> $row['create_time'],											//订单下单时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
				'order_updtime'		=> max($row['pay_time'],$row['create_time'],$row['dateline']),	//订单最后更新时间（格式：时间戳、YYYY-MM-DD HH-II-SS）
				'server_price'		=> $row['fare'],												//订单服务费、运费、手续费等附加费用
				'coupon'			=> $row['card'],												//优惠劵、代金卷金额
				'total_price'		=> $row['origin'],												//订单总价（不含服务费用，不含优惠劵金额）
				'status'			=> $status,														//订单状态（1新订单；2已确认尚未发货和支付；3已发货；4已支付；5已完成；6已取消）
				'products'			=> $products,
			);
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

	/********************************* Abstract *********************************/

	public function order_save($order_id,$data){
		if(!$order_id) return false;
		$cpsinfo = CPS360_api::unserialize($_COOKIE[CPS360_config::COOKIE_NAME]);
		if(!$cpsinfo) return false;

		//CPS信息
		$cpsdata = array(
			'order_id' => $order_id,
			'qid' => $cpsinfo['qid'],
			'qihoo_id' => $cpsinfo['qihoo_id'],
			'ext' => $cpsinfo['ext'],
			'products' => serialize($data),
			'dateline' => time(),
		);

		//检查重复
		$cpsdata_exists = Table::Fetch(CPS360_config::TABLE_CPS,array('order_id' => $order_id),'order_id');

		//入库
		if(empty($cpsdata_exists)){
			$result = DB::Insert(CPS360_config::TABLE_CPS,$cpsdata);
		}else{
			$result = DB::Update(CPS360_config::TABLE_CPS,$cpsdata_exists['0']['id'],$cpsdata);
		}

		return $result;
	}

	public function login_auto($qid,$mail,$name){

		//检查用户是否存在
		$user_id = 0;
		$cpsuser_sign = '360:'.$qid;
		$user = DB::GetTableRow('user',array('sns' => $cpsuser_sign));
		if(!$user){

			//重名处理
			$i = null;
			do{
				if(is_null($i)){
					$cpsuser_username = $name;
					$i = 0;
				}else{
					if($i == 0){
						$cpsuser_username = '360'.$name;
					}else{
						$cpsuser_username = $name.'_'.$i;
					}
					$i++;
				}
				$user_exists = DB::GetTableRow('user',array('username' => $cpsuser_username));
			}while($user_exists);

			//邮箱重复处理
			$i = null;
			do{
				if(is_null($i)){
					$cpsuser_mail = $mail;
					$i = 0;
				}else{
					if($i == 0){
						$cpsuser_mail = '360'.$mail;
					}else{
						$cpsuser_mail = $mail.'_'.$i;
					}
					$i++;
				}
				$user_exists = DB::GetTableRow('user',array('email' => $cpsuser_mail));
			}while($user_exists);

			//建立新用户
			$user_new = array(
				'username' => $cpsuser_username,
				'realname' => $name,
				'email' => $cpsuser_mail,
				'password' => rand(10000000,99999999),
				'gender' => 'M',
				'sns' => $cpsuser_sign,
			);
			if($user_id = ZUser::Create($user_new,true)){
				Session::Set('user_id', $user_id);
			}
		}else{
			//登录
			Session::Set('user_id', $user['id']);
			ZLogin::Remember($user);
			ZUser::SynLogin($user['email'], $user['password']);
			$user_id = $user['id'];
		}

		return $user_id;
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
			'last_order_id' => intval($last_order_id),
		));
	}

	public function order_by_updtime($updstart_time,$updend_time,$last_order_id = ''){
		return self::_order('updtime',array(
			'updstart_time' => strtotime($updstart_time),
			'updend_time' => strtotime($updend_time),
			'last_order_id' => intval($last_order_id),
		));
	}

	public function check_by_month($bill_month,$last_order_id = ''){
		return self::_order('check',array(
			'bill_month' => $bill_month,
			'last_order_id' => intval($last_order_id),
		));
	}

}