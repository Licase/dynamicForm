/*
SQLyog 企业版 - MySQL GUI v8.14 
MySQL - 5.7.8-rc-log : Database - dynamic_project
*********************************************************************
*/


/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
USE `dynamic_project`;

/*Table structure for table `tab_admin` */

CREATE TABLE IF NOT EXISTS `tab_admin` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `nickname` varchar(64) NOT NULL DEFAULT '',
  `uuid` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL,
  `mobile` varchar(15) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_admin` */

/*Table structure for table `tab_admin_role` */

CREATE TABLE IF NOT EXISTS `tab_admin_role` (
  `admin_id` int(8) unsigned NOT NULL,
  `role_id` int(8) unsigned NOT NULL,
  PRIMARY KEY (`admin_id`,`role_id`),
  KEY `admin_id` (`admin_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_admin_role` */

/*Table structure for table `tab_admin_supporter` */

CREATE TABLE IF NOT EXISTS `tab_admin_supporter` (
  `uuid` varchar(32) NOT NULL,
  `showname` varchar(64) NOT NULL,
  `is_online` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `online_total` int(8) unsigned NOT NULL DEFAULT '0',
  `online_today` int(8) unsigned NOT NULL DEFAULT '0',
  `served_total` int(8) NOT NULL DEFAULT '0',
  `served_today` int(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_admin_supporter` */

/*Table structure for table `tab_admin_supporter_stat` */

