<?PHP
class CPS360_model{

	protected $extraparam;

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

	protected function _format_money($val,$isabs = false){
		if($isabs){
			$val = abs(floatval($val));
		}else{
			$val = $val < 0 ? 0 : floatval($val);
		}
		return CPS360_api::round($val,2);
	}

	protected function _format_url($val){
		return (stripos($val,'%3A%2F%2F') !== false) ? $val : urlencode($val);
	}

	protected function _format_percent($val){
		return ($val * 100).'%';
	}

	protected function _format_microlist($val,$isprocess = false){
		if($isprocess){
			$val = str_replace(array(',','|'),'',$val);
		}else{
			$val = $this->_recursionarray($val,'_format_microlist');
		}
		
		return $val;
	}

	protected function _format_xmlcompatible($val,$isprocess = false){
		if($isprocess){
			$val =htmlspecialchars($val);
		}else{
			$val = $this->_recursionarray($val,'_format_xmlcompatible');
		}
		
		return $val;
	}

	protected function _format_trim($val,$isprocess = false){
		if($isprocess){
			$val =trim($val);
		}else{
			$val = $this->_recursionarray($val,'_format_trim');
		}
		
		return $val;
	}
	
	private function _recursionarray($val,$callback){
		if(is_array($val)){
			foreach($val as &$v){
				$v = $this->_recursionarray($v,$callback);
			}
		}else{
			$val = $this->$callback($val,true);
		}

		return $val;
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

	public function __construct($data,$order){
		$data = $this->_format_microlist($this->_format_xmlcompatible($this->_format_trim($data)));

		//Info
		$this->id = $data['id'];
		$this->name = $data['name'];
		$this->url = $this->_format_url($data['url']);
		$this->cateid = $data['cateid'];
		$this->catename = $data['catename'];
		$this->price = $this->_format_money($data['price']);
		$this->quantity = intval($data['quantity']);

		//Commission
		$this->commrate = CPS360_config::COMMRATE($this,$order);
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

class CPS360_model_node extends CPS360_model{
	protected $order_id;
	protected $order_time;
	protected $order_updtime;
	protected $status;

	protected $server_price;
	protected $coupon;
	protected $total_price;
	protected $total_comm;
	protected $commission;
	protected $p_info;

	protected $products;
	protected $comm_coupon;
	protected $comm_product;
	protected $coupon_minrate;

	public function __construct($data,$extraparam = ''){
		//Extraparam
		$this->extraparam = $extraparam;

		//Order Info
		$this->order_id = $data['order_id'];
		$this->order_time = $this->_format_date($data['order_time']);
		$this->order_updtime = $this->_format_date($data['order_updtime']);
		$this->status = $data['status'];

		$this->server_price = $this->_format_money($data['server_price'],true);
		$this->total_price = $this->_format_money($data['total_price']);
		$this->coupon = $this->_format_money($data['coupon'],true);
		$this->coupon = $this->coupon > $this->total_price ? $this->total_price : $this->coupon;
		$this->total_comm = 0;

		//Products
		$this->product_fill($data['products']);
	}

	public function product_add($data){
		$obj = new CPS360_model_product($data,$this);
		$this->products[] = $obj;
		$this->p_info[] = $obj->to_pinfo();
		$this->comm_product[] = $obj->to_commission();
		$this->total_comm += $obj->get_commamount();
		if($obj->attr('commrate') < $this->coupon_minrate && $obj->attr('commrate') > 0 || !isset($this->coupon_minrate)){
			$this->coupon_minrate = $obj->attr('commrate');
		}
	}

	public function product_fill($data){
		$data = is_array($data) ? $data : array();

		foreach($data as $value){
			$this->product_add($value);
		}

		$this->comm_coupon = $this->_format_money($this->coupon * $this->coupon_minrate);
		$this->total_comm = $this->_format_money($this->total_comm - $this->comm_coupon);
	}
}

class CPS360_model_order extends CPS360_model_node{
	protected $bid;
	protected $qid;
	protected $qihoo_id;
	protected $ext;

	public function __construct($data,$extraparam = ''){
		$data = $this->_format_xmlcompatible($this->_format_trim($data));
		parent::__construct($data,$extraparam);

		//CPS Info
		$this->bid = CPS360_config::BID;
		$this->qid = $data['qid'];
		$this->qihoo_id = $data['qihoo_id'];
		$this->ext = $data['ext'];
	}

	public function to_xml(){
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
	<commission>'.implode('|',$this->comm_product).'|'.($this->comm_coupon).'</commission>
	<p_info>'.implode('|',$this->p_info).'</p_info>
</order>';
		return $xmldoc;
	}
}

class CPS360_model_check extends CPS360_model_node{

	public function __construct($data,$extraparam = ''){
		$data = $this->_format_xmlcompatible($this->_format_trim($data));
		parent::__construct($data,$extraparam);
	}

	public function to_xml(){
		$xmldoc =
'<order>
	<order_id>'.$this->order_id.'</order_id>
	<order_time>'.$this->order_time.'</order_time>
	<order_updtime>'.$this->order_updtime.'</order_updtime>
	<status>'.$this->status.'</status>
	<server_price>'.$this->server_price.'</server_price>
	<coupon>'.$this->coupon.'</coupon>
	<total_price>'.$this->total_price.'</total_price>
	<total_comm>'.$this->total_comm.'</total_comm>
	<commission>'.implode('|',$this->comm_product).'|'.($this->comm_coupon).'</commission>
</order>';
		return $xmldoc;
	}
}