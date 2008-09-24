DROP DATABASE `fdk4test_import`;
CREATE DATABASE `fdk4test_import` /*!40100 CHARACTER SET utf8 COLLATE utf8_general_ci */;
USE `fdk4test_import`;

CREATE TABLE sys_events_place (
       placeId smallint unsigned NOT NULL AUTO_INCREMENT
     , name VARCHAR(50) NOT NULL
     , ord SMALLINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (placeId)
) ENGINE=InnoDB ;

CREATE TABLE sys_weather (
       id smallint unsigned NOT NULL AUTO_INCREMENT
     , locationid VARCHAR(10) NOT NULL
     , locationname VARCHAR(50) NOT NULL
     , dateins DATETIME NOT NULL
     , temp FLOAT(12) NOT NULL
     , press FLOAT(12) NOT NULL
     , humi SMALLINT(6) NOT NULL
     , wind FLOAT(12) NOT NULL
     , winddegree SMALLINT(6) NOT NULL
     , widgusts FLOAT(12) NOT NULL
     , dewpoint smallint(6) unsigned NOT NULL
     , sunrise TIME NOT NULL
     , sunset TIME NOT NULL
     , station VARCHAR(100) NOT NULL
     , wupdate VARCHAR(50) NOT NULL
     , PRIMARY KEY (id)
) ENGINE=InnoDB ;

CREATE TABLE sys_leftpanel_functions (
       functionId smallint unsigned NOT NULL AUTO_INCREMENT
     , function VARCHAR(40) NOT NULL
     , name VARCHAR(40)
     , public TINYINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (functionId)
) ENGINE=InnoDB ;

CREATE TABLE sys_sessions (
       sid VARCHAR(32) NOT NULL
     , hostname VARCHAR(80) NOT NULL
     , timestamp INT(10) NOT NULL DEFAULT 0
     , session LONGTEXT
     , PRIMARY KEY (sid)
) ENGINE=InnoDB ;
CREATE INDEX timestamp ON sys_sessions (timestamp ASC);

CREATE TABLE sys_pages_category (
       categoryId SMALLINT unsigned NOT NULL AUTO_INCREMENT
     , typeId VARCHAR(10) DEFAULT NULL
     , name VARCHAR(50) NOT NULL
     , description TEXT DEFAULT NULL
     , ord SMALLINT unsigned NOT NULL DEFAULT 0
     , public TINYINT unsigned NOT NULL DEFAULT 1
     , PRIMARY KEY (categoryId)
) ENGINE=InnoDB ;

CREATE TABLE sys_cron_log (
       id mediumint unsigned NOT NULL AUTO_INCREMENT
     , date DATETIME NOT NULL
     , text TEXT
     , PRIMARY KEY (id)
) ENGINE=InnoDB ;

CREATE TABLE sys_users (
       userId MEDIUMINT unsigned NOT NULL AUTO_INCREMENT
     , skinId SMALLINT unsigned DEFAULT null
     , name VARCHAR(20) NOT NULL
     , password VARCHAR(32) NOT NULL
     , ipcheck BOOLEAN DEFAULT 1
     , dateCreated DATETIME NOT NULL
     , dateUpdated DATETIME default null
     , dateLastVisit DATETIME default null
     , email VARCHAR(100) DEFAULT null
     , icq VARCHAR(20) DEFAULT null
     , info TEXT
     , avatar VARCHAR(100) DEFAULT null
     , weather_loc_id VARCHAR(10) DEFAULT 'EZXX0012'
     , zbanner TINYINT unsigned DEFAULT 1
     , zavatar TINYINT unsigned DEFAULT 1
     , zforumico TINYINT unsigned DEFAULT 1
     , zgalerytype TINYINT unsigned DEFAULT 0
     , deleted TINYINT unsigned DEFAULT 0
     , hit INT unsigned DEFAULT 0
     , PRIMARY KEY (userId)
) ENGINE=InnoDB ;

CREATE TABLE sys_users_draft (
  userId MEDIUMINT unsigned NOT NULL
  ,place VARCHAR(10) NOT NULL
  ,text  TEXT
  ,UNIQUE KEY `userDraft` (`userId`,`place`)
) ENGINE=InnoDB ;
CREATE INDEX `draft-user` ON sys_users_draft (userId);
CREATE INDEX `draft-place` ON sys_users_draft (place);

