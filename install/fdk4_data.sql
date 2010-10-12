#truncate sys_pages;
delete from sys_pages where typeId='top';
INSERT INTO `sys_pages` VALUES ('maina', null, 'top', 'home', null, 'page_PageItemList', '', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('frien', null, 'top', 'friend', null, 'page_UserFriends', 'Přátelé - Informace o uživatelích', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('booke', null, 'top', 'book', null, 'page_PagesBooked', 'Oblíbené', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;

INSERT INTO `sys_pages` VALUES ('searc', null, 'top', 'top', null, 'page_Search', 'Vyhledavani', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;

INSERT INTO `sys_pages` VALUES ('galer', null, 'top', 'galery', null, 'page_PagesList', 'Galerie', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('foall', null, 'top', null, null, 'page_PagesList', 'Prehled', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;

INSERT INTO `sys_pages` VALUES ('roger', null, 'top', null, null, 'page_Registration', 'Registrace', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('fpost', null, 'top', 'post', null, 'page_UserPost', 'Pošta', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('finfo', null, 'top', 'friend', null, 'page_UserInfo', 'Profil', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('fedit', null, 'top', 'friend', null, 'page_UserSettings', 'Nastavení osobního profilu', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;

INSERT INTO `sys_pages` VALUES ('event', null, 'event', null, null, 'page_PageItemList', 'Tipy na kulturní a jiné akce', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, 'page/event') ;

/*INSERT INTO `sys_pages` VALUES ('taggi', null, 'top', 'galery', null, 'page_ItemsRaggingRandoms', 'Popisky k fotkam', 'Popisky', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('fsurf', null, 'top', 'friend', null, 'page_UserSurf', 'Oblíbené odkazy', 'Odkazy', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('fdiar', null, 'top', 'diary', null, 'page_userDiary', 'Diář', 'Diář', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('ffall', null, 'top', 'friend', null, 'page_UserFriendsAll', 'Seznam všech registrovaných uživatelů', 'VšechnyID', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
*/

INSERT INTO `sys_pages` VALUES ('sadmi', null, 'admin', null, null, 'page_SysEdit', 'Tam jsou lvi', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbann', null, 'admin', null, null, 'page_SysEditUsersBanns', 'Kontrola nad uživateli - blokování', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('skate', null, 'admin', null, null, 'page_SysEditCategories', 'Editace kategorií', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('spoll', null, 'admin', null, null, 'page_PollEdit', 'Ankety', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sfunc', null, 'admin', null, null, 'page_SysEditLeftpanelFunctions', 'Sidebar funkce', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('sleft', null, 'admin', null, null, 'page_SysEditLeftpanel', 'Propojení panel-stránka', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('spaka', null, 'admin', null, null, 'page_SysEditPages', 'Nastaveni stranek', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;

truncate sys_menu;
INSERT INTO `sys_menu` VALUES ('maina', 'maina', 'Úvod', 1, 1);
INSERT INTO `sys_menu` VALUES ('maina', 'event', 'Tipy', 1, 2);
INSERT INTO `sys_menu` VALUES ('maina', 'galer', 'Galerie', 1, 3);
INSERT INTO `sys_menu` VALUES ('maina', 'foall', 'Prehled', 1, 4);
INSERT INTO `sys_menu` VALUES ('maina', 'frien', 'Přátelé', 0, 7);
INSERT INTO `sys_menu` VALUES ('maina', 'booke', 'Oblíbené', 0, 8);

INSERT INTO `sys_menu` VALUES ('idega', 'idega', 'Blog', 1, 1);
INSERT INTO `sys_menu` VALUES ('idega', 'ideg1', 'Neco o me', 1, 2);
INSERT INTO `sys_menu` VALUES ('idega', 'ideg2', 'Hudba', 1, 3);
INSERT INTO `sys_menu` VALUES ('idega', 'galer', 'Galerie', 1, 4);
INSERT INTO `sys_menu` VALUES ('idega', 'maina', 'Live', 0, 5);
INSERT INTO `sys_menu` VALUES ('idega', 'frien', 'Přátelé', 0, 7);
INSERT INTO `sys_menu` VALUES ('idega', 'booke', 'Oblíbené', 0, 8);

truncate sys_leftpanel_functions;
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_login', '', 1, 1, null,null,'depend:user;lifeTime:30');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_galerie_rnd', 'Galerie', 1, 1, null,null,'depend:member;lifeTime:180');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_akce_rnd', 'Tipy', 1, 1, null,null,'depend:member;lifeTime:360');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_anketa', 'Anketa', 1, 1, null,null,'depend:user;lifeTime:86400');
INSERT INTO `sys_leftpanel_functions` VALUES ('bookedRelatedPagesList', 'Podobne sledovane', 0, 1, null,null,'depend:page,user;lifeTime:86400');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_diar_kalendar', 'Kalendář', 0, 1, null,null,'depend:user,page;lifeTime:86400');
INSERT INTO `sys_leftpanel_functions` VALUES ('pageCategories','Kategorie', 1, 1, null, null,'depend:page;lifeTime:86400');

truncate sys_leftpanel_defaults;
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'rh_login', 0, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'pageCategories', 20, 1);
#skupina top stranek
INSERT INTO `sys_leftpanel_defaults` VALUES ('top', 'rh_diar_kalendar', 10, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('top', 'rh_akce_rnd', 30, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('top', 'rh_galerie_rnd', 40, 1);
#skupina pro kluby
INSERT INTO `sys_leftpanel_defaults` VALUES ('forum', 'bookedRelatedPagesList', 90, 1);
#skupina pro blogy
INSERT INTO `sys_leftpanel_defaults` VALUES ('blog', 'rh_diar_kalendar', 20, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('blog', 'bookedRelatedPagesList', 90, 1);
#skupina pro galerie
INSERT INTO `sys_leftpanel_defaults` VALUES ('galery', 'bookedRelatedPagesList', 90, 1);