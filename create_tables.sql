# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 192.168.99.100 (MySQL 5.5.5-10.1.13-MariaDB-1~jessie)
# Database: quickslots
# Generation Time: 2016-04-20 13:59:34 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table allowed
# ------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS quickslots;
USE quickslots;

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `allowed`;

CREATE TABLE `allowed` (
  `course_id` char(10) NOT NULL,
  `batch_name` varchar(30) NOT NULL,
  `batch_dept` char(5) NOT NULL,
  PRIMARY KEY (`course_id`,`batch_name`,`batch_dept`),
  KEY `course_id` (`course_id`),
  KEY `batch_name` (`batch_name`,`batch_dept`),
  CONSTRAINT `batch` FOREIGN KEY (`batch_name`, `batch_dept`) REFERENCES `batches` (`batch_name`, `batch_dept`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table batches
# ------------------------------------------------------------

DROP TABLE IF EXISTS `batches`;

CREATE TABLE `batches` (
  `batch_name` varchar(30) NOT NULL,
  `batch_dept` char(5) NOT NULL,
  `size` int(11) NOT NULL,
  PRIMARY KEY (`batch_name`,`batch_dept`),
  KEY `batches_department` (`batch_dept`),
  CONSTRAINT `batches_department` FOREIGN KEY (`batch_dept`) REFERENCES `depts` (`dept_code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table config
# ------------------------------------------------------------

DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `Name` varchar(30) NOT NULL,
  `value` varchar(30) NOT NULL,
  PRIMARY KEY (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table courses
# ------------------------------------------------------------

DROP TABLE IF EXISTS `courses`;

CREATE TABLE `courses` (
  `course_id` char(10) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `fac_id` char(25) NOT NULL,
  `allow_conflict` tinyint(1) NOT NULL DEFAULT '0',
  `registered_count` int(11) NOT NULL DEFAULT '0',
  `type` enum('laca','minor','lab','core','other') NOT NULL DEFAULT 'core',
  PRIMARY KEY (`course_id`),
  KEY `fac_id` (`fac_id`),
  CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`fac_id`) REFERENCES `faculty` (`uName`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table depts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `depts`;

CREATE TABLE `depts` (
  `dept_code` char(5) NOT NULL,
  `dept_name` varchar(50) NOT NULL,
  PRIMARY KEY (`dept_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table faculty
# ------------------------------------------------------------

DROP TABLE IF EXISTS `faculty`;

CREATE TABLE `faculty` (
  `uName` char(25) NOT NULL,
  `fac_name` varchar(50) NOT NULL,
  `pswd` char(64) NOT NULL,
  `level` enum('dean','hod','faculty','') NOT NULL DEFAULT 'faculty',
  `dept_code` char(5) NOT NULL,
  `dateRegd` char(25) NOT NULL,
  PRIMARY KEY (`uName`),
  KEY `dept_code` (`dept_code`),
  CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`dept_code`) REFERENCES `depts` (`dept_code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table preferences
# ------------------------------------------------------------

DROP TABLE IF EXISTS `preferences`;

CREATE TABLE `preferences` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` char(10) DEFAULT NULL,
  `slot_group` char(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `slot_group` (`slot_group`),
  CONSTRAINT `preferences_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_2` FOREIGN KEY (`slot_group`) REFERENCES `slot_groups` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table rooms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `rooms`;

CREATE TABLE `rooms` (
  `room_name` varchar(25) NOT NULL,
  `capacity` int(11) NOT NULL,
  `lab` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`room_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table slot_allocs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `slot_allocs`;

CREATE TABLE `slot_allocs` (
  `table_name` varchar(30) NOT NULL,
  `day` int(1) unsigned NOT NULL,
  `slot_num` int(2) unsigned NOT NULL,
  `room` varchar(25) NOT NULL,
  `course_id` char(10) NOT NULL,
  PRIMARY KEY (`table_name`,`day`,`slot_num`,`room`),
  KEY `fk_course_id` (`course_id`),
  KEY `fk_room` (`room`),
  KEY `fk_slot` (`day`,`slot_num`),
  CONSTRAINT `fk_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_room` FOREIGN KEY (`room`) REFERENCES `rooms` (`room_name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_slot` FOREIGN KEY (`table_name`, `day`, `slot_num`) REFERENCES `slots` (`table_name`, `day`, `slot_num`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table slot_groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `slot_groups`;

CREATE TABLE `slot_groups` (
  `id` char(3) NOT NULL DEFAULT '',
  `slots` varchar(2000) NOT NULL DEFAULT '[]',
  `lab` tinyint(4) NOT NULL DEFAULT '0',
  `tod` enum('morning','evening') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table slots
# ------------------------------------------------------------

DROP TABLE IF EXISTS `slots`;

CREATE TABLE `slots` (
  `table_name` varchar(30) NOT NULL,
  `day` int(1) unsigned NOT NULL,
  `slot_num` int(2) unsigned NOT NULL,
  `state` enum('active','disabled') NOT NULL,
  PRIMARY KEY (`table_name`,`day`,`slot_num`),
  CONSTRAINT `fk_timetable` FOREIGN KEY (`table_name`) REFERENCES `timetables` (`table_name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table timetables
# ------------------------------------------------------------

DROP TABLE IF EXISTS `timetables`;

CREATE TABLE `timetables` (
  `table_name` varchar(30) NOT NULL,
  `days` int(11) NOT NULL DEFAULT '5',
  `slots` int(11) NOT NULL DEFAULT '0',
  `duration` int(11) NOT NULL DEFAULT '90',
  `start_hr` char(2) NOT NULL DEFAULT '08',
  `start_min` char(2) NOT NULL DEFAULT '30',
  `start_mer` enum('AM','PM') NOT NULL DEFAULT 'AM',
  `allowConflicts` tinyint(1) NOT NULL DEFAULT '0',
  `frozen` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS=1;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