CREATE TABLE sys_events_category (
       categoryId smallint unsigned NOT NULL AUTO_INCREMENT
     , name VARCHAR(50) NOT NULL
     , description TEXT
     , ord SMALLINT unsigned NOT NULL DEFAULT 0
     , public TINYINT unsigned NOT NULL DEFAULT 1
     , PRIMARY KEY (categoryId)
) ENGINE=InnoDB ;

 
CREATE TABLE sys_users_post (
       postId mediumint unsigned NOT NULL AUTO_INCREMENT
     , userId MEDIUMINT unsigned NOT NULL 
     , postIdFrom mediumint unsigned default null
     , userIdTo MEDIUMINT unsigned NOT NULL
     , userIdFrom MEDIUMINT unsigned NOT NULL
     , dateCreated DATETIME NOT NULL
     , text TEXT
     , readed tinyint(1) NOT NULL default '0'
     , PRIMARY KEY (postId)
) ENGINE=InnoDB ;
CREATE INDEX `post-postIdFrom` ON sys_users_post (postIdFrom);
CREATE INDEX `post-userIdTo` ON sys_users_post (userIdTo);
CREATE INDEX `post-userIdFrom` ON sys_users_post (userIdFrom);
CREATE INDEX `post-userId` ON sys_users_post (userId);
CREATE INDEX `post-dateCreated` ON sys_users_post (dateCreated Desc);

CREATE TABLE sys_pages (
     pageId varchar(5) NOT NULL
     ,pageIdTop varchar(5) DEFAULT NULL
     , typeId VARCHAR(10) DEFAULT null
     , typeIdChild VARCHAR(10) DEFAULT null
     , categoryId SMALLINT unsigned DEFAULT null
     , menuSecondaryGroup VARCHAR(10) DEFAULT null
     , leftpanelGroup VARCHAR(10) DEFAULT null
     , template VARCHAR(50) DEFAULT null
     , name VARCHAR(100) NOT NULL
     , nameshort VARCHAR(20) NOT NULL
     , description TEXT
     , content TEXT
     , public tinyint unsigned NOT NULL DEFAULT 1
     , dateCreated DATETIME not null
     , dateUpdated DATETIME default null
     , dateContent DATETIME DEFAULT null
     , userIdOwner MEDIUMINT unsigned NOT NULL
     , pageIco VARCHAR(30)
     , cnt MEDIUMINT DEFAULT 0
     , locked TINYINT DEFAULT 0
     , authorContent VARCHAR(100)
     , galeryDir VARCHAR(100) DEFAULT null
     , pageParams text
     , PRIMARY KEY (pageId)
) ENGINE=InnoDB ;
CREATE INDEX `pages-category` on sys_pages (categoryId);
CREATE INDEX `pages-top` on sys_pages (pageIdTop);
CREATE INDEX `pages-type` on sys_pages (typeId);
CREATE INDEX `pages-public` on sys_pages (public);
CREATE INDEX `pages-locked` on sys_pages (locked);
CREATE INDEX `pages-owner` on sys_pages (userIdOwner);

CREATE TABLE sys_pages_relations (
  pageId varchar(5) NOT NULL
  ,pageIdRelative varchar(5) NOT NULL
  ,UNIQUE KEY `pageIdUni` (`pageId`,`pageIdRelative`)
) ENGINE=InnoDB ;
CREATE INDEX `pages-rel1` on sys_pages_relations (pageId);
CREATE INDEX `pages-rel2` on sys_pages_relations (pageIdRelative);


CREATE TABLE sys_poll (
       pollId smallint unsigned NOT NULL AUTO_INCREMENT
     , pageId varchar(5) NOT NULL
     , activ tinyint unsigned NOT NULL DEFAULT 0
     , question VARCHAR(255) NOT NULL
     , dateCreated DATETIME NOT NULL
	   , dateUpdated DATETIME default NULL
     , userId MEDIUMINT unsigned NOT NULL DEFAULT 0
     , publicresults SMALLINT unsigned NOT NULL DEFAULT 0
     , votesperuser SMALLINT unsigned NOT NULL DEFAULT 1
     , PRIMARY KEY (pollId)
) ENGINE=InnoDB ;
CREATE INDEX `poll-pageId` on sys_poll (pageId);
CREATE INDEX `poll-userId` on sys_poll (userId);

