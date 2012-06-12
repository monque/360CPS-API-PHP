360CPS-API-PHP
==============

本开发包是基于360cps开发文档实现的一个简单框架，开发人员可以通过填充该框架的cps360model.class.php、cps360config.class.php等文件来实现主要的cps接口功能。


填充完毕后，可以提供以下几个接口地址：
1.访问跳转接口：http://example.com/360cps/redirect.php
2.订单查询接口: http://example.com/360cps/orderQuery.php
3.对账查询接口: http://example.com/360cps/orderCheck.php


特别说明：用户下订单时可以通过调用$cps360Model->saveCpsOrder()方法来记录cps的订单


文件说明

    1）cps360config.class.php 商家接入360CPS系统的配置信息(需要开发人员配置)
    2）cps360model.class.php 接入360CPS系统的操作类（需要开发人员实现，注意 TODO 的地方）

    3）cps360utils.class.php 通用的工具类
    4）redirect.php 访问跳转接口

    5）orderQuery.php 订单查询接口

    6）orderCheck.php 对账查询接口

    7) common.php    同一加载文件

开发文档
http://open.union.360.cn/apidoc

注：开发人员也可以按照接口文档自行开发