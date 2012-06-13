<?
define('IN_ECS', true);
class CPS360_plugin_ecshop extends CPS360_plugin{

	private $db;
	const ECS_ROOT_PATH = '/home/wangyuchen/workspace/ecshop/';

	/********************************* Abstract *********************************/

	public function login_auto($qid,$mail,$name){
		//TODO:执行登录逻辑
		setcookie('auth','test',0,'/',CPS360_config::COOKIE_DOMAIN);
	}

	public function order_by_ids($order_ids){
		return self::order('ids',array(
			'order_ids' => $order_ids
		));
	}

	public function order_by_time($start_time,$end_time,$last_order_id = ''){
		return self::order('time',array(
			'start_time' => $start_time,
			'end_time' => $end_time,
			'last_order_id' => $last_order_id,
		));
	}

	public function order_by_updtime($updstart_time,$updend_time,$last_order_id = ''){
		return self::order('updtime',array(
			'updstart_time' => $updstart_time,
			'updend_time' => $updend_time,
			'last_order_id' => $last_order_id,
		));
	}

	public function check_by_month($month,$last_order_id = ''){
		return self::order('time',array(
			'last_order_id' => $last_order_id,
		),'CPS360_model_check');
	}

	/********************************* User Define *********************************/

	public function __construct(){
		/* 初始化数据库类 */
		require(self::ECS_ROOT_PATH . 'data/config.php');
		require(self::ECS_ROOT_PATH . 'includes/cls_mysql.php');
		$this->db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
		$this->db->prefix = $prefix;
	}

	private function order($method,$params,$classname = 'CPS360_model_order'){
		$orderlist = $sql_where = array();

		//参数
		if($method == 'ids'){
			$sql_where[] = 'order_sn IN ('.$params['order_ids'].')';
		}elseif($method == 'time'){
			if(isset($params['last_order_id'])){
				$sql_where[] = 'order_sn > "'.$params['last_order_id'].'"';
			}
		}elseif($method == 'updtime'){
			if(isset($params['last_order_id'])){
				$sql_where[] = 'order_sn > "'.$params['last_order_id'].'"';
			}
		}
		
		//查询
		$query = $this->db->query("SELECT * FROM ".$this->db->prefix."order_info "
								.($sql_where ? 'WHERE '.implode(' AND ',$sql_where) : '')
								." ORDER BY order_id
								LIMIT ".CPS360_api::MAXNUM);
		while($row = $this->db->fetch_array($query)){
			$orderlist[$row['order_id']] = array(
				'qid' => '',
				'qihoo_id' => 36000,
				'ext' => '1339039506|0|0|0|152917633|36000|c4392',
				'order_id' => $row['order_sn'],
				'order_time' => $row['add_time'],
				'order_updtime' => max($row['add_time'],$row['confirm_time'],$row['pay_time'],$row['shipping_time']),
				'server_price' => $row['shipping_fee'] + $row['insure_fee'] + $row['pay_fee'] + $row['pack_fee'] + $row['card_fee'],
				'coupon' => $row['bonus'],
				'total_price' => $row['goods_amount'] - $row['bonus'],
				'status' => 1,
			);
			$orderids[] = $row['order_id'];
		}

		$query = $this->db->query("SELECT o.order_id,o.goods_id,o.goods_name,o.goods_sn,o.goods_number,o.goods_price,g.cat_id,c.cat_name
							FROM ".$this->db->prefix."order_goods o
							LEFT JOIN ".$this->db->prefix."goods g ON g.goods_id = o.goods_id
							LEFT JOIN ".$this->db->prefix."category c ON c.cat_id = g.cat_id
							WHERE order_id IN (".implode(',',$orderids).")");
		while($row = $this->db->fetch_array($query)){
			$orderlist[$row['order_id']]['products'][] = array(
				'id' => $row['goods_sn'],
				'name' => $row['goods_name'],
				'url' => 'http://ecshop.com/?id='.$row['goods_id'],
				'cateid' => $row['cat_id'],
				'catename' => $row['cat_name'],
				'price' => $row['goods_price'],
				'quantity' => $row['goods_number'],
			);
		}
		
		//实例化
		foreach($orderlist as $order_id => $order){
			$extraparam = array(
				'time' => date('Y-m-d H:i:s')
			);
			echo '<pre>'.var_export($order,1);
			$result[] = new $classname($order,$extraparam);
		}
		
		return $result;
	}

	public function order_save($order_id){
	    if(!$_COOKIE[CPS360_config::COOKIE_NAME]) return false;
		$cpsinfo = CPS360_api::unserialize($_COOKIE[CPS360_config::COOKIE_NAME]);
		if(!$cpsinfo) return false;
		
		//TODO 创建一个订单扩展表，将这些信息记录到订单中
	}

}