truncate sys_pages;
INSERT INTO `sys_pages` VALUES ('maina', null, 'top', 'home', null, null, 'bloged.main.php', '', 'Úvod', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('frien', null, 'top', 'friend', null, 2, 'user.friends.php', 'Přátelé - Informace o uživatelích', 'Přátelé', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('booke', null, 'top', 'book', null, 3, 'pages.booked.php', 'Oblíbené', 'Oblíbené', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('livea', null, 'top', 'book', null, 3, 'items.live.php', 'Poslední pridane', 'FunDeLive', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('galer', null, 'top', 'galery', null, 4, 'galery.list.php', 'Galerie - Fotografie a jiné obrázky', 'Galerie', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('galed', null, 'top', 'galery', null, 4, 'galery.edit.php', 'Nové a úpravy Galerie', 'EditGal', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('taggi', null, 'top', 'galery', null, 4, 'items.tagging.randoms.php', 'Popisky k fotkam', 'Popisky', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('galbe', null, 'top', 'galery', null, 4, 'items.tags.php', 'Nejlepsi foto', 'Nej fot', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('galse', null, 'top', 'galery', null, 4, 'pages.search.php', 'Hledani galerii', 'Vyhledavani', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('foall', null, 'top', 'forum', null, 5, 'pages.list.php', 'Seznam všech klubů', 'Kluby', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forne', null, 'top', 'forum', null, 5, 'page.new.simple.php', 'Založení nového klubu', 'Nový klub', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forli', null, 'top', 'forum', null, 5, 'items.live.php', 'Poslední príspěvky do klubů', 'FunDeLive', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forlw', null, 'top', 'forum', null, 5, 'pages.search.php', 'Vyhledavani klubů', 'Vyhledavani', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forls', null, 'top', 'forum', null, 5, 'items.search.php', 'Hledani príspěvků', 'Vyhledavani', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('forbe', null, 'top', 'forum', null, 5, 'items.tags.php', 'Nejlepsi v klubech', 'Klub nej', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('bloll', null, 'top', 'blog', null, 6, 'pages.list.php', 'Seznam všech blogů', 'Blogy', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blone', null, 'top', 'blog', null, 6, 'page.new.simple.php', 'Založení nového blogu', 'Nový blog', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('bloli', null, 'top', 'blog', null, 6, 'items.live.php', 'Poslední příspěvky do blogů', 'BlogLive', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blolw', null, 'top', 'blog', null, 6, 'pages.search.php', 'Vyhledavani blogů', 'Vyhledavani', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blols', null, 'top', 'blog', null, 6, 'items.search.php', 'Hledani clanků', 'Vyhledavani', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('blobe', null, 'top', 'blog', null, 6, 'items.tags.php', 'Nejlepsi v blogach', 'Blog nej', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('roger', null, 'top', null, null, null, 'registration.php', 'Registrace nového uživatele', 'Registrace', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('finfo', null, 'top', 'friend', null, 2, 'user.info.php', 'Informace o uživateli', 'Info', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fpost', null, 'top', 'post', null, 2, 'user.post.php', 'Pošta', 'Pošta', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fsurf', null, 'top', 'friend', null, 2, 'user.surf.php', 'Oblíbené odkazy', 'Odkazy', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fdiar', null, 'top', 'diary', null, 2, 'user.diary.php', 'Osobní diář', 'Diář', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('ffall', null, 'top', 'friend', null, 2, 'user.friends.all.php', 'Seznam všech registrovaných uživatelů', 'VšechnyID', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('fedit', null, 'top', 'friend', null, 2, 'user.settings.php', 'Nastavení osobních údajů', 'Osobní', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('event', null, 'top', 'event', null, 7, 'events.view.php', 'Tipy na kulturní a jiné akce', 'Tipy', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('evena', null, 'top', 'event', null, 7, 'events.edit.php', 'Novy tip', 'Novy tip', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

INSERT INTO `sys_pages` VALUES ('sadmi', null, 'admin', null, null, 9, 'sys.edit.php', 'Tam jsou lvi', 'admin', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbann', null, 'admin', null, null, 9, 'sys.edit.users.banns.php', 'Kontrola nad uživateli - blokování', 'Banány', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('skate', null, 'admin', null, null, 9, 'sys.edit.categories.php', 'Editace kategorií', 'EditKatAudit', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbanr', null, 'admin', null, null, 9, 'sys.edit.banner.php', 'Bannery', 'editBanner', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('spoll', null, 'admin', null, null, 9, 'poll.edit.php', 'Ankety', 'editAnket', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sfunc', null, 'admin', null, null, 9, 'sys.edit.leftpanel.functions.php', 'Sidebar funkce', 'EditLFunc', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('sleft', null, 'admin', null, null, 9, 'sys.edit.leftpanel.php', 'Propojení panel-stránka', 'EditLFuncStr', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;
INSERT INTO `sys_pages` VALUES ('spaka', null, 'admin', null, null, 9, 'sys.edit.pages.php', 'Nastaveni stranek', 'Stranky', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL, NULL, NULL) ;

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
INSERT INTO `sys_leftpanel` VALUES (1, 'default', 'rh_login', 0);
INSERT INTO `sys_leftpanel` VALUES (2, 'default', 'pocket', 1);
INSERT INTO `sys_leftpanel` VALUES (3, 'default', 'rh_anketa', 2);

INSERT INTO `sys_leftpanel` VALUES (4, 'top', 'rh_diar_kalendar', 10);
INSERT INTO `sys_leftpanel` VALUES (5, 'top', 'rh_akce_rnd', 20);
INSERT INTO `sys_leftpanel` VALUES (6, 'top', 'rh_galerie_rnd', 40);

INSERT INTO `sys_leftpanel` VALUES (7, 'default', 'relatedPagesList', 90);
INSERT INTO `sys_leftpanel` VALUES (8, 'default', 'rh_logged_list', 100);

#skupina pro kluby
INSERT INTO `sys_leftpanel` VALUES (13, 'forum', 'rh_audit_popis', 10;
INSERT INTO `sys_leftpanel` VALUES (14, 'forum', 'bookedRelatedPagesList', 60);

#skupina pro blogy
INSERT INTO `sys_leftpanel` VALUES (13, 'blog', 'rh_audit_popis', 10);
INSERT INTO `sys_leftpanel` VALUES (10, 'blog', 'rh_diar_kalendar', 20);
INSERT INTO `sys_leftpanel` VALUES (14, 'blog', 'bookedRelatedPagesList', 60);

#skupina pro galerie
INSERT INTO `sys_leftpanel` VALUES (14, 'galery', 'bookedRelatedPagesList', 60);

#pouze posta
INSERT INTO `sys_leftpanel` VALUES (16, 'post', 'rh_posta_kdo', 10);
INSERT INTO `sys_leftpanel` VALUES (4, 'top', 'rh_diar_kalendar', 20);

truncate sys_menu_secondary;
INSERT INTO `sys_menu_secondary` VALUES (1, 2, 'fpost', 'Pošta', 0, 0);
INSERT INTO `sys_menu_secondary` VALUES (2, 2, 'finfo', 'Info', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (3, 2, 'fsurf', 'Odkazy', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (4, 2, 'fdiar', 'Diář', 0, 3);
INSERT INTO `sys_menu_secondary` VALUES (5, 2, 'ffall', 'Uživatelé', 0, 4);
INSERT INTO `sys_menu_secondary` VALUES (6, 2, 'fedit', 'Osobní', 0, 10);
INSERT INTO `sys_menu_secondary` VALUES (7, 2, 'sadmin', 'Admin', 0, 23);

INSERT INTO `sys_menu_secondary` VALUES (6, 3, 'livea', 'Živě', 0, 0);

INSERT INTO `sys_menu_secondary` VALUES (11, 4, 'taggi', 'Popisuj fotky', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (12, 4, 'galse', 'Hledani galerii', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (13, 4, 'galbe', 'Nej', 0, 3);
INSERT INTO `sys_menu_secondary` VALUES (14, 4, 'galed', 'Založit galerii', 0, 4);

INSERT INTO `sys_menu_secondary` VALUES (21, 5, 'forlw', 'Hledani klubu', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (22, 5, 'forls', 'Hledani v prispevcich', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (23, 5, 'forbe', 'Nej', 0, 3);
INSERT INTO `sys_menu_secondary` VALUES (24, 5, 'forli', 'Živě', 0, 4);
INSERT INTO `sys_menu_secondary` VALUES (25, 5, 'forne', 'Založit klub', 0, 5);

INSERT INTO `sys_menu_secondary` VALUES (31, 6, 'blolw', 'Hledani blogu', 0, 1);
INSERT INTO `sys_menu_secondary` VALUES (32, 6, 'blols', 'Hledani v clancich', 0, 2);
INSERT INTO `sys_menu_secondary` VALUES (33, 6, 'blobe', 'Nej', 0, 3);
INSERT INTO `sys_menu_secondary` VALUES (34, 6, 'bloli', 'Živě', 0, 4);
INSERT INTO `sys_menu_secondary` VALUES (35, 6, 'blone', 'Založit blog', 0, 5);

INSERT INTO `sys_menu_secondary` VALUES (41, 7, 'evena', 'Novy tip', 0, 1);