truncate sys_pages;
INSERT INTO `sys_pages` VALUES ('maina', null, 'top', 'home', null, null, 'page_Main', '', 'Úvod', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('frien', null, 'top', 'friend', null, 2, 'page_UserFriends', 'Přátelé - Informace o uživatelích', 'Přátelé', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('booke', null, 'top', 'book', null, 3, 'page_PagesBooked', 'Oblíbené', 'Oblíbené', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('livea', null, 'top', 'book', null, 3, 'page_ItemsLive', 'Poslední pridane', 'FunDeLive', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('paged', null, 'top', 'culture', null, 2, 'page_PageEdit', 'Nova stranka', 'Nova stranka', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('galer', null, 'top', 'galery', null, 4, 'page_PagesList', 'Galerie', 'Galerie', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('galed', null, 'top', 'galery', null, 0, 'page_PageEdit', 'Založ galerii', 'EditGal', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('taggi', null, 'top', 'galery', null, 4, 'page_ItemsRaggingRandoms', 'Popisky k fotkam', 'Popisky', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('galbe', null, 'top', 'galery', null, 4, 'page_ItemsTags', 'Nejlepsi foto', 'Nej fot', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('foall', null, 'top', 'forum', null, 5, 'page_PagesList', 'Kluby', 'Kluby', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forne', null, 'top', 'forum', null, 5, 'page_PageEdit', 'Založ klub', 'Nový klub', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forli', null, 'top', 'forum', null, 5, 'page_ItemsLive', 'Poslední príspěvky do klubů', 'FunDeLive', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forls', null, 'top', 'forum', null, 5, 'page_ItemsSearch', 'Hledani príspěvků', 'Vyhledavani', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forbe', null, 'top', 'forum', null, 5, 'page_ItemsTags', 'Nejlepsi v klubech', 'Klub nej', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('bloll', null, 'top', 'blog', null, 6, 'page_PagesList', 'Blogy', 'Blogy', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blone', null, 'top', 'blog', null, 6, 'page_PageEdit', 'Založ blog', 'Nový blog', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('bloli', null, 'top', 'blog', null, 6, 'page_ItemsLive', 'Poslední příspěvky do blogů', 'BlogLive', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blols', null, 'top', 'blog', null, 6, 'page_ItemsSearch', 'Hledani clanků', 'Vyhledavani', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blobe', null, 'top', 'blog', null, 6, 'page_ItemsTags', 'Nejlepsi v blogach', 'Blog nej', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('roger', null, 'top', null, null, null, 'page_Registration', 'Registrace nového uživatele', 'Registrace', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('finfo', null, 'top', 'friend', null, 2, 'page_UserInfo', 'Informace o uživateli', 'Info', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fpost', null, 'top', 'post', null, 2, 'page_UserPost', 'Pošta', 'Pošta', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fsurf', null, 'top', 'friend', null, 2, 'page_UserSurf', 'Oblíbené odkazy', 'Odkazy', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fdiar', null, 'top', 'diary', null, 2, 'page_userDiary', 'Diář', 'Diář', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('ffall', null, 'top', 'friend', null, 2, 'page_UserFriendsAll', 'Seznam všech registrovaných uživatelů', 'VšechnyID', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fedit', null, 'top', 'friend', null, 2, 'page_UserSettings', 'Nastavení osobních údajů', 'Osobní', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('event', null, 'top', 'event', null, 7, 'page_EventsView', 'Tipy na kulturní a jiné akce', 'Tipy', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('eveac', null, 'top', 'event', null, null, 'page_EventsArchiv', 'Archiv tipu', 'Tipy archiv', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('evena', 'event', 'top', 'event', null, null, 'page_EventsEdit', 'Novy tip', 'Novy tip', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('sadmi', null, 'admin', null, null, 9, 'page_SysEdit', 'Tam jsou lvi', 'admin', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbann', null, 'admin', null, null, 9, 'page_SysEditUsersBanns', 'Kontrola nad uživateli - blokování', 'Banány', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('skate', null, 'admin', null, null, 9, 'page_SysEditCategories', 'Editace kategorií', 'EditKatAudit', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbanr', null, 'admin', null, null, 9, 'page_SysEditBanner', 'Bannery', 'editBanner', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('spoll', null, 'admin', null, null, 9, 'page_PollEdit', 'Ankety', 'editAnket', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sfunc', null, 'admin', null, null, 9, 'page_SysEditLeftpanelFunctions', 'Sidebar funkce', 'EditLFunc', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sleft', null, 'admin', null, null, 9, 'page_SysEditLeftpanel', 'Propojení panel-stránka', 'EditLFuncStr', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('spaka', null, 'admin', null, null, 9, 'page_SysEditPages', 'Nastaveni stranek', 'Stranky', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

truncate sys_menu;
INSERT INTO `sys_menu` VALUES (1, 'maina', 'Úvod', 1, 1);
INSERT INTO `sys_menu` VALUES (2, 'event', 'Tipy', 1, 2);
#INSERT INTO `sys_menu` VALUES (3, 'tagli', 'Palce', 0, 3);
INSERT INTO `sys_menu` VALUES (4, 'frien', 'Přátelé', 0, 4);
INSERT INTO `sys_menu` VALUES (5, 'booke', 'Oblíbené', 0, 5);
INSERT INTO `sys_menu` VALUES (6, 'galer', 'Galerie', 1, 6);
INSERT INTO `sys_menu` VALUES (7, 'foall', 'Kluby', 1, 7);
INSERT INTO `sys_menu` VALUES (8, 'bloll', 'Blogy', 1, 8);

truncate sys_leftpanel_functions;
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_login', '', 1, 1, null,null,'depend:user;lifeTime:30');
INSERT INTO `sys_leftpanel_functions` VALUES ('pocket', 'Kapsa', 0, 1, null,null,'depend:user;lifeTime:nocache');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_logged_list', 'OnLine uživatelé', 0, 1, null,null,'depend:user;lifeTime:10');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_galerie_rnd', 'Galerie', 1, 1, null,null,'depend:member;lifeTime:180');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_akce_rnd', 'Tipy', 1, 1, null,null,'depend:member;lifeTime:360');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_anketa', 'Anketa', 1, 1, null,null,'depend:user;lifeTime:86400');
INSERT INTO `sys_leftpanel_functions` VALUES ('bookedRelatedPagesList', 'Podobne sledovane', 0, 1, null,null,'depend:page,user;lifeTime:86400');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_diar_kalendar', 'Kalendář', 0, 1, null,null,'depend:user,page;lifeTime:86400');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_audit_popis', 'Popis', 1, 1, null,null,'depend:user,page;lifeTime:86400');
INSERT INTO `sys_leftpanel_functions` VALUES ('rh_posta_kdo', 'Přijaté zprávy', 0, 1, null,null,'depend:user;lifeTime:86400');
INSERT INTO `sys_leftpanel_functions` VALUES ('relatedPagesList', 'Spřátelená stránky', 1, 1, null,null,'depend:user,page;lifeTime:86400');
INSERT INTO `sys_leftpanel_functions` VALUES ('pageCategories','Kategorie', 1, 1, null, null,'depend:page;lifeTime:86400');

truncate sys_leftpanel_defaults;
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'rh_login', 0, 1);
/*INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'pocket', 1, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'rh_anketa', 2, 1);*/

INSERT INTO `sys_leftpanel_defaults` VALUES ('top', 'rh_diar_kalendar', 10, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('top', 'rh_akce_rnd', 20, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('top', 'rh_galerie_rnd', 40, 1);

INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'relatedPagesList', 80, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'rh_logged_list', 100, 1);

#skupina pro kluby
INSERT INTO `sys_leftpanel_defaults` VALUES ('forum', 'rh_audit_popis', 10, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('forum', 'bookedRelatedPagesList', 90, 1);

#skupina pro blogy
INSERT INTO `sys_leftpanel_defaults` VALUES ('blog', 'rh_audit_popis', 10, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('blog', 'rh_diar_kalendar', 20, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('blog', 'pageCategories', 30, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('blog', 'bookedRelatedPagesList', 90, 1);

#skupina pro galerie
INSERT INTO `sys_leftpanel_defaults` VALUES ('galery', 'bookedRelatedPagesList', 90, 1);

#pouze posta
INSERT INTO `sys_leftpanel_defaults` VALUES ('post', 'rh_posta_kdo', 10, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('post', 'rh_diar_kalendar', 20, 1);

truncate sys_menu_secondary;
INSERT INTO `sys_menu_secondary` VALUES (2, 'fpost', 'Pošta', 0, 0);
/*INSERT INTO `sys_menu_secondary` VALUES (2, 'finfo', 'Info', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (2, 'fsurf', 'Odkazy', 0, 2);*/
INSERT INTO `sys_menu_secondary` VALUES (2, 'fdiar', 'Diář', 0, 3);
/*INSERT INTO `sys_menu_secondary` VALUES (2, 'ffall', 'Uživatelé', 0, 4);*/
INSERT INTO `sys_menu_secondary` VALUES (2, 'fedit', 'Osobní', 0, 10);
INSERT INTO `sys_menu_secondary` VALUES (2, 'sadmin', 'Admin', 0, 23);

INSERT INTO `sys_menu_secondary` VALUES (3, 'livea', 'Živě', 0, 0);

/*INSERT INTO `sys_menu_secondary` VALUES (4, 'taggi', 'Popisuj fotky', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (4, 'galse', 'Hledani galerii', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (4, 'galbe', 'Nej', 0, 3);*/
INSERT INTO `sys_menu_secondary` VALUES (4, 'galed', 'Založit galerii', 0, 4);

INSERT INTO `sys_menu_secondary` VALUES (5, 'forlw', 'Hledani klubu', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (5, 'forls', 'Hledani v prispevcich', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (5, 'forbe', 'Nej', 0, 3);
INSERT INTO `sys_menu_secondary` VALUES (5, 'forli', 'Živě', 0, 4);
INSERT INTO `sys_menu_secondary` VALUES (5, 'forne', 'Založit klub', 0, 5);

INSERT INTO `sys_menu_secondary` VALUES (6, 'blolw', 'Hledani blogu', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (6, 'blols', 'Hledani v clancich', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (6, 'blobe', 'Nej', 0, 3);
INSERT INTO `sys_menu_secondary` VALUES (6, 'bloli', 'Živě', 0, 4);
INSERT INTO `sys_menu_secondary` VALUES (6, 'blone', 'Založit blog', 0, 5);

INSERT INTO `sys_menu_secondary` VALUES (7, 'evena', 'Novy tip', 0, 1);