CREATE TABLE sys_poll_answers (
       pollId smallint unsigned NOT NULL
     , pollAnswerId mediumint unsigned NOT NULL AUTO_INCREMENT
     , answer VARCHAR(255) NOT NULL
     , ord SMALLINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (pollAnswerId)
) ENGINE=InnoDB ;
CREATE INDEX `pollanswers-pollId` ON sys_poll_answers (pollId ASC);

CREATE TABLE sys_poll_answers_users (
       pollId smallint unsigned NOT NULL
     , pollAnswerId mediumint unsigned NOT NULL
     , userId MEDIUMINT unsigned NOT NULL
) ENGINE=InnoDB ;
CREATE INDEX `pollanswersusers-pollId` ON sys_poll_answers_users (pollId ASC);
CREATE INDEX `pollanswersusers-pollAnswerId` ON sys_poll_answers_users (pollAnswerId ASC);

CREATE TABLE sys_pages_items (
       itemId mediumint unsigned NOT NULL AUTO_INCREMENT
     , itemIdTop mediumint unsigned default NULL
     , pageId varchar(5) NOT NULL
     , userId MEDIUMINT unsigned default null
     , name VARCHAR(15) not null
     , dateCreated DATETIME NOT NULL
     , text TEXT
     , enclosure VARCHAR(255) default null
     , addon VARCHAR(100) default null
     , filesize mediumint unsigned default null
     , hit mediumint unsigned default null
     , cnt smallint unsigned not null default 0
     , PRIMARY KEY (itemId)
) ENGINE=InnoDB ;
CREATE INDEX `item-itemIdTop` ON sys_pages_items (itemIdTop ASC);
CREATE INDEX `item-pageId` ON sys_pages_items (pageId ASC);
CREATE INDEX `item-userId` ON sys_pages_items (userId ASC);
CREATE INDEX `item-dateCreated` ON sys_pages_items (dateCreated DESC);

CREATE TABLE sys_pages_items_readed_reactions (
  itemId mediumint unsigned NOT NULL,
  userId MEDIUMINT unsigned default NULL,
  cnt MEDIUMINT unsigned not null default 0,
  dateCreated datetime NOT NULL default '0000-00-00 00:00:00'
  ,UNIQUE KEY `itemsReact` (`itemId`,`userId`)
) ENGINE=InnoDB ;

CREATE TABLE sys_pages_items_hit (
  itemId mediumint unsigned NOT NULL,
  userId MEDIUMINT unsigned default NULL,
  dateCreated datetime NOT NULL default '0000-00-00 00:00:00'
) ENGINE=InnoDB ;
CREATE INDEX `itemhit-user` ON sys_pages_items_hit (userId);
CREATE INDEX `itemhit-foto` ON sys_pages_items_hit (itemId);

CREATE TABLE sys_pages_items_tag (
       itemId mediumint unsigned NOT NULL
     , userId MEDIUMINT unsigned NOT NULL
     , tag varchar(255) DEFAULT null
     , weight tinyint unsigned default null
     , dateCreated DATETIME not null
     ,UNIQUE KEY `itemsTag` (`itemId`,`userId`)
) ENGINE=InnoDB ;
CREATE INDEX `itemtag-itemId` ON sys_pages_items_tag (itemId);
CREATE INDEX `itemtag-user` ON sys_pages_items_tag (userId);

CREATE TABLE sys_banner (
       bannerId smallint(6) unsigned NOT NULL AUTO_INCREMENT
     , userId MEDIUMINT unsigned NOT NULL
     , imageUrl TEXT NOT NULL
     , linkUrl TEXT NOT NULL
     , dateFrom DATE NOT NULL
     , dateTo DATE not null
     , hit INT unsigned NOT NULL DEFAULT 0
     , display INT unsigned NOT NULL DEFAULT 0
     , strict tinyint(1) unsigned not null DEFAULT 0
     , dateCreated datetime not null
     , dateUpdated datetime default null
     , PRIMARY KEY (bannerId)
) ENGINE=InnoDB ;
CREATE INDEX `banner-user` ON sys_banner (userId);

