CREATE TABLE sys_leftpanel_functions (
    functionName VARCHAR(40) NOT NULL
     , name VARCHAR(40) NOT NULL
     , public TINYINT unsigned NOT NULL DEFAULT 0
     , userId MEDIUMINT unsigned NOT NULL
     , content TEXT 
     , PRIMARY KEY (functionName)
);

CREATE TABLE sys_leftpanel_defaults (
     leftpanelGroup VARCHAR(10) NOT NULL
     , functionName VARCHAR(40) NOT NULL
     , ord SMALLINT unsigned NOT NULL DEFAULT 0
     , visible TINYINT unsigned NOT NULL DEFAULT 1
     , minimized TINYINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (leftpanelGroup, functionName)
)  ;
CREATE INDEX leftpanel_group ON sys_leftpanel_defaults (leftpanelGroup ASC);
CREATE INDEX leftpanel_func ON sys_leftpanel_defaults (functionName ASC);

CREATE TABLE sys_leftpanel_pages (
     pageId varchar(5) NOT NULL
     , functionName VARCHAR(40) NOT NULL
     , ord SMALLINT unsigned DEFAULT NULL
     , visible TINYINT unsigned DEFAULT NULL
     , minimized TINYINT unsigned DEFAULT NULL
     , PRIMARY KEY (pageId, functionName)
)  ;
CREATE INDEX leftpanel_group ON sys_leftpanel_pages (pageId ASC);
CREATE INDEX leftpanel_func ON sys_leftpanel_pages (functionName ASC);

CREATE TABLE sys_leftpanel_users (
  userId MEDIUMINT unsigned NOT NULL
  , pageId varchar(5) NOT NULL
  , functionName VARCHAR(40) NOT NULL
  , ord SMALLINT unsigned DEFAULT NULL
  , minimized TINYINT unsigned DEFAULT NULL
  , PRIMARY KEY (userId ,pageId, functionName)
) ;
CREATE INDEX leftpanel_user ON sys_leftpanel_users (userId ASC);
CREATE INDEX leftpanel_page ON sys_leftpanel_users (pageId ASC);
CREATE INDEX leftpanel_func ON sys_leftpanel_users (functionName ASC);

CREATE TABLE sys_sessions (
       sid VARCHAR(32) NOT NULL
     , hostname VARCHAR(80) NOT NULL
     , timestamp INT(10) NOT NULL DEFAULT 0
     , session LONGTEXT
     , PRIMARY KEY (sid)
)  ;
CREATE INDEX timestamp ON sys_sessions (timestamp ASC);

CREATE TABLE sys_pages_category (
       categoryId SMALLINT unsigned NOT NULL AUTO_INCREMENT
     , typeId VARCHAR(10) DEFAULT NULL
     , name VARCHAR(50) NOT NULL
     , description TEXT DEFAULT NULL
     , ord SMALLINT unsigned NOT NULL DEFAULT 0
     , public TINYINT unsigned NOT NULL DEFAULT 1
     , PRIMARY KEY (categoryId)
)  ;

CREATE TABLE sys_cron_log (
       id mediumint unsigned NOT NULL AUTO_INCREMENT
     , date DATETIME NOT NULL
     , text TEXT
     , PRIMARY KEY (id)
)  ;

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
)  ;
CREATE TABLE sys_users_pocket (
       pocketId mediumint unsigned NOT NULL AUTO_INCREMENT
     , userId MEDIUMINT unsigned NOT NULL
     , pageId varchar(5) NOT NULL
     , itemId mediumint unsigned default NULL
     , description VARCHAR(50) default NULL
     , dateCreated DATETIME NOT NULL
     , PRIMARY KEY (pocketId)
);
CREATE INDEX `pocket-user` ON sys_users_pocket (userId);

CREATE TABLE sys_users_draft (
  userId MEDIUMINT unsigned NOT NULL
  ,place VARCHAR(10) NOT NULL
  ,text  TEXT
  ,UNIQUE KEY `userDraft` (`userId`,`place`)
)  ;
CREATE INDEX `draft-user` ON sys_users_draft (userId);
CREATE INDEX `draft-place` ON sys_users_draft (place);

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
)  ;
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
)  ;
CREATE INDEX `pages-category` on sys_pages (categoryId);
CREATE INDEX `pages-top` on sys_pages (pageIdTop);
CREATE INDEX `pages-type` on sys_pages (typeId);
CREATE INDEX `pages-public` on sys_pages (public);
CREATE INDEX `pages-locked` on sys_pages (locked);
CREATE INDEX `pages-owner` on sys_pages (userIdOwner);

