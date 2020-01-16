/*
SQLyog 企业版 - MySQL GUI v8.14 
MySQL - 5.7.8-rc-log : Database - dynamic_table
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`dynamic_table` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `dynamic_table`;

/*Table structure for table `tab_template` */

DROP TABLE IF EXISTS `tab_template`;

CREATE TABLE `tab_template` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `is_show` tinyint(1) NOT NULL DEFAULT '1',
  `remark` varchar(255) DEFAULT NULL,
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8;

/*Table structure for table `tab_template_field` */

DROP TABLE IF EXISTS `tab_template_field`;

CREATE TABLE `tab_template_field` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `tempalte_id` int(8) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `data_type` varchar(32) NOT NULL,
  `options` text,
  `sort` int(5) unsigned NOT NULL DEFAULT '20',
  `is_title` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_require` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_filter` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `validate` varchar(255) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `template` (`tempalte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `tab_user_data` */

DROP TABLE IF EXISTS `tab_user_data`;

CREATE TABLE `tab_user_data` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `template_id` int(8) unsigned NOT NULL,
  `tab_field_id` int(8) unsigned NOT NULL,
  `val` varchar(255) NOT NULL,
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_tem_field` (`user_id`,`template_id`,`tab_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