CREATE TABLE sys_banner_hit (
      bannerId smallint(6) unsigned NOT NULL
     , userId MEDIUMINT unsigned NOT NULL
     , dateCreated datetime not null
);
CREATE INDEX `bannerhit-user` ON sys_banner_hit (userId);
CREATE INDEX `bannerhit-banner` ON sys_banner_hit (bannerId);

CREATE TABLE sys_pages_counter (
     pageId varchar(5) NOT NULL
     , userId MEDIUMINT unsigned DEFAULT null
     , dateLast datetime NOT NULL
     , hit INT unsigned NOT NULL DEFAULT 0
     , ins INT unsigned NOT NULL DEFAULT 0
     , UNIQUE KEY `pageuser` (`pageId`,`userId`)
) ENGINE=InnoDB ;
CREATE INDEX `counter-page` ON sys_pages_counter (pageId);
CREATE INDEX `counter-user` ON sys_pages_counter (userId);

CREATE TABLE sys_users_diary (
       diaryId mediumint unsigned NOT NULL AUTO_INCREMENT
     , userId MEDIUMINT unsigned NOT NULL DEFAULT 0
     , dateCreated DATETIME NOT NULL
     , dateEvent DATETIME not null
     , eventForAll tinyint(1) NOT NULL DEFAULT 0
     , name VARCHAR(50) NOT NULL
     , text TEXT
     , reminder TINYINT unsigned NOT NULL DEFAULT 0
     , everyday TINYINT unsigned NOT NULL DEFAULT 0
     , recurrence TINYINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (diaryId)
) ENGINE=InnoDB ;
CREATE INDEX `diary-user` ON sys_users_diary (userId);


CREATE TABLE sys_surfinie (
       surfId smallint unsigned NOT NULL AUTO_INCREMENT
     , categoryId smallint unsigned
     , userId MEDIUMINT unsigned default null
     , url varchar(255) not null
     , name varchar(255) not null
     , dateCreated DATETIME NOT NULL 
     , public TINYINT unsigned NOT NULL DEFAULT 1
     , PRIMARY KEY (surfId)
) ENGINE=InnoDB ;
CREATE INDEX `sys_surfinie-user` ON sys_surfinie (userId);
CREATE INDEX `sys_surfinie-category` ON sys_surfinie (categoryId);

CREATE TABLE sys_menu (
       menuId SMALLINT unsigned NOT NULL AUTO_INCREMENT
     , pageId varchar(5) NOT NULL
     , text VARCHAR(50) NOT NULL
     , public TINYINT unsigned NOT NULL DEFAULT 0
     , ord SMALLINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (menuId)
     , INDEX (pageId)
) ENGINE=InnoDB ;
CREATE INDEX `menu-page` ON sys_menu (pageId);

CREATE TABLE sys_pages_favorites (
  userId MEDIUMINT unsigned NOT NULL,
  pageId varchar(5) NOT NULL,
  cnt mediumint unsigned unsigned NOT NULL,
  book tinyint unsigned unsigned NOT NULL,
  UNIQUE KEY userId_2 (userId,pageId)
) ENGINE=InnoDB ;
CREATE INDEX `fav-user` ON sys_pages_favorites (userId);
CREATE INDEX `fav-page` ON sys_pages_favorites (pageId);

CREATE TABLE sys_users_friends (
       userId MEDIUMINT unsigned NOT NULL
     , userIdFriend MEDIUMINT unsigned NOT NULL
     , dateCreated DATETIME NOT NULL
     , comment VARCHAR(100) default null
     , UNIQUE KEY `userId_2` (`userId`,`userIdFriend`)
) ENGINE=InnoDB ;
CREATE INDEX userId ON sys_users_friends (userId ASC);
CREATE INDEX userIdFriend ON sys_users_friends (userIdFriend ASC);

