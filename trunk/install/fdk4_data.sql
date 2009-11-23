truncate sys_pages;
INSERT INTO `sys_pages` VALUES ('maina', null, 'top', 'home', null, null, 'page_Main', '', 'Úvod', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('frien', null, 'top', 'friend', null, 2, 'page_UserFriends', 'Přátelé - Informace o uživatelích', 'Přátelé', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('booke', null, 'top', 'book', null, null, 'page_PagesBooked', 'Oblíbené', 'Oblíbené', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('livea', null, 'top', 'book', null, null, 'page_ItemsLive', 'Poslední pridane', 'FunDeLive', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('forls', null, 'top', 'top', null, 5, 'page_Search', 'Vysledky vyhledavani', 'Vyhledavani', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forbe', null, 'top', 'top', null, 5, 'page_ItemsTags', 'Palce', 'Palce', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('paged', null, 'top', 'culture', null, 2, 'page_PageEdit', 'Nova stranka', 'Nova stranka', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('galer', null, 'top', 'galery', null, 4, 'page_PagesList', 'Galerie', 'Galerie', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('galed', null, 'top', 'galery', null, 0, 'page_PageEdit', 'Založ galerii', 'EditGal', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('foall', null, 'top', 'forum', null, 5, 'page_PagesList', 'Kluby', 'Kluby', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forne', null, 'top', 'forum', null, null, 'page_PageEdit', 'Založ klub', 'Nový klub', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('bloll', null, 'top', 'blog', null, 6, 'page_PagesList', 'Blogy', 'Blogy', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blone', null, 'top', 'blog', null, null, 'page_PageEdit', 'Založ blog', 'Nový blog', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('roger', null, 'top', null, null, null, 'page_Registration', 'Registrace nového uživatele', 'Registrace', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fpost', null, 'top', 'post', null, 2, 'page_UserPost', 'Pošta', 'Pošta', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('finfo', null, 'top', 'friend', null, null, 'page_UserInfo', 'Profil uživatele', 'Profil', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fedit', null, 'top', 'friend', null, 2, 'page_UserSettings', 'Nastavení osobního profilu', 'Nastaveni', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('event', null, 'top', 'event', null, 7, 'page_EventsView', 'Tipy na kulturní a jiné akce', 'Tipy', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('eveac', null, 'top', 'event', null, null, 'page_EventsArchiv', 'Archiv tipu', 'Tipy archiv', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('evena', null, 'top', 'event', null, null, 'page_EventsEdit', 'Novy tip', 'Novy tip', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

/*INSERT INTO `sys_pages` VALUES ('taggi', null, 'top', 'galery', null, 4, 'page_ItemsRaggingRandoms', 'Popisky k fotkam', 'Popisky', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fsurf', null, 'top', 'friend', null, 2, 'page_UserSurf', 'Oblíbené odkazy', 'Odkazy', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fdiar', null, 'top', 'diary', null, 2, 'page_userDiary', 'Diář', 'Diář', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('ffall', null, 'top', 'friend', null, 2, 'page_UserFriendsAll', 'Seznam všech registrovaných uživatelů', 'VšechnyID', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
*/

INSERT INTO `sys_pages` VALUES ('sadmi', null, 'admin', null, null, 9, 'page_SysEdit', 'Tam jsou lvi', 'admin', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbann', null, 'admin', null, null, 9, 'page_SysEditUsersBanns', 'Kontrola nad uživateli - blokování', 'Banány', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('skate', null, 'admin', null, null, 9, 'page_SysEditCategories', 'Editace kategorií', 'EditKatAudit', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbanr', null, 'admin', null, null, 9, 'page_SysEditBanner', 'Bannery', 'editBanner', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('spoll', null, 'admin', null, null, 9, 'page_PollEdit', 'Ankety', 'editAnket', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sfunc', null, 'admin', null, null, 9, 'page_SysEditLeftpanelFunctions', 'Sidebar funkce', 'EditLFunc', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sleft', null, 'admin', null, null, 9, 'page_SysEditLeftpanel', 'Propojení panel-stránka', 'EditLFuncStr', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('spaka', null, 'admin', null, null, 9, 'page_SysEditPages', 'Nastaveni stranek', 'Stranky', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

truncate sys_menu;
INSERT INTO `sys_menu` VALUES ('maina', 'maina', 'Úvod', 1, 1);
INSERT INTO `sys_menu` VALUES ('maina', 'event', 'Tipy', 1, 2);
INSERT INTO `sys_menu` VALUES ('maina', 'galer', 'Galerie', 1, 3);
INSERT INTO `sys_menu` VALUES ('maina', 'foall', 'Kluby', 1, 4);
INSERT INTO `sys_menu` VALUES ('maina', 'bloll', 'Blogy', 1, 5);
INSERT INTO `sys_menu` VALUES ('maina', 'livea', 'Živě', 0, 6);
INSERT INTO `sys_menu` VALUES ('maina', 'frien', 'Přátelé', 0, 7);
INSERT INTO `sys_menu` VALUES ('maina', 'booke', 'Oblíbené', 0, 8);

INSERT INTO `sys_menu` VALUES ('idega', 'idega', 'Blog', 1, 1);
INSERT INTO `sys_menu` VALUES ('idega', 'ideg1', 'Neco o me', 1, 2);
INSERT INTO `sys_menu` VALUES ('idega', 'ideg2', 'Hudba', 1, 3);
INSERT INTO `sys_menu` VALUES ('idega', 'galer', 'Galerie', 1, 4);
INSERT INTO `sys_menu` VALUES ('idega', 'foall', 'Kluby', 0, 5);
INSERT INTO `sys_menu` VALUES ('idega', 'bloll', 'Blogy', 0, 6);
INSERT INTO `sys_menu` VALUES ('idega', 'frien', 'Přátelé', 0, 7);
INSERT INTO `sys_menu` VALUES ('idega', 'booke', 'Oblíbené', 0, 8);

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
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'rh_audit_popis', 10, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'pageCategories', 20, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'relatedPagesList', 80, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'rh_logged_list', 100, 1);
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
#pouze posta
INSERT INTO `sys_leftpanel_defaults` VALUES ('post', 'rh_posta_kdo', 10, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('post', 'rh_diar_kalendar', 20, 1);

truncate sys_menu_secondary;
INSERT INTO `sys_menu_secondary` VALUES (2, 'fpost', 'Pošta', 0, 0);
INSERT INTO `sys_menu_secondary` VALUES (2, 'fedit', 'Osobní', 0, 10);
INSERT INTO `sys_menu_secondary` VALUES (2, 'sadmin', 'Admin', 0, 23);
INSERT INTO `sys_menu_secondary` VALUES (4, 'galed', 'Založit galerii', 0, 4);
INSERT INTO `sys_menu_secondary` VALUES (5, 'forne', 'Založit klub', 0, 5);
INSERT INTO `sys_menu_secondary` VALUES (6, 'blone', 'Založit blog', 0, 5);
INSERT INTO `sys_menu_secondary` VALUES (7, 'evena', 'Novy tip', 0, 1);