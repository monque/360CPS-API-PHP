<?
define('IN_ECS', true);
class CPS360_plugin_ecshop extends CPS360_plugin{

	private $db;
	const ECS_ROOT_PATH = '/home/wangyuchen/workspace/ecshop/';

	/********************************* User Define *********************************/

	public function __construct(){
		/* 初始化数据库类 */
		require(self::ECS_ROOT_PATH . 'data/config.php');
		require(self::ECS_ROOT_PATH . 'includes/cls_mysql.php');
		$this->db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
	}

	private function order($method,$params){
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
		$query = $this->db->query("SELECT * 
								FROM ecs_order_info "
								.($sql_where ? 'WHERE '.implode(' AND ',$sql_where) : '')
								." ORDER BY order_id
								LIMIT ".CPS360_api::MAXNUM);
		while($row = $this->db->fetch_array($query)){
			$orderlist[$row['order_id']] = new CPS360_model_order(array(
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
			));
			$orderids[] = $row['order_id'];
		}

		$query = $this->db->query("SELECT
							  o.`order_id`,
							  o.goods_id,
							  o.goods_name,
							  o.goods_sn,
							  o.goods_number,
							  o.goods_price,
							  g.`cat_id`,
							  c.`cat_name`
							FROM `ecs_order_goods` o
							LEFT JOIN `ecs_goods` g ON g.goods_id = o.goods_id
							LEFT JOIN ecs_category c ON c.`cat_id` = g.`cat_id`
							WHERE order_id IN (".implode(',',$orderids).")");
		while($row = $this->db->fetch_array($query)){
			$orderlist[$row['order_id']]->add_product(array(
				'id' => $row['goods_sn'],
				'name' => $row['goods_name'],
				'url' => 'http://ecshop.com/?id='.$row['goods_id'],
				'cateid' => $row['cat_id'],
				'catename' => $row['cat_name'],
				'price' => $row['goods_price'],
				'quantity' => $row['goods_number'],
			));
		}

		return $orderlist;
	}

	/********************************* Abstract *********************************/

	public function login_auto($qid,$mail,$name){
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
		));
	}

}