CREATE DATABASE `wpjob` /*!40100 DEFAULT CHARACTER SET utf8 */;
CREATE TABLE `batch` (
  `id` int(11) NOT NULL,
  `_index` int(11) NOT NULL DEFAULT '0',
  `uuid` varchar(45) NOT NULL,
  `type` enum('mail-split','mail-send','contact-import','contact-export') NOT NULL,
  `slaver_uuid` varchar(45) NOT NULL,
  `task_uuid` varchar(45) NOT NULL,
  `_status` enum('pending','processing','pause','abort','finished') NOT NULL,
  `_plan_time` datetime NOT NULL,
  `_created` datetime NOT NULL,
  `_finished` datetime NOT NULL,
  `_process_rate` int(11) NOT NULL,
  `abort_reson` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('mail-split','mail-send','contact-import','contact-export') NOT NULL,
  `uuid` varchar(45) DEFAULT NULL,
  `task_uuid` varchar(45) DEFAULT NULL,
  `batch_uuid` varchar(45) DEFAULT NULL,
  `slaver_uuid` varchar(45) DEFAULT NULL,
  `status` enum('processing','finished','abort') DEFAULT NULL,
  `count` int(11) NOT NULL DEFAULT '0',
  `_created` datetime DEFAULT NULL,
  `_finished` datetime DEFAULT NULL,
  `_process_rate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8;

CREATE TABLE `master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `port` int(11) NOT NULL,
  `_created` datetime NOT NULL,
  `_updated` datetime NOT NULL,
  `_status` enum('working','exit') DEFAULT NULL,
  `role` enum('master','standbyer') DEFAULT NULL,
  `uuid` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1635 DEFAULT CHARSET=utf8;

CREATE TABLE `slaver` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(45) NOT NULL,
  `master_uuid` varchar(45) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `port` int(11) NOT NULL,
  `executer` varchar(45) NOT NULL,
  `process_count` int(11) NOT NULL,
  `busy_process_count` int(11) NOT NULL DEFAULT '0',
  `_status` enum('working','exit') NOT NULL DEFAULT 'working',
  `_created` datetime NOT NULL,
  `_updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=469 DEFAULT CHARSET=utf8;

CREATE TABLE `task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projectId` varchar(45) NOT NULL DEFAULT 'unkown',
  `uuid` varchar(45) NOT NULL,
  `name` varchar(45) NOT NULL,
  `type` enum('mail-split','mail-send','contact-import','contact-export') NOT NULL,
  `_status` enum('pending','ready','processing','pause','finished','abort') NOT NULL,
  `config` text NOT NULL,
  `_plan_time` datetime NOT NULL,
  `_created` datetime NOT NULL,
  `_finished` datetime NOT NULL,
  `_process_rate` int(11) NOT NULL DEFAULT '0',
  `abort_reson` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