CREATE TABLE `sys_pages_properties` (
  `pageId` varchar(5) NOT NULL,
  `name` varchar(20) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY  (`pageId`,`name`)
) ;

CREATE TABLE sys_pages_relations (
  pageId varchar(5) NOT NULL
  ,pageIdRelative varchar(5) NOT NULL
  ,UNIQUE KEY `pageIdUni` (`pageId`,`pageIdRelative`)
)  ;
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
)  ;
CREATE INDEX `poll-pageId` on sys_poll (pageId);
CREATE INDEX `poll-userId` on sys_poll (userId);

CREATE TABLE sys_poll_answers (
       pollId smallint unsigned NOT NULL
     , pollAnswerId mediumint unsigned NOT NULL AUTO_INCREMENT
     , answer VARCHAR(255) NOT NULL
     , ord SMALLINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (pollAnswerId)
)  ;
CREATE INDEX `pollanswers-pollId` ON sys_poll_answers (pollId ASC);

CREATE TABLE sys_poll_answers_users (
       pollId smallint unsigned NOT NULL
     , pollAnswerId mediumint unsigned NOT NULL
     , userId MEDIUMINT unsigned NOT NULL
)  ;
CREATE INDEX `pollanswersusers-pollId` ON sys_poll_answers_users (pollId ASC);
CREATE INDEX `pollanswersusers-pollAnswerId` ON sys_poll_answers_users (pollAnswerId ASC);

CREATE TABLE sys_pages_items (
       itemId mediumint unsigned NOT NULL AUTO_INCREMENT
     , itemIdTop mediumint unsigned default NULL
     , itemIdBottom mediumint unsigned default NULL
     , typeId VARCHAR(10) DEFAULT null
     , pageId varchar(5) NOT NULL
     , pageIdBottom varchar(5) default NULL
     , categoryId SMALLINT unsigned DEFAULT null
     , userId MEDIUMINT unsigned default null
     , name VARCHAR(15) not null
     , dateStart DATETIME default NULL
     , dateEnd DATETIME default NULL
     , dateCreated DATETIME NOT NULL
     , text TEXT
     , enclosure VARCHAR(255) default null
     , addon VARCHAR(100) default null
     , filesize mediumint unsigned default null
     , hit mediumint unsigned default 0
     , cnt smallint unsigned not null default 0
     , tag_weight mediumint unsigned default 0
     , location VARCHAR(100) default null
     , public TINYINT unsigned NOT NULL DEFAULT 1
     , PRIMARY KEY (itemId)
)  ;
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
)  ;

CREATE TABLE sys_pages_items_hit (
  itemId mediumint unsigned NOT NULL,
  userId MEDIUMINT unsigned default NULL,
  dateCreated datetime NOT NULL default '0000-00-00 00:00:00'
);
CREATE INDEX `itemhit-user` ON sys_pages_items_hit (userId);
CREATE INDEX `itemhit-foto` ON sys_pages_items_hit (itemId);

CREATE TABLE `sys_pages_items_history` (
  `dateInt` varchar(10) NOT NULL,
  `itemId` mediumint(8) unsigned NOT NULL,
  `historyType` tinyint(3) unsigned NOT NULL default '0',
  `valueSum` mediumint(8) unsigned NOT NULL,
  UNIQUE KEY `dateInt` (`dateInt`,`itemId`,`historyType`),
  KEY `dateInt_2` (`dateInt`,`historyType`)
);

CREATE TABLE sys_pages_items_tag (
       itemId mediumint unsigned NOT NULL
     , userId MEDIUMINT unsigned NOT NULL
     , tag varchar(255) DEFAULT null
     , weight tinyint unsigned default null
     , dateCreated DATETIME not null
     ,UNIQUE KEY `itemsTag` (`itemId`,`userId`)
)  ;
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
)  ;
CREATE INDEX `banner-user` ON sys_banner (userId);

