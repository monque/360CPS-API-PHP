360CPS API(PHP)
==============

简介
-------------
本开发包是基于360CPS开发文档实现的一个简单框架，开发人员可以通过修改该框架的 api_config.php、plugin/CPS360_plugin_api.php 文件来实现主要的CPS接口功能。

接口地址
-------------
	1）访问跳转接口：http://example.com/360cps/api_redirect.php
	2）订单查询接口: http://example.com/360cps/api_order.php
	3）对账查询接口: http://example.com/360cps/api_check.php

特别说明：用户下订单时可以通过调用$CPS360_plugin_api->order_save()方法来记录CPS的订单


文件说明
-------------
	1）core/CPS360_api.class.php		接口核心类
	2）core/CPS360_models.class.php	订单模型
	3）core/CPS360_plugin.class.php	插件模型
	4）plugin/CPS360_plugin_api.php	插件模板（需修改）
	5）api_config.php					接口配置信息（需修改）
	6）api_redirect.php				访问跳转接口
	7）api_order.php					订单查询接口
	8）api_check.php					对账查询接口

开发文档
-------------
http://open.union.360.cn/apidoc