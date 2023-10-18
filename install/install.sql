DROP TABLE IF EXISTS `weiboapi_config`;
CREATE TABLE `weiboapi_config` (
  `k` varchar(32) NOT NULL,
  `v` text NULL,
  PRIMARY KEY  (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `weiboapi_config` VALUES ('admin_user', 'admin');
INSERT INTO `weiboapi_config` VALUES ('admin_pwd', '123456');
INSERT INTO `weiboapi_config` VALUES ('ip_type', '0');
INSERT INTO `weiboapi_config` VALUES ('white_list', '');
INSERT INTO `weiboapi_config` VALUES ('mail_open', '0');
INSERT INTO `weiboapi_config` VALUES ('mail_cloud', '0');
INSERT INTO `weiboapi_config` VALUES ('mail_smtp', 'smtp.qq.com');
INSERT INTO `weiboapi_config` VALUES ('mail_port', '465');
INSERT INTO `weiboapi_config` VALUES ('mail_name', '');
INSERT INTO `weiboapi_config` VALUES ('mail_pwd', '');
INSERT INTO `weiboapi_config` VALUES ('sitename', '微博API管理中心');
INSERT INTO `weiboapi_config` VALUES ('cache_time', '300');
INSERT INTO `weiboapi_config` VALUES ('cache_clean', '');


DROP TABLE IF EXISTS `weiboapi_account`;
CREATE TABLE `weiboapi_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(15) NOT NULL,
  `nickname` varchar(150) NOT NULL,
  `loginname` varchar(100) DEFAULT NULL,
  `cookie_login` text DEFAULT NULL,
  `cookie_weibo` text DEFAULT NULL,
  `addtime` datetime DEFAULT NULL,
  `usetime` datetime DEFAULT NULL,
  `checktime` datetime DEFAULT NULL,
  `refreshtime` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uid`(`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `weiboapi_log`;
CREATE TABLE `weiboapi_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `time` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `weiboapi_cache`;
CREATE TABLE `weiboapi_cache` (
  `key` varchar(32) NOT NULL,
  `data` mediumtext DEFAULT NULL,
  `time` int(11) NOT NULL,
 PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;