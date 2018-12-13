-- phpMyAdmin SQL Dump
-- version 3.4.10.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Server version: 5.7.15
-- PHP Version: 5.5.36

--
-- Database: `CCApp`
--

-- --------------------------------------------------------

--
-- Table structure for table `CCApp`
--

CREATE TABLE IF NOT EXISTS `CCApp` (
  `ApptID` char(30) NOT NULL,
  `Counselor` varchar(20) NOT NULL,
  `FirstName` varchar(20) NOT NULL,
  `LastName` varchar(20) NOT NULL,
  `UnixTimestamp` int(15) NOT NULL,
  `Email` varchar(40) NOT NULL,
  PRIMARY KEY (`ApptID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `CCAppBusy`
--

CREATE TABLE IF NOT EXISTS `CCAppBusy` (
  `Counselor` varchar(20) NOT NULL,
  `UnixTimestamp` int(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `CCAppLog`
--

CREATE TABLE IF NOT EXISTS `CCAppLog` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `remote_addr` varchar(255) NOT NULL DEFAULT '',
  `request_uri` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `CCAppSettings`
--

CREATE TABLE IF NOT EXISTS `CCAppSettings` (
  `Name` varchar(20) NOT NULL,
  `Data` text NOT NULL,
  PRIMARY KEY (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `CCAppSettings`
--

INSERT INTO `CCAppSettings` (`Name`, `Data`) VALUES
('AdminPassword', 's:60:\\"$2y$10$passwordHash\\";'),
('ApptIDLength', 'i:30;'),
('Counselors', 'a:3:{i:0;s:9:"Smith";i:1;s:5:"Brown";i:2;s:7:"Thompson";}'),
('DaysAtATime', 'i:5;'),
('FarAhead', 'i:7;'),
('MailerSettings', 'a:5:{s:10:"DebugLevel";i:0;s:4:"Host";s:18:"smtp.example.com";s:8:"Username";s:22:"example@example.com";s:8:"Password";s:8:"password";s:10:"ReturnName";s:12:"CCApp Mailer";}'),
('SendConfEmail', 'b:0;'),
('Timeslots', 'a:9:{i:0;a:2:{s:5:\\"start\\";s:6:\\"8:00AM\\";s:3:\\"end\\";s:6:\\"8:45AM\\";}i:1;a:2:{s:5:\\"start\\";s:6:\\"8:49AM\\";s:3:\\"end\\";s:6:\\"9:34AM\\";}i:2;a:2:{s:5:\\"start\\";s:6:\\"9:38AM\\";s:3:\\"end\\";s:7:\\"10:23AM\\";}i:3;a:2:{s:5:\\"start\\";s:7:\\"10:36AM\\";s:3:\\"end\\";s:7:\\"11:21AM\\";}i:4;a:2:{s:5:\\"start\\";s:7:\\"11:25AM\\";s:3:\\"end\\";s:7:\\"12:10PM\\";}i:5;a:2:{s:5:\\"start\\";s:7:\\"12:10PM\\";s:3:\\"end\\";s:7:\\"12:53PM\\";}i:6;a:2:{s:5:\\"start\\";s:7:\\"12:56PM\\";s:3:\\"end\\";s:6:\\"1:41PM\\";}i:7;a:2:{s:5:\\"start\\";s:6:\\"1:45PM\\";s:3:\\"end\\";s:6:\\"2:30PM\\";}i:8;a:2:{s:5:\\"start\\";s:6:\\"2:34PM\\";s:3:\\"end\\";s:6:\\"3:19PM\\";}}'),
('VisibleErrors', 'b:1;');