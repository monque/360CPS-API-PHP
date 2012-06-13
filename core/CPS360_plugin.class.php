<?PHP
abstract class CPS360_plugin{

    /*
     * 保存cps订单，订单保存完毕后调用该函数，如果有CPS的Cookie信息，则将相应CPS信息记录
     *
     * $order_id Integer 反馈订单号
     *
     * return String 反馈状态
     */
	abstract public function order_save($order_id);

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
	abstract public function login_auto($qid,$mail,$name);

    /*
     * 通过订单Id返回查询订单的数据
     *
     * $order_ids array 请求参数
     *
     * return Array 返回订单列表
     */
	abstract public function order_by_ids($order_ids);

    /*
     * 通过下单时间返回查询订单的数据，查询结果需要按照订单号排序
     *
     * $start_time datetime 查询开始时间
	 * $end_time datetime 查询开始时间
	 * $last_order_id string 查询订单条件，如果该参数不为空则需要返回大于该订单的订单
     *
     * return Array 返回订单列表
     */
	abstract public function order_by_time($start_time,$end_time,$last_order_id = '');

    /*
     * 按照订单最后更新时间返回查询订单的数据，查询结果需要按照订单号排序
     *
	 * $updstart_time datetime 查询开始时间
	 * $updend_time datetime 查询开始时间
	 * $last_order_id string 查询订单条件，如果该参数不为空则需要返回大于该订单的订单
     *
     * return Array 返回订单列表
     */
	abstract public function order_by_updtime($updstart_time,$updend_time,$last_order_id = '');

    /*
     * 返回对账订单的数据，查询结果需要按照订单号排序
     *
	 * $month date 查询开始时间
	 * $last_order_id string 查询订单条件，如果该参数不为空则需要返回大于该订单的订单
     *
     * return Array 返回订单列表
     */
	abstract public function check_by_month($month,$last_order_id = '');

}