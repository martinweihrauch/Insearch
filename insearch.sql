-- phpMyAdmin SQL Dump
-- version 3.4.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 07. Sep 2012 um 08:25
-- Server Version: 5.1.37
-- PHP-Version: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `insearch`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `in_articles`
--

CREATE TABLE IF NOT EXISTS `in_articles` (
  `aid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dateadded` datetime NOT NULL,
  `articleid` int(10) unsigned NOT NULL,
  `module` varchar(20) NOT NULL,
  `rating` float(6,2) DEFAULT NULL,
  PRIMARY KEY (`aid`),
  KEY `articleid` (`articleid`,`module`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `in_occurrences`
--

CREATE TABLE IF NOT EXISTS `in_occurrences` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL,
  `wordid` int(10) unsigned NOT NULL,
  `count` int(10) unsigned NOT NULL,
  `articlepart` varchar(20) NOT NULL,
  `module` varchar(20) NOT NULL,
  `weight` float(3,2) NOT NULL DEFAULT \'1.00\',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `module` (`module`),
  KEY `wordid` (`wordid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `in_words`
--

CREATE TABLE IF NOT EXISTS `in_words` (
  `wordid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `word_orig` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT \'The original word as input\',
  `word_wo_specials` varchar(40) NOT NULL COMMENT \'Word without special characters, etc\',
  `word_stemmed` varchar(40) NOT NULL COMMENT \'Word-stem\',
  `soundex` varchar(10) NOT NULL,
  `word_extra` varchar(40) NOT NULL COMMENT \'To be determined\',
  `language` varchar(3) NOT NULL COMMENT \'en=english, de=german\',
  `totaloccurrences` int(10) unsigned NOT NULL,
  PRIMARY KEY (`wordid`),
  UNIQUE KEY `word` (`word_orig`),
  KEY `word_wo_specials` (`word_wo_specials`),
  KEY `soundex` (`soundex`,`language`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;