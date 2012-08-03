360CPS API(PHP) 最土插件使用说明
==============

步骤1
-------------
将360CPS接口文件夹复制到/zuitu目录下，并重命名为360cps

步骤2
-------------
执行以下SQL创建360CPS订单表

`
CREATE TABLE 360cps (
  id bigint(20) NOT NULL auto_increment,
  order_id varchar(128) NOT NULL,
  qid varchar(128) NOT NULL default '0',
  qihoo_id varchar(128) NOT NULL default '',
  ext varchar(255) NOT NULL default '',
  products text,
  dateline int(10) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY order_id (order_id)
) ENGINE=MyISAM AUTO_INCREMENT=0;
`

步骤3
-------------
执行以下SQL修改订单表，增加更新时间字段

`
ALTER TABLE `order` ADD `update_time` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL AFTER `pay_time`;
UPDATE `order` SET update_time = FROM_UNIXTIME((create_time * (create_time > pay_time)) + (pay_time * (pay_time > create_time)));
`

步骤4
-------------
修改程序记录CPS订单

1、编辑/zuitu/team/buy.php文件

2、搜索“DB::Insert('referer', $data);”

3、在其下行粘贴如下代码

`
require_once DIR_ROOT . '/../360cps/core/CPS360_api.class.php';
if(isset($_COOKIE[CPS360_config::COOKIE_NAME])){
	$group = Table::Fetch('category', $team['group_id']);
	$teams = array(
		array(
			'id'		=> $team['id'],							//商品Id
			'name'		=> $team['product'],					//商品名称
			'url'		=> 'http://'.$_SERVER['HTTP_HOST'].WEB_ROOT.'/team.php?id='.$team['id'],	//商品URL
			'cateid'	=> $team['group_id'],					//商品分类Id
			'catename'	=> $group['name'],						//商品分类名称
			'price'		=> $team['team_price'],					//商品单价
			'quantity'	=> $table->quantity,					//商品数量
		)
	);
	CPS360_api::order_save($order_id,$teams);
}
`