CREATE TABLE sys_leftpanel (
       leftpanelId smallint unsigned NOT NULL AUTO_INCREMENT
     , leftpanelGroup VARCHAR(10) NOT NULL
     , functionId SMALLINT unsigned NOT NULL
     , ord SMALLINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (leftpanelId)
) ENGINE=InnoDB ;
CREATE INDEX leftpanel_group ON sys_leftpanel (leftpanelGroup ASC);
CREATE INDEX leftpanel_func ON sys_leftpanel (functionId ASC);

CREATE TABLE sys_menu_secondary (
       menuSecondaryId smallint unsigned NOT NULL AUTO_INCREMENT
     , menuSecondaryGroup VARCHAR(10) default null
     , pageId varchar(5) NOT NULL
     , name VARCHAR(50) NOT NULL
     , public TINYINT unsigned NOT NULL DEFAULT 0
     , ord SMALLINT unsigned DEFAULT 0
     , PRIMARY KEY (menuSecondaryId)
) ENGINE=InnoDB ;
CREATE INDEX menusec_page ON sys_menu_secondary (pageId ASC);

CREATE TABLE sys_skin (
       skinId smallint unsigned NOT NULL AUTO_INCREMENT
     , userId MEDIUMINT unsigned NOT NULL
     , cssfilename VARCHAR(20) NOT NULL
     , dir VARCHAR(20) NOT NULL
     , name VARCHAR(20) NOT NULL
     , dateCreated DATETIME NOT NULL
     , PRIMARY KEY (skinId)
) ENGINE=InnoDB ;
CREATE INDEX skin_user ON sys_skin (userId ASC);

CREATE TABLE sys_users_buttons (
       buttonId smallint unsigned NOT NULL AUTO_INCREMENT
     , userId MEDIUMINT unsigned NOT NULL
     , name VARCHAR(20) NOT NULL
     , url VARCHAR(100) NOT NULL
     , ord TINYINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (buttonId)
) ENGINE=InnoDB ;
CREATE INDEX butt_user ON sys_users_buttons (userId ASC);

CREATE TABLE sys_users_logged (
       userId MEDIUMINT unsigned NOT NULL DEFAULT 0
     , loginId VARCHAR(50) NOT NULL
     , dateCreated DATETIME NOT NULL
     , dateUpdated DATETIME default null
     , location VARCHAR(5) NOT NULL
     , params VARCHAR(8)
     , ip VARCHAR(100)
     , invalidatePerm boolean default 0
     , INDEX (userId)
) ENGINE=InnoDB ;
CREATE INDEX logged_user ON sys_users_logged (userId ASC);

CREATE TABLE sys_users_perm (
       userId MEDIUMINT unsigned NOT NULL
     , pageId varchar(5) NOT NULL
     , rules TINYINT unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB ;
CREATE INDEX perm_user ON sys_users_perm (userId ASC);
CREATE INDEX perm_page ON sys_users_perm (pageId ASC);

CREATE TABLE sys_events (
       eventId mediumint unsigned NOT NULL AUTO_INCREMENT
     , userId MEDIUMINT unsigned NOT NULL
     , categoryId SMALLINT unsigned default NULL
     , placeId SMALLINT unsigned default null
     , dateCreated DATETIME NOT NULL
     , dateEvent DATE NOT NULL
     , timeEvent VARCHAR(20) default null
     , name VARCHAR(100) NOT NULL
     , place VARCHAR(100) NOT NULL
     , description TEXT NOT NULL
     , flyer VARCHAR(100) NOT NULL
     , price VARCHAR(100) NOT NULL
     , PRIMARY KEY (eventId)
) ENGINE=InnoDB ;
CREATE INDEX `events-user` ON sys_events (userId ASC);
CREATE INDEX `events-cat` ON sys_events (categoryId ASC);
CREATE INDEX `events-place` ON sys_events (placeId ASC);

alter table sys_users_post add CONSTRAINT FK_post_1 FOREIGN KEY (postIdFrom) REFERENCES sys_users_post (postId);
alter table sys_users_post add CONSTRAINT FK_post_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_post add CONSTRAINT FK_post_3 FOREIGN KEY (userIdFrom) REFERENCES sys_users (userId);
alter table sys_users_post add CONSTRAINT FK_post_4 FOREIGN KEY (userIdTo) REFERENCES sys_users (userId);
alter table sys_users_draft add CONSTRAINT FK_user_draft FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_pages add CONSTRAINT FK_sys_pages_1 FOREIGN KEY (categoryId) REFERENCES sys_pages_category (categoryId);
alter table sys_pages add CONSTRAINT FK_sys_pages_2 FOREIGN KEY (pageIdTop) REFERENCES sys_pages (pageId);
alter table sys_poll add CONSTRAINT FK_sys_poll_2 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_poll add CONSTRAINT FK_sys_poll_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_poll_answers add CONSTRAINT FK_ankodp_1 FOREIGN KEY (pollId) REFERENCES sys_poll (pollId);
alter table sys_poll_answers_users add CONSTRAINT FK_ankklik_3 FOREIGN KEY (pollId) REFERENCES sys_poll (pollId);
alter table sys_poll_answers_users add CONSTRAINT FK_ankklik_2 FOREIGN KEY (pollAnswerId) REFERENCES sys_poll_answers (pollAnswerId);
alter table sys_poll_answers_users add CONSTRAINT FK_ankklik_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);