CREATE TABLE sys_banner_hit (
      bannerId smallint(6) unsigned NOT NULL
     , userId MEDIUMINT unsigned NOT NULL
     , dateCreated datetime not null
);
CREATE INDEX `bannerhit-user` ON sys_banner_hit (userId);
CREATE INDEX `bannerhit-banner` ON sys_banner_hit (bannerId);

CREATE TABLE `sys_pages_counter` (
  `pageId` varchar(5) NOT NULL,
  `typeId` varchar(10) NOT NULL,
  `userId` mediumint(8) unsigned NOT NULL default '0',
  `dateStamp` date NOT NULL,
  `hit` int(10) unsigned NOT NULL default '0',
  `ins` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`pageId`,`userId`,`dateStamp`)
);

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
)  ;
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
)  ;
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
)  ;
CREATE INDEX `menu-page` ON sys_menu (pageId);

CREATE TABLE sys_pages_favorites (
  userId MEDIUMINT unsigned NOT NULL,
  pageId varchar(5) NOT NULL,
  cnt mediumint unsigned unsigned NOT NULL,
  book tinyint unsigned unsigned NOT NULL,
  UNIQUE KEY userId_2 (userId,pageId)
)  ;
CREATE INDEX `fav-user` ON sys_pages_favorites (userId);
CREATE INDEX `fav-page` ON sys_pages_favorites (pageId);

CREATE TABLE sys_users_friends (
       userId MEDIUMINT unsigned NOT NULL
     , userIdFriend MEDIUMINT unsigned NOT NULL
     , dateCreated DATETIME NOT NULL
     , comment VARCHAR(100) default null
     , UNIQUE KEY `userId_2` (`userId`,`userIdFriend`)
)  ;
CREATE INDEX userId ON sys_users_friends (userId ASC);
CREATE INDEX userIdFriend ON sys_users_friends (userIdFriend ASC);

CREATE TABLE sys_menu_secondary (
     menuSecondaryGroup VARCHAR(10) default null
     , pageId varchar(5) NOT NULL
     , name VARCHAR(50) NOT NULL
     , public TINYINT unsigned NOT NULL DEFAULT 0
     , ord SMALLINT unsigned DEFAULT 0
     , PRIMARY KEY (menuSecondaryGroup,pageId)
)  ;
CREATE INDEX menusec_page ON sys_menu_secondary (pageId ASC);

CREATE TABLE sys_skin (
       skinId smallint unsigned NOT NULL AUTO_INCREMENT
     , userId MEDIUMINT unsigned NOT NULL
     , cssfilename VARCHAR(20) NOT NULL
     , dir VARCHAR(20) NOT NULL
     , name VARCHAR(20) NOT NULL
     , dateCreated DATETIME NOT NULL
     , PRIMARY KEY (skinId)
)  ;
CREATE INDEX skin_user ON sys_skin (userId ASC);

CREATE TABLE sys_users_buttons (
       buttonId smallint unsigned NOT NULL AUTO_INCREMENT
     , userId MEDIUMINT unsigned NOT NULL
     , name VARCHAR(20) NOT NULL
     , url VARCHAR(100) NOT NULL
     , ord TINYINT unsigned NOT NULL DEFAULT 0
     , PRIMARY KEY (buttonId)
)  ;
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
     , PRIMARY KEY (userId)
)  ;

CREATE TABLE sys_users_perm (
       userId MEDIUMINT unsigned NOT NULL
     , pageId varchar(5) NOT NULL
     , rules TINYINT unsigned NOT NULL DEFAULT 0
)  ;
CREATE INDEX perm_user ON sys_users_perm (userId ASC);
CREATE INDEX perm_page ON sys_users_perm (pageId ASC);

CREATE TABLE sys_users_perm_cache (
     typeId VARCHAR(10) not null
    , userId MEDIUMINT unsigned NOT NULL
     , pageId varchar(5) NOT NULL
);
CREATE INDEX perm_cache_type ON sys_users_perm_cache (typeId ASC);
CREATE INDEX perm_cache_user ON sys_users_perm_cache (userId ASC);
CREATE INDEX perm_cache_page ON sys_users_perm_cache (pageId ASC);

CREATE TABLE sys_users_cache (
     userId MEDIUMINT unsigned NOT NULL
    , name VARCHAR(20) not null
    , value VARCHAR(100) not null
    , dateUpdated DATETIME not null
    , PRIMARY KEY (userId,name)
);