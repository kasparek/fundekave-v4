-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Host: mysql5-2
-- Generation Time: Sep 28, 2010 at 03:55 PM
-- Server version: 5.0.32
-- PHP Version: 5.2.6-1+lenny9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `fdk4_p_97440`
--

-- --------------------------------------------------------

--
-- Table structure for table `sys_cache`
--

DROP TABLE IF EXISTS `sys_cache`;
CREATE TABLE IF NOT EXISTS `sys_cache` (
  `groupId` varchar(20) NOT NULL default 'default',
  `nameId` varchar(20) NOT NULL,
  `value` blob NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `lifeTime` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (`groupId`,`nameId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_leftpanel_defaults`
--

DROP TABLE IF EXISTS `sys_leftpanel_defaults`;
CREATE TABLE IF NOT EXISTS `sys_leftpanel_defaults` (
  `leftpanelGroup` varchar(10) NOT NULL,
  `functionName` varchar(40) NOT NULL,
  `ord` smallint(5) unsigned NOT NULL default '0',
  `visible` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`leftpanelGroup`,`functionName`),
  KEY `leftpanel_group` (`leftpanelGroup`),
  KEY `leftpanel_func` (`functionName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_leftpanel_functions`
--

DROP TABLE IF EXISTS `sys_leftpanel_functions`;
CREATE TABLE IF NOT EXISTS `sys_leftpanel_functions` (
  `functionName` varchar(40) NOT NULL,
  `name` varchar(40) NOT NULL,
  `public` tinyint(3) unsigned NOT NULL default '0',
  `userId` mediumint(9) NOT NULL,
  `pageId` varchar(5) default NULL,
  `content` text,
  `options` varchar(50) default NULL,
  PRIMARY KEY  (`functionName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_leftpanel_pages`
--

DROP TABLE IF EXISTS `sys_leftpanel_pages`;
CREATE TABLE IF NOT EXISTS `sys_leftpanel_pages` (
  `pageId` varchar(5) NOT NULL,
  `functionName` varchar(40) NOT NULL,
  `ord` smallint(5) unsigned default NULL,
  `visible` tinyint(3) unsigned default NULL,
  `minimized` tinyint(3) unsigned default NULL,
  PRIMARY KEY  (`pageId`,`functionName`),
  KEY `leftpanel_group` (`pageId`),
  KEY `leftpanel_func` (`functionName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_menu`
--

DROP TABLE IF EXISTS `sys_menu`;
CREATE TABLE IF NOT EXISTS `sys_menu` (
  `pageIdTop` varchar(5) NOT NULL,
  `pageId` varchar(5) NOT NULL,
  `text` varchar(50) NOT NULL,
  `public` tinyint(3) unsigned NOT NULL default '0',
  `ord` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`pageIdTop`,`pageId`),
  KEY `menu-pageTop` (`pageIdTop`),
  KEY `menu-page` (`pageId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages`
--

DROP TABLE IF EXISTS `sys_pages`;
CREATE TABLE IF NOT EXISTS `sys_pages` (
  `pageId` varchar(5) NOT NULL,
  `pageIdTop` varchar(5) default NULL,
  `typeId` varchar(10) default NULL,
  `typeIdChild` varchar(10) default NULL,
  `categoryId` smallint(5) unsigned default NULL,
  `template` varchar(50) default NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `content` text,
  `public` tinyint(3) unsigned NOT NULL default '1',
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime default NULL,
  `dateContent` datetime default NULL,
  `userIdOwner` mediumint(8) unsigned NOT NULL,
  `pageIco` varchar(30) default NULL,
  `cnt` mediumint(9) default '0',
  `locked` tinyint(4) default '0',
  `galeryDir` varchar(100) default NULL,
  PRIMARY KEY  (`pageId`),
  KEY `pages-category` (`categoryId`),
  KEY `pages-top` (`pageIdTop`),
  KEY `pages-type` (`typeId`),
  KEY `pages-public` (`public`),
  KEY `pages-locked` (`locked`),
  KEY `pages-owner` (`userIdOwner`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_category`
--

DROP TABLE IF EXISTS `sys_pages_category`;
CREATE TABLE IF NOT EXISTS `sys_pages_category` (
  `categoryId` smallint(5) unsigned NOT NULL auto_increment,
  `typeId` varchar(10) default NULL,
  `pageIdTop` varchar(5) default NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `ord` smallint(5) unsigned NOT NULL default '0',
  `public` tinyint(3) unsigned NOT NULL default '1',
  `num` int(11) NOT NULL,
  PRIMARY KEY  (`categoryId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_favorites`
--

DROP TABLE IF EXISTS `sys_pages_favorites`;
CREATE TABLE IF NOT EXISTS `sys_pages_favorites` (
  `userId` mediumint(8) unsigned NOT NULL,
  `pageId` varchar(5) NOT NULL,
  `cnt` mediumint(8) unsigned NOT NULL,
  `book` tinyint(3) unsigned NOT NULL,
  UNIQUE KEY `userId_2` (`userId`,`pageId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_items`
--

DROP TABLE IF EXISTS `sys_pages_items`;
CREATE TABLE IF NOT EXISTS `sys_pages_items` (
  `itemId` mediumint(8) unsigned NOT NULL auto_increment,
  `itemIdTop` mediumint(8) unsigned default NULL,
  `typeId` varchar(10) default NULL,
  `pageId` varchar(5) NOT NULL,
  `pageIdTop` varchar(5) default NULL,
  `categoryId` smallint(5) unsigned default NULL,
  `userId` mediumint(8) unsigned default NULL,
  `name` varchar(15) NOT NULL,
  `dateStart` datetime default NULL,
  `dateEnd` datetime default NULL,
  `dateCreated` datetime NOT NULL,
  `text` text,
  `textLong` text,
  `enclosure` varchar(255) default NULL,
  `addon` varchar(100) default NULL,
  `filesize` mediumint(8) unsigned default NULL,
  `hit` mediumint(8) unsigned NOT NULL default '0',
  `cnt` smallint(5) unsigned NOT NULL default '0',
  `tag_weight` mediumint(8) unsigned default '0',
  `location` varchar(100) default NULL,
  `public` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`itemId`),
  KEY `item-itemIdTop` (`itemIdTop`),
  KEY `item-pageId` (`pageId`),
  KEY `item-userId` (`userId`),
  KEY `item-dateCreated` (`dateCreated`),
  KEY `hit` (`hit`),
  KEY `cnt` (`cnt`),
  FULLTEXT KEY `search` (`text`,`enclosure`,`addon`,`textLong`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_items_history`
--

DROP TABLE IF EXISTS `sys_pages_items_history`;
CREATE TABLE IF NOT EXISTS `sys_pages_items_history` (
  `dateInt` varchar(10) NOT NULL,
  `itemId` mediumint(8) unsigned NOT NULL,
  `historyType` tinyint(3) unsigned NOT NULL default '0',
  `valueSum` mediumint(8) unsigned NOT NULL,
  UNIQUE KEY `dateInt` (`dateInt`,`itemId`,`historyType`),
  KEY `item` (`itemId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_items_hit`
--

DROP TABLE IF EXISTS `sys_pages_items_hit`;
CREATE TABLE IF NOT EXISTS `sys_pages_items_hit` (
  `itemId` mediumint(8) unsigned NOT NULL,
  `userId` mediumint(8) unsigned default NULL,
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_items_properties`
--

DROP TABLE IF EXISTS `sys_pages_items_properties`;
CREATE TABLE IF NOT EXISTS `sys_pages_items_properties` (
  `itemId` mediumint(8) unsigned zerofill NOT NULL,
  `name` varchar(20) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`itemId`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_items_readed_reactions`
--

DROP TABLE IF EXISTS `sys_pages_items_readed_reactions`;
CREATE TABLE IF NOT EXISTS `sys_pages_items_readed_reactions` (
  `itemId` mediumint(8) unsigned NOT NULL,
  `userId` mediumint(8) unsigned default NULL,
  `cnt` mediumint(8) unsigned NOT NULL default '0',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  UNIQUE KEY `itemsReact` (`itemId`,`userId`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_items_tag`
--

DROP TABLE IF EXISTS `sys_pages_items_tag`;
CREATE TABLE IF NOT EXISTS `sys_pages_items_tag` (
  `itemId` mediumint(8) unsigned NOT NULL,
  `userId` mediumint(8) unsigned NOT NULL,
  `tag` varchar(255) default NULL,
  `weight` tinyint(3) unsigned default NULL,
  `dateCreated` datetime NOT NULL,
  UNIQUE KEY `itemsTag` (`itemId`,`userId`),
  KEY `itemtag-itemId` (`itemId`),
  KEY `itemtag-user` (`userId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_properties`
--

DROP TABLE IF EXISTS `sys_pages_properties`;
CREATE TABLE IF NOT EXISTS `sys_pages_properties` (
  `pageId` varchar(5) NOT NULL,
  `name` varchar(20) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`pageId`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages_relations`
--

DROP TABLE IF EXISTS `sys_pages_relations`;
CREATE TABLE IF NOT EXISTS `sys_pages_relations` (
  `pageId` varchar(5) NOT NULL,
  `pageIdRelative` varchar(5) NOT NULL,
  UNIQUE KEY `pageIdUni` (`pageId`,`pageIdRelative`),
  KEY `pages-rel1` (`pageId`),
  KEY `pages-rel2` (`pageIdRelative`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_poll`
--

DROP TABLE IF EXISTS `sys_poll`;
CREATE TABLE IF NOT EXISTS `sys_poll` (
  `pollId` smallint(5) unsigned NOT NULL auto_increment,
  `pageId` varchar(5) NOT NULL,
  `activ` tinyint(3) unsigned NOT NULL default '0',
  `question` varchar(255) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime default NULL,
  `userId` mediumint(8) unsigned NOT NULL default '0',
  `publicresults` smallint(5) unsigned NOT NULL default '0',
  `votesperuser` smallint(5) unsigned NOT NULL default '1',
  PRIMARY KEY  (`pollId`),
  KEY `poll-pageId` (`pageId`),
  KEY `poll-userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_poll_answers`
--

DROP TABLE IF EXISTS `sys_poll_answers`;
CREATE TABLE IF NOT EXISTS `sys_poll_answers` (
  `pollId` smallint(5) unsigned NOT NULL,
  `pollAnswerId` mediumint(8) unsigned NOT NULL auto_increment,
  `answer` varchar(255) NOT NULL,
  `ord` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`pollAnswerId`),
  KEY `pollanswers-pollId` (`pollId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_poll_answers_users`
--

DROP TABLE IF EXISTS `sys_poll_answers_users`;
CREATE TABLE IF NOT EXISTS `sys_poll_answers_users` (
  `pollId` smallint(5) unsigned NOT NULL,
  `pollAnswerId` mediumint(8) unsigned NOT NULL,
  `userId` mediumint(8) unsigned NOT NULL,
  KEY `pollanswersusers-pollId` (`pollId`),
  KEY `pollanswersusers-pollAnswerId` (`pollAnswerId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_users`
--

DROP TABLE IF EXISTS `sys_users`;
CREATE TABLE IF NOT EXISTS `sys_users` (
  `userId` mediumint(8) unsigned NOT NULL auto_increment,
  `skinId` smallint(5) unsigned default NULL,
  `name` varchar(20) NOT NULL,
  `password` varchar(32) NOT NULL,
  `ipcheck` tinyint(1) default '1',
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime default NULL,
  `dateLastVisit` datetime default NULL,
  `email` varchar(100) default NULL,
  `icq` varchar(20) default NULL,
  `info` text,
  `avatar` varchar(100) default NULL,
  `zbanner` tinyint(3) unsigned default '1',
  `zavatar` tinyint(3) unsigned default '1',
  `zforumico` tinyint(3) unsigned default '1',
  `zgalerytype` tinyint(3) unsigned default '0',
  `deleted` tinyint(3) unsigned default '0',
  `hit` int(10) unsigned default '0',
  PRIMARY KEY  (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_users_diary`
--

DROP TABLE IF EXISTS `sys_users_diary`;
CREATE TABLE IF NOT EXISTS `sys_users_diary` (
  `diaryId` mediumint(8) unsigned NOT NULL auto_increment,
  `userId` mediumint(8) unsigned NOT NULL default '0',
  `dateCreated` datetime NOT NULL,
  `dateEvent` datetime NOT NULL,
  `eventForAll` tinyint(1) NOT NULL default '0',
  `name` varchar(50) NOT NULL,
  `text` text,
  `reminder` tinyint(3) unsigned NOT NULL default '0',
  `everyday` tinyint(3) unsigned NOT NULL default '0',
  `recurrence` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`diaryId`),
  KEY `diary-user` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_users_friends`
--

DROP TABLE IF EXISTS `sys_users_friends`;
CREATE TABLE IF NOT EXISTS `sys_users_friends` (
  `userId` mediumint(8) unsigned NOT NULL,
  `userIdFriend` mediumint(8) unsigned NOT NULL,
  `dateCreated` datetime NOT NULL,
  `comment` varchar(100) default NULL,
  UNIQUE KEY `userId_2` (`userId`,`userIdFriend`),
  KEY `userId` (`userId`),
  KEY `userIdFriend` (`userIdFriend`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_users_fuvatar_live`
--

DROP TABLE IF EXISTS `sys_users_fuvatar_live`;
CREATE TABLE IF NOT EXISTS `sys_users_fuvatar_live` (
  `userId` mediumint(8) unsigned NOT NULL,
  `pageId` varchar(5) NOT NULL,
  `refresh` smallint(5) unsigned NOT NULL,
  `filetime` varchar(20) NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `resolution` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_users_logged`
--

DROP TABLE IF EXISTS `sys_users_logged`;
CREATE TABLE IF NOT EXISTS `sys_users_logged` (
  `userId` mediumint(8) unsigned NOT NULL default '0',
  `loginId` varchar(50) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime default NULL,
  `location` varchar(5) NOT NULL,
  `params` varchar(8) default NULL,
  `ip` varchar(100) default NULL,
  `invalidatePerm` tinyint(1) default '0',
  PRIMARY KEY  (`userId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_users_perm`
--

DROP TABLE IF EXISTS `sys_users_perm`;
CREATE TABLE IF NOT EXISTS `sys_users_perm` (
  `userId` mediumint(8) unsigned NOT NULL,
  `pageId` varchar(5) NOT NULL,
  `rules` tinyint(3) unsigned NOT NULL default '0',
  KEY `perm_user` (`userId`),
  KEY `perm_page` (`pageId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_users_pocket`
--

DROP TABLE IF EXISTS `sys_users_pocket`;
CREATE TABLE IF NOT EXISTS `sys_users_pocket` (
  `pocketId` mediumint(8) unsigned NOT NULL auto_increment,
  `userId` mediumint(8) unsigned NOT NULL,
  `pageId` varchar(5) NOT NULL,
  `itemId` mediumint(8) unsigned default NULL,
  `description` varchar(50) default NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY  (`pocketId`),
  KEY `pocket-user` (`userId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sys_users_post`
--

DROP TABLE IF EXISTS `sys_users_post`;
CREATE TABLE IF NOT EXISTS `sys_users_post` (
  `postId` mediumint(8) unsigned NOT NULL auto_increment,
  `userId` mediumint(8) unsigned NOT NULL,
  `postIdFrom` mediumint(8) unsigned default NULL,
  `userIdTo` mediumint(8) unsigned NOT NULL,
  `userIdFrom` mediumint(8) unsigned NOT NULL,
  `dateCreated` datetime NOT NULL,
  `text` text,
  `readed` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`postId`),
  KEY `post-postIdFrom` (`postIdFrom`),
  KEY `post-userIdTo` (`userIdTo`),
  KEY `post-userIdFrom` (`userIdFrom`),
  KEY `post-userId` (`userId`),
  KEY `post-dateCreated` (`dateCreated`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