CREATE TABLE IF NOT EXISTS `tab_admin_supporter_stat` (
  `uuid` varchar(32) NOT NULL,
  `cur_day` date NOT NULL,
  `online_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '在线时长',
  `served_num` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '服务人数',
  PRIMARY KEY (`uuid`),
  KEY `bydate` (`cur_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_admin_supporter_stat` */

/*Table structure for table `tab_msg` */

CREATE TABLE IF NOT EXISTS `tab_msg` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `from` varchar(64) NOT NULL,
  `to` varchar(64) NOT NULL,
  `role` varchar(10) NOT NULL,
  `msg` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '1',
  `curDay` date NOT NULL DEFAULT '0000-00-00',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `from` (`from`),
  KEY `to` (`to`),
  KEY `date` (`curDay`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_msg` */

/*Table structure for table `tab_permission` */

CREATE TABLE IF NOT EXISTS `tab_permission` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
   `pid` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '上级id',
   `name` varchar(64) NOT NULL COMMENT '权限名',
   `code` varchar(255) NOT NULL COMMENT '权限码',
   `uri` varchar(255) NOT NULL,
   `type` varchar(10) NOT NULL DEFAULT 'menu' COMMENT '类型,menu:菜单 ,button:按钮,op:接口',
   `method` varchar(6) NOT NULL DEFAULT 'GET' COMMENT '请求方式',
   `sorts` smallint(8) unsigned NOT NULL DEFAULT '10' COMMENT '排序',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_permission` */

/*Table structure for table `tab_project` */

CREATE TABLE IF NOT EXISTS `tab_project` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `steps` tinyint(8) unsigned NOT NULL DEFAULT '0',
  `uuid` varchar(64) NOT NULL DEFAULT '',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1:正常.0:隐藏',
  `temps` varchar(255) NOT NULL DEFAULT '' COMMENT '项目中用到的模板',
  `roles` varchar(255) NOT NULL DEFAULT '' COMMENT '参与项目的角色,没有则所有用户可用',
  `admin_roles` varchar(255) NOT NULL DEFAULT '' COMMENT '项目中的管理角色',
  `onlyone` tinyint(1) NOT NULL DEFAULT '0',
  `total` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '现有数据',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_project` */

/*Table structure for table `tab_project_flow` */

CREATE TABLE IF NOT EXISTS `tab_project_flow` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `p_id` int(8) unsigned NOT NULL COMMENT '流程id',
  `step` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `temps` varchar(255) NOT NULL DEFAULT '' COMMENT '使用的模板id',
  `has_check` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否审核',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `pid` (`p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_project_flow` */

/*Table structure for table `tab_project_roles` */

CREATE TABLE IF NOT EXISTS `tab_project_roles` (
  `p_id` int(8) unsigned NOT NULL,
  `role_id` int(8) unsigned NOT NULL,
  UNIQUE KEY `NewIndex1` (`p_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_project_roles` */

/*Table structure for table `tab_role` */

CREATE TABLE IF NOT EXISTS `tab_role` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_role` */

/*Table structure for table `tab_role_perm` */

CREATE TABLE IF NOT EXISTS `tab_role_perm` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(8) unsigned NOT NULL,
  `p_id` int(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rid` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_role_perm` */

/*Table structure for table `tab_setting` */

CREATE TABLE IF NOT EXISTS `tab_setting` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `setting` varchar(32) NOT NULL,
  `values` varchar(255) NOT NULL DEFAULT '',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `user_id` int(8) unsigned NOT NULL DEFAULT '0',
  `category` varchar(20) NOT NULL DEFAULT 'base',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `setting` (`category`,`setting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_setting` */

/*Table structure for table `tab_template` */

CREATE TABLE IF NOT EXISTS `tab_template` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `user_id` int(8) NOT NULL DEFAULT '0',
  `field_count` tinyint(1) NOT NULL DEFAULT '0',
  `remark` varchar(255) DEFAULT NULL,
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_template` */

/*Table structure for table `tab_template_field` */

CREATE TABLE IF NOT EXISTS `tab_template_field` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `temp_id` int(8) unsigned NOT NULL,
  `user_id` int(8) NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL,
  `data_type` varchar(32) NOT NULL,
  `options` text,
  `sort` int(5) unsigned NOT NULL DEFAULT '20',
  `is_title` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_require` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_filter` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_sort` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `remark` varchar(255) DEFAULT NULL,
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `template` (`temp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_template_field` */

/*Table structure for table `tab_user` */

CREATE TABLE IF NOT EXISTS `tab_user` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `nickname` varchar(64) NOT NULL DEFAULT '',
  `uuid` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `gender` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `province` varchar(32) NOT NULL DEFAULT '',
  `city` varchar(32) NOT NULL DEFAULT '',
  `mobile` varchar(15) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `access_time` int(10) NOT NULL DEFAULT '0',
  `server_uuid` varchar(32) NOT NULL DEFAULT '',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_user` */

/*Table structure for table `tab_user_data` */

CREATE TABLE IF NOT EXISTS `tab_user_data` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `p_id` int(8) unsigned NOT NULL,
  `user_id` int(8) unsigned NOT NULL,
  `cur_step` int(1) unsigned NOT NULL DEFAULT '1',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1:正常,0:删除',
  `sorts` varchar(64) NOT NULL DEFAULT '',
  `is_complete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否完成',
  `cur_day` date NOT NULL DEFAULT '2020-01-01',
  `from_code` varchar(32) NOT NULL DEFAULT '',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `pid` (`p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_user_data` */

/*Table structure for table `tab_user_data_check` */

CREATE TABLE IF NOT EXISTS `tab_user_data_check` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `p_id` int(8) unsigned NOT NULL,
  `data_id` int(8) unsigned NOT NULL,
  `step` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `user_id` int(8) unsigned NOT NULL COMMENT '提交人',
  `check_user` int(8) unsigned NOT NULL COMMENT '审核人',
  `check_time` datetime DEFAULT NULL,
  `check_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `remark` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pid_dataid_step` (`p_id`,`data_id`,`step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_user_data_check` */

/*Table structure for table `tab_user_data_check_stat` */

CREATE TABLE IF NOT EXISTS `tab_user_data_check_stat` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `p_id` int(8) unsigned NOT NULL,
  `user_id` int(8) unsigned NOT NULL,
  `total` int(8) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`),
  KEY `user_pid` (`user_id`,`p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_user_data_check_stat` */

/*Table structure for table `tab_user_data_detail` */

CREATE TABLE IF NOT EXISTS `tab_user_data_detail` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `p_id` int(8) unsigned NOT NULL,
  `data_id` int(8) NOT NULL,
  `user_id` int(8) unsigned NOT NULL,
  `step` int(1) unsigned NOT NULL,
  `temp_id` int(8) NOT NULL,
  `field_id` int(8) unsigned NOT NULL,
  `val` text NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid_data_step` (`p_id`,`data_id`,`step`),
  KEY `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_user_data_detail` */

/*Table structure for table `tab_user_third` */

CREATE TABLE IF NOT EXISTS `tab_user_third` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `union_id` varchar(255) NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `avatar` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(15) NOT NULL,
  `user_id` int(8) unsigned NOT NULL,
  `ceate_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `union_Id` (`union_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `tab_user_third` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;




/*  initialize script */
DROP PROCEDURE IF EXISTS `init_data`;
DELIMITER ;;
CREATE DEFINER=`dynamic_project`@`localhost` PROCEDURE `dynamic_project`.`init_data`(OUT init_data INT)
BEGIN

INSERT INTO `tab_admin` (`id`,`account`,`name`,`nickname`,`uuid`,`password`,`mobile`,`email`,`remark`,`status`,`create_time`)  select 1,'admin','系统管理员','系统管理员','c608234a5fc1','$2y$10$cNdJp.AjzeDsJs64MZ82yu8o1RaFjjMy2e6zzlkeFAFpWCAtn5eD6','','','',1,'2020-03-05 10:30:14' from dual where not exists ( select * from `tab_admin` where id=1);


INSERT INTO `tab_setting` (`id`,`setting`,`values`,`remark`,`user_id`,`category`,`create_time`,`update_time`)  select 1,'sys_title','项目管理系统','',1,'base','0000-00-00 00:00:00','2020-04-23 18:46:37' from dual where not exists ( select * from `tab_setting` where id=1);
INSERT INTO `tab_setting` (`id`,`setting`,`values`,`remark`,`user_id`,`category`,`create_time`,`update_time`)  select 2,'tab_host','','',1,'server','0000-00-00 00:00:00','2020-04-23 18:16:52' from dual where not exists ( select * from `tab_setting` where id=2);
INSERT INTO `tab_setting` (`id`,`setting`,`values`,`remark`,`user_id`,`category`,`create_time`,`update_time`)  select 3,'service_port','8090','',1,'server','0000-00-00 00:00:00','2020-04-23 18:16:52' from dual where not exists ( select * from `tab_setting` where id=3);
INSERT INTO `tab_setting` (`id`,`setting`,`values`,`remark`,`user_id`,`category`,`create_time`,`update_time`)  select 4,'user_disconnect_second','300','',0,'server','0000-00-00 00:00:00','2020-04-23 18:16:52' from dual where not exists ( select * from `tab_setting` where id=4);
INSERT INTO `tab_setting` (`id`,`setting`,`values`,`remark`,`user_id`,`category`,`create_time`,`update_time`)  select 5,'wel_word','','',0,'server','0000-00-00 00:00:00','2020-04-23 18:16:52' from dual where not exists ( select * from `tab_setting` where id=5);

TRUNCATE `tab_permission`;
INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 1,0,'项目管理', '/project','menu','get' ,10 from dual where not exists(select * from `tab_permission` where id=1);
INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 2,0,'人员管理', '/user', 'menu','get' ,10 from dual where not exists(select * from `tab_permission` where id=2);
INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 3,0,'系统设置', '/setting', 'menu','get' ,10 from dual where not exists(select * from `tab_permission` where id=3);

INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 4,1,'数据管理', '/api/v1/data', 'button','get' ,10 from dual where not exists(select * from `tab_permission` where id=4);
INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 5,1,'项目管理', '/api/v1/project', 'button','get' ,10 from dual where not exists(select * from `tab_permission` where id=5);
INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 6,1,'模板管理', '/api/v1/template', 'button','get' ,10 from dual where not exists(select * from `tab_permission` where id=6);

INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 7,2,'管理员管理', '/api/v1/admin', 'button','get' ,10 from dual where not exists(select * from `tab_permission` where id=7);
INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 8,2,'客服管理', '/api/v1/supporter', 'button','get' ,10 from dual where not exists(select * from `tab_permission` where id=8);
INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 9,2,'角色管理', '/api/v1/role', 'button','get' ,10 from dual where not exists(select * from `tab_permission` where id=9);

INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 10,3,'基本设置', '/api/v1/settingBase', 'button','get' ,10 from dual where not exists(select * from `tab_permission` where id=10);
INSERT INTO `tab_permission` (`id`,`pid`,`name`,  `code`, `type`, `method`,`sorts`) select 11,3,'客服系统设置', '/api/v1/settingServer', 'button','get' ,10 from dual where not exists(select * from `tab_permission` where id=11);

set init_data =0;
END
;;
DELIMITER ;
CALL init_data(@out);