360CPS API(PHP) ECSHOP插件使用说明
==============

步骤1
-------------
将360CPS接口文件夹的文件全部复制到{根目录}/cps下

步骤2
-------------
在以下三个cps文件中
api_redirect.php
api_order.php
api_check.php
加入如下内容：
//ECSHOP初始化设置
define('IN_ECS', true);
require(dirname(__FILE__).'/../includes/init.php');

步骤3
-------------
执行以下SQL创建360CPS订单表

CREATE TABLE 360cps (
  order_id varchar(20) NOT NULL,
  qid varchar(128) NOT NULL default '0',
  qihoo_id varchar(128) NOT NULL default '',
  ext varchar(255) NOT NULL default '',
  PRIMARY KEY  (order_id)
) ENGINE=MyISAM;


步骤4
-------------
执行以下SQL修改订单表，增加更新时间字段

ALTER TABLE `order_info` ADD `update_time` int(10) unsigned NOT NULL default 0 AFTER `shipping_time`;
ALTER TABLE `order_info` ADD INDEX `index_udptime` (`update_time`);
ALTER TABLE `order_info` ADD INDEX `index_addtime` (`add_time`);
CREATE TRIGGER `order_crttime` BEFORE INSERT ON `order_info` FOR EACH ROW SET NEW.update_time = UNIX_TIMESTAMP();
CREATE TRIGGER `order_updtime` BEFORE UPDATE ON `order_info` FOR EACH ROW SET NEW.update_time = UNIX_TIMESTAMP();

步骤5
-------------
修改程序记录CPS订单

1、编辑根目录下/flow.php文件

2、搜索“$order['order_id'] = $new_order_id;”

3、在其下行粘贴如下代码
    require_once ROOT_PATH . 'cps/core/CPS360_api.class.php';
    if(isset($_COOKIE[CPS360_config::COOKIE_NAME])){
        CPS360_api::order_save($order['order_sn']);
    }
    
步骤6
修改CPS360_plugin_ecshop.php 中的 WEB_HOST 为网站域名，例如:
const WEB_HOST = 'http://www.xxx.com';