truncate sys_pages;
INSERT INTO `sys_pages` VALUES ('maina', null, 'top', null, null, null, 1, 'bloged.main.php', '', 'Úvod', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
#INSERT INTO `sys_pages` VALUES ('cults', null, 'top', 'culture', null, null, 2, 'pages.list.php', 'Kultura - něco na čtení', 'Kultura', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('frien', null, 'top', null, null, 3, 3, 'user.friends.php', 'Přátelé - Informace o uživatelích', 'Přátelé', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
#INSERT INTO `sys_pages` VALUES ('tagli', null, 'top', null, null, null, 3, 'items.tags.php', 'Živé palce', 'Palce', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('booke', null, 'top', 'forum', null, null, 2, 'forums.booked.php', 'Oblíbené', 'Oblíbené', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('galer', null, 'top', 'galery', null, 4, 2, 'galery.list.php', 'Galerie - Fotografie a jiné obrázky', 'Galerie', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('galed', null, 'top', 'galery', null, 4, 2, 'galery.edit.php', 'Nové a úpravy Galerie', 'EditGal', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('galse', null, 'top', 'galery', null, 4, 2, 'galery.search.php', 'Galerie - Vyhledávání', 'GalSearch', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('taggi', null, 'top', null, null, 4, 2, 'items.tagging.randoms.php', 'Popisky k fotkam', 'Popisky', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('foall', null, 'top', 'forum', null, 5, 2, 'forums.list.php', 'Seznam všech klubů', 'Kluby', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forne', null, 'top', 'forum', null, 5, 2, 'forum.new.php', 'Založení nového klubu', 'Nový klub', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forli', null, 'top', 'forum', null, 5, 2, 'forums.live.php', 'Poslední príspěvky do klubů', 'FunDeLive', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fsear', null, 'top', 'forum', null, 5, 2, 'forums.search.php', 'Prohledávání všech klubů', 'Hledání', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('bloll', null, 'top', 'blog', null, 6, 2, 'forums.list.php', 'Seznam všech blogů', 'Blogy', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blone', null, 'top', 'blog', null, 6, 2, 'forum.new.php', 'Založení nového blogu', 'Nový blog', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('bloli', null, 'top', 'blog', null, 6, 2, 'forums.live.php', 'Poslední příspěvky do blogů', 'BlogLive', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blose', null, 'top', 'blog', null, 6, 2, 'forums.search.php', 'Prohledávání blogu', 'Hledání', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('roger', null, 'top', null, null, null, 1, 'registration.php', 'Registrace nového uživatele', 'Registrace', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('finfo', null, 'top', null, null, 3, 3, 'user.info.php', 'Informace o uživateli', 'Info', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fpost', null, 'top', null, null, 3, 10, 'user.post.php', 'Pošta', 'Pošta', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fsurf', null, 'top', null, null, 3, 3, 'user.surf.php', 'Oblíbené odkazy', 'Odkazy', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fdiar', null, 'top', null, null, 3, 3, 'user.diary.php', 'Osobní diář', 'Diář', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('ffall', null, 'top', null, null, 3, 3, 'user.friends.all.php', 'Seznam všech registrovaných uživatelů', 'VšechnyID', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fedit', null, 'top', null, null, 3, 3, 'user.settings.php', 'Nastavení osobních údajů', 'Osobní', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('event', null, 'event', null, null, null, 1, 'events.view.php', 'Tipy na kulturní a jiné akce', 'Tipy', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('sadmi', null, 'admin', null, null, 9, null, 'sys.edit.php', 'Tam jsou lvi', 'admin', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbann', null, 'admin', null, null, 9, null, 'sys.edit.users.banns.php', 'Kontrola nad uživateli - blokování', 'Banány', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('skate', null, 'admin', null, null, 9, null, 'sys.edit.categories.php', 'Editace kategorií', 'EditKatAudit', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbanr', null, 'admin', null, null, 9, null, 'sys.edit.banner.php', 'Bannery', 'editBanner', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('spoll', null, 'admin', null, null, 9, null, 'poll.edit.php', 'Ankety', 'editAnket', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sfunc', null, 'admin', null, null, 9, null, 'sys.edit.leftpanel.functions.php', 'Sidebar funkce', 'EditLFunc', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sleft', null, 'admin', null, null, 9, null, 'sys.edit.leftpanel.php', 'Propojení panel-stránka', 'EditLFuncStr', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('spaka', null, 'admin', null, null, 9, null, 'sys.edit.pages.php', 'Nastaveni stranek', 'Stranky', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

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
INSERT INTO `sys_leftpanel_functions` VALUES (1, 'rh_login', '', 1);
INSERT INTO `sys_leftpanel_functions` VALUES (10, 'pocket', 'Kapsa', 0);
INSERT INTO `sys_leftpanel_functions` VALUES (2, 'rh_datum', '', 1);
INSERT INTO `sys_leftpanel_functions` VALUES (5, 'rh_logged_list', 'OnLine uživatelé', 0);
INSERT INTO `sys_leftpanel_functions` VALUES (6, 'rh_galerie_rnd', 'Galerie', 1);
INSERT INTO `sys_leftpanel_functions` VALUES (8, 'rh_akce_rnd', 'Tipy', 1);
INSERT INTO `sys_leftpanel_functions` VALUES (9, 'rh_anketa', 'Anketa', 1);
INSERT INTO `sys_leftpanel_functions` VALUES (11, 'bookedRelatedPagesList', 'Podobne sledovane', 0);
INSERT INTO `sys_leftpanel_functions` VALUES (13, 'rh_diar_kalendar', 'Kalendář', 0);
INSERT INTO `sys_leftpanel_functions` VALUES (14, 'rh_audit_popis', 'Popis', 1);
INSERT INTO `sys_leftpanel_functions` VALUES (15, 'rh_posta_kdo', 'Přijaté zprávy', 0);
INSERT INTO `sys_leftpanel_functions` VALUES (23, 'relatedPagesList', 'Spřátelená stránky', 1);
#INSERT INTO `sys_leftpanel_functions` VALUES (16, 'rh_pocasi', 'Počasí', 1);
#INSERT INTO `sys_leftpanel_functions` VALUES (17, 'rh_pocasi_weatherdotcom', 'Weather', 1);

truncate sys_leftpanel;
INSERT INTO `sys_leftpanel` VALUES (1, 0, 1, 0);
INSERT INTO `sys_leftpanel` VALUES (15, 0, 10, 1);
INSERT INTO `sys_leftpanel` VALUES (2, 0, 23, 6);
INSERT INTO `sys_leftpanel` VALUES (3, 0, 2, 2);
INSERT INTO `sys_leftpanel` VALUES (4, 0, 9, 5);
INSERT INTO `sys_leftpanel` VALUES (5, 0, 5, 101);
INSERT INTO `sys_leftpanel` VALUES (6, 1, 8, 20);
INSERT INTO `sys_leftpanel` VALUES (7, 1, 6, 50);
INSERT INTO `sys_leftpanel` VALUES (8, 2, 6, 50);
INSERT INTO `sys_leftpanel` VALUES (9, 3, 13, 3);
INSERT INTO `sys_leftpanel` VALUES (10, 1, 13, 3);
INSERT INTO `sys_leftpanel` VALUES (11, 2, 13, 3);
INSERT INTO `sys_leftpanel` VALUES (12, 3, 6, 50);
INSERT INTO `sys_leftpanel` VALUES (13, 9, 14, 3);
INSERT INTO `sys_leftpanel` VALUES (14, 9, 11, 4);
INSERT INTO `sys_leftpanel` VALUES (16, 10, 15, 10);

truncate sys_menu_secondary;
INSERT INTO `sys_menu_secondary` VALUES (1, 3, 'fpost', 'Pošta', 0, 0);
INSERT INTO `sys_menu_secondary` VALUES (2, 3, 'finfo', 'Info', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (3, 3, 'fsurf', 'Odkazy', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (4, 3, 'fdiar', 'Diář', 0, 3);
INSERT INTO `sys_menu_secondary` VALUES (5, 3, 'ffall', 'Uživatelé', 0, 4);
INSERT INTO `sys_menu_secondary` VALUES (6, 3, 'fedit', 'Osobní', 0, 10);
INSERT INTO `sys_menu_secondary` VALUES (7, 3, 'sadmin', 'Admin', 0, 23);

INSERT INTO `sys_menu_secondary` VALUES (8, 4, 'galse', 'Hledání', 0, 1);

INSERT INTO `sys_menu_secondary` VALUES (9, 5, 'fsear', 'Hledání', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (10, 5, 'forli', 'Živě', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (11, 5, 'forne', 'Založit klub', 0, 3);

INSERT INTO `sys_menu_secondary` VALUES (12, 6, 'blose', 'Hledání', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (13, 6, 'bloli', 'Živě', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (14, 6, 'blone', 'Založit blog', 0, 3);


