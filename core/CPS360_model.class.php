<?PHP
class CPS360_model{

	public function attr($key,$value = null){
		if(!isset($value)){
			return $this->$key;
		}else{
			return $this->$key = $value;
		}
	}

	protected function _format_date($val){
		$val = empty($val) ? 0 : $val;
		if(is_numeric($val)){
			$val = date('Y-m-d H:i:s',$val);
		}

		return $val;
	}

	protected function _format_money($val){
		$val = $val < 0 ? 0 : floatval($val);
		return CPS360_api::round($val,2);
	}

	protected function _format_url($val){
		return (stripos($val,'%3A%2F%2F') !== false) ? $val : urlencode($val);
	}

	protected function _format_percent($val){
		return ($val * 100).'%';
	}

	protected function _format_trim($array){
		foreach($array as &$value){
			$value = trim($value);
		}

		return $array;
	}

}

class CPS360_model_product extends CPS360_model{
	protected $id;
	protected $name;
	protected $url;

	protected $cateid;
	protected $catename;

	protected $price;
	protected $quantity;

	protected $commrate;
	protected $commrate_percent;
	protected $commamount;

	public function __construct($data){
		//Info

		$this->id = $data['id'];
		$this->name = $data['name'];
		$this->url = $this->_format_url($data['url']);
		$this->cateid = $data['cateid'];
		$this->catename = $data['catename'];
		$this->price = $this->_format_money($data['price']);
		$this->quantity = $data['quantity'];

		//Commission
		$this->commrate = CPS360_config::COMMRATE($this->cateid);
		$this->commrate_percent = $this->_format_percent($this->commrate);
		$this->commamount = $this->_format_money($this->commrate * $this->price * $this->quantity);
	}

	public function to_pinfo(){
		return implode(',',array($this->cateid,$this->name,$this->id,$this->price,$this->quantity,$this->catename,$this->url));
	}

	public function to_commission(){
		return implode(',',array($this->cateid,$this->commrate_percent,$this->commamount,$this->price,$this->quantity));
	}

	public function get_amount(){
		return $this->_format_money($this->price * $this->quantity);
	}

	public function get_commamount(){
		return $this->commamount;
	}
}

class CPS360_model_order extends CPS360_model{
	private $bid;
	private $qid;
	private $qihoo_id;
	private $ext;

	private $order_id;
	private $order_time;
	private $order_updtime;
	private $status;

	private $server_price;
	private $coupon;
	private $total_price;
	private $total_comm;
	private $commission;
	private $p_info;

	private $products;
	private $coupon_minrate;

	public function __construct($data){
		$this->bid = CPS360_config::BID;

		//Info
		foreach($data as &$value){
			$value = trim($value);
		}
		$this->qid = $data['qid'];
		$this->qihoo_id = $data['qihoo_id'];
		$this->ext = $data['ext'];

		$this->order_id = $data['order_id'];
		$this->order_time = $this->_format_date($data['order_time']);
		$this->order_updtime = $this->_format_date($data['order_updtime']);
		$this->status = $data['status'];

		$this->server_price = $this->_format_money($data['server_price']);
		$this->coupon = $this->_format_money($data['coupon']);
		$this->total_price = $this->_format_money($data['total_price']);
		$this->total_comm = 0;

	}

	public function add_product($data){
		$obj = new CPS360_model_product($data);
		$this->products[] = $obj;
		$this->p_info[] = $obj->to_pinfo();
		$this->commission[] = $obj->to_commission();
		$this->total_comm += $obj->get_commamount();
		if($obj->attr('commrate') < $this->coupon_minrate && $obj->attr('commrate') > 0 || !isset($this->coupon_minrate)){
			$this->coupon_minrate = $obj->attr('commrate');
		}
	}

	public function to_xml(){
		$this->coupon_comm = $this->_format_money($this->coupon * $this->coupon_minrate);
		$this->total_comm = $this->_format_money($this->total_comm - $this->coupon_comm);

		$xmldoc =
'<order>
	<bid>'.$this->bid.'</bid>
	<qid>'.$this->qid.'</qid>
	<qihoo_id>'.$this->qihoo_id.'</qihoo_id>
	<ext>'.$this->ext.'</ext>
	<order_id>'.$this->order_id.'</order_id>
	<order_time>'.$this->order_time.'</order_time>
	<order_updtime>'.$this->order_updtime.'</order_updtime>
	<status>'.$this->status.'</status>
	<server_price>'.$this->server_price.'</server_price>
	<coupon>'.$this->coupon.'</coupon>
	<total_price>'.$this->total_price.'</total_price>
	<total_comm>'.$this->total_comm.'</total_comm>
	<commission>'.implode('|',$this->commission).'|'.($this->coupon_comm).'</commission>
	<p_info>'.implode('|',$this->p_info).'</p_info>
</order>';
		return $xmldoc;
	}
}

class CPS360_model_check extends CPS360_model{
	private $order_id;
	private $order_time;
	private $order_updtime;

	private $server_price;
	private $coupon;
	private $total_price;
	private $total_comm;
	private $commission;

	private $products;
	private $coupon_minrate;

	public function __construct($data){
		$this->bid = CPS360_config::BID;

		//Info
		foreach($data as &$value){
			$value = trim($value);
		}
		$this->qid = $data['qid'];
		$this->qihoo_id = $data['qihoo_id'];
		$this->ext = $data['ext'];

		$this->order_id = $data['order_id'];
		$this->order_time = $this->_format_date($data['order_time']);
		$this->order_updtime = $this->_format_date($data['order_updtime']);
		$this->status = $data['status'];

		$this->server_price = $this->_format_money($data['server_price']);
		$this->coupon = $this->_format_money($data['coupon']);
		$this->total_price = $this->_format_money($data['total_price']);
		$this->total_comm = 0;

	}

	public function add_product($data){
		$obj = new CPS360_model_product($data);
		$this->products[] = $obj;
		$this->p_info[] = $obj->to_pinfo();
		$this->commission[] = $obj->to_commission();
		$this->total_comm += $obj->get_commamount();
		if($obj->attr('commrate') < $this->coupon_minrate && $obj->attr('commrate') > 0 || !isset($this->coupon_minrate)){
			$this->coupon_minrate = $obj->attr('commrate');
		}
	}

	public function to_xml(){
		$this->coupon_comm = $this->_format_money($this->coupon * $this->coupon_minrate);
		$this->total_comm = $this->_format_money($this->total_comm - $this->coupon_comm);

		$xmldoc =
'<order>
	<bid>'.$this->bid.'</bid>
	<qid>'.$this->qid.'</qid>
	<qihoo_id>'.$this->qihoo_id.'</qihoo_id>
	<ext>'.$this->ext.'</ext>
	<order_id>'.$this->order_id.'</order_id>
	<order_time>'.$this->order_time.'</order_time>
	<order_updtime>'.$this->order_updtime.'</order_updtime>
	<status>'.$this->status.'</status>
	<server_price>'.$this->server_price.'</server_price>
	<coupon>'.$this->coupon.'</coupon>
	<total_price>'.$this->total_price.'</total_price>
	<total_comm>'.$this->total_comm.'</total_comm>
	<commission>'.implode('|',$this->commission).'|'.($this->coupon_comm).'</commission>
	<p_info>'.implode('|',$this->p_info).'</p_info>
</order>';
		return $xmldoc;
	}
}