alter table sys_pages_items add CONSTRAINT FK_sys_pages_items_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_items add CONSTRAINT FK_sys_pages_items_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_pages_items add CONSTRAINT FK_sys_pages_items_3 FOREIGN KEY (itemIdTop) REFERENCES sys_pages_items (itemId);

alter table sys_banner add CONSTRAINT FK_banner_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_banner_hit add CONSTRAINT FK_banner_hit_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_banner_hit add CONSTRAINT FK_banner_hit_2 FOREIGN KEY (bannerId) REFERENCES sys_banner (bannerId);
alter table sys_pages_counter add CONSTRAINT FK_sys_pages_counter_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_counter add CONSTRAINT FK_sys_pages_counter_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_diary add CONSTRAINT FK_sys_users_diary_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);

alter table sys_pages_items_hit add CONSTRAINT FK_sys_pages_items_hit_1 FOREIGN KEY (itemId) REFERENCES sys_pages_items (itemId);
alter table sys_pages_items_hit add CONSTRAINT FK_sys_pages_items_hit_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_pages_items_tag add CONSTRAINT FK_sys_pages_items_tag_1 FOREIGN KEY (itemId) REFERENCES sys_pages_items (itemId);
alter table sys_pages_items_tag add CONSTRAINT FK_sys_pages_items_tag_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);

alter table sys_surfinie add CONSTRAINT FK_sys_surfinie_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_surfinie add  CONSTRAINT FK_sys_surfinie_2 FOREIGN KEY (categoryId) REFERENCES sys_pages_category (categoryId);
alter table sys_menu add CONSTRAINT FK_menu_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_favorites add CONSTRAINT FK_sys_pages_favorites_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_favorites add CONSTRAINT FK_sys_pages_favorites_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_friends add CONSTRAINT FK_sys_users_friends_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_friends add CONSTRAINT FK_sys_users_friends_2 FOREIGN KEY (userIdFriend) REFERENCES sys_users (userId);
alter table sys_leftpanel add CONSTRAINT FK_sys_leftpanel_1 FOREIGN KEY (functionId) REFERENCES sys_leftpanel_functions (functionId);
alter table sys_menu_secondary add CONSTRAINT FK_sys_menu_secondary_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_skin add CONSTRAINT FK_sys_skin_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_buttons add CONSTRAINT FK_sys_users_buttons_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_logged add CONSTRAINT FK_sys_users_logged_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_perm add CONSTRAINT FK_sys_users_perm_2 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_users_perm add CONSTRAINT FK_sys_users_perm_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_events add CONSTRAINT FK_sys_events_1 FOREIGN KEY (placeId) REFERENCES sys_events_place (placeId);
alter table sys_events add CONSTRAINT FK_sys_events_2 FOREIGN KEY (categoryId) REFERENCES sys_events_category (categoryId);
alter table sys_events add CONSTRAINT FK_sys_events_3 FOREIGN KEY (userId) REFERENCES sys_users (userId);

alter table sys_pages_relations add CONSTRAINT FK_sys_pages_relations_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_relations add CONSTRAINT FK_sys_pages_relations_2 FOREIGN KEY (pageIdRelative) REFERENCES sys_pages (pageId);