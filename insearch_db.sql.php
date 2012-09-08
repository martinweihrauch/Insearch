<?php

$sql_db = array();
$sql_db[] = 'CREATE TABLE IF NOT EXISTS `in_articles` (
  `aid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dateadded` datetime NOT NULL,
  `articleid` int(10) unsigned NOT NULL,
  `module` varchar(20) NOT NULL,
  `rating` float(6,2) DEFAULT NULL,
  PRIMARY KEY (`aid`),
  KEY `articleid` (`articleid`,`module`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
$sql_db[] = 'CREATE TABLE IF NOT EXISTS `in_occurrences` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
$sql_db[] = 'CREATE TABLE IF NOT EXISTS `in_words` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

?>