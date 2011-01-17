delete from sys_pages where typeId not in ('forum','blog','galery','culture');
INSERT INTO `sys_pages` VALUES ('maina', null, 'top', null, null, 'page_ItemsList', '', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL);
INSERT INTO `sys_pages` VALUES ('mapon', null, 'top', null, null, 'page_Map', 'Mapa', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL);
INSERT INTO `sys_pages` VALUES ('booke', null, 'top', 'book', null, 'page_PagesBooked', 'Oblíbené', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('searc', null, 'top', 'top', null, 'page_Search', 'Vyhledávání', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('galer', null, 'top', 'galery', null, 'page_PagesList', 'Galerie', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('foall', null, 'top', null, null, 'page_PagesList', 'Přehled', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('roger', null, 'top', null, null, 'page_Registration', 'Registrace', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('fpost', null, 'top', 'post', null, 'page_UserPost', 'Pošta', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('finfo', null, 'top', 'friend', null, 'page_UserInfo', 'Profil', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('fedit', null, 'top', 'friend', null, 'page_UserSettings', 'Nastavení osobního profilu', NULL, NULL, 2, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('event', null, 'event', null, null, 'page_ItemsList', 'Tipy na kulturní a jiné akce', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, 'page/event') ;
INSERT INTO `sys_pages` VALUES ('sadmi', null, 'admin', null, null, 'page_SysEdit', 'Tam jsou lvi', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('sbann', null, 'admin', null, null, 'page_SysEditUsersBanns', 'Kontrola nad uživateli - blokování', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('skate', null, 'admin', null, null, 'page_SysEditCategories', 'Editace kategorií', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;
INSERT INTO `sys_pages` VALUES ('spaka', null, 'admin', null, null, 'page_SysEditPages', 'Nastaveni stranek', NULL, NULL, 3, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;

INSERT INTO `sys_pages` VALUES ('gamas', null, 'top', null, null, 'page_fotoMashup', 'Foto Mix', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL) ;

truncate sys_menu;
INSERT INTO `sys_menu` VALUES ('maina', 'maina', 'Úvod', 1, 1);
INSERT INTO `sys_menu` VALUES ('maina', 'event', 'Tipy', 1, 2);
INSERT INTO `sys_menu` VALUES ('maina', 'galer', 'Galerie', 1, 3);
INSERT INTO `sys_menu` VALUES ('maina', 'finfo', 'Přátelé', 0, 7);
INSERT INTO `sys_menu` VALUES ('maina', 'booke', 'Oblíbené', 0, 8);

INSERT INTO `sys_menu` VALUES ('idega', 'idega', 'Blog', 1, 1);
INSERT INTO `sys_menu` VALUES ('idega', 'ideg1', 'Něco o mě', 1, 2);
INSERT INTO `sys_menu` VALUES ('idega', 'ideg2', 'Hudba', 1, 3);
INSERT INTO `sys_menu` VALUES ('idega', 'galer', 'Galerie', 1, 4);
INSERT INTO `sys_menu` VALUES ('idega', 'maina', 'Přehled', 0, 5);
INSERT INTO `sys_menu` VALUES ('idega', 'finfo', 'Přátelé', 0, 7);

INSERT INTO `sys_menu` VALUES ('awake', 'maina', 'Blog', 1, 1);
INSERT INTO `sys_menu` VALUES ('awake', 'fl327', 'Vzkazy', 1, 2);
INSERT INTO `sys_menu` VALUES ('awake', 'galer', 'Galerie', 1, 4);
INSERT INTO `sys_menu` VALUES ('awake', 'finfo', 'Přátelé', 0, 7);

INSERT INTO `sys_menu` VALUES ('ehZJn', 'ehZJn', 'Blog', 1, 1);
INSERT INTO `sys_menu` VALUES ('ehZJn', 'qpzmb', 'Vzkazy', 1, 2);
INSERT INTO `sys_menu` VALUES ('ehZJn', 'mapon', 'Mapa', 1, 3);
INSERT INTO `sys_menu` VALUES ('ehZJn', 'galer', 'Galerie', 1, 4);
INSERT INTO `sys_menu` VALUES ('ehZJn', 'maina', 'Přehled', 0, 5);
INSERT INTO `sys_menu` VALUES ('ehZJn', 'finfo', 'Přátelé', 0, 6);

delete from sys_menu where pageIdTop='Nr1ZZ';
INSERT INTO `sys_menu` VALUES ('Nr1ZZ', 'Nr1ZZ', 'Novinky', 1, 1);
INSERT INTO `sys_menu` VALUES ('Nr1ZZ', 'Nr3ZZ', 'O nás', 1, 2);
INSERT INTO `sys_menu` VALUES ('Nr1ZZ', 'galer', 'Galerie', 1, 3);
INSERT INTO `sys_menu` VALUES ('Nr1ZZ', 'Nr5ZZ', 'Video', 1, 4);
INSERT INTO `sys_menu` VALUES ('Nr1ZZ', 'Nr2ZZ', 'Kontakt', 1, 5);
INSERT INTO `sys_menu` VALUES ('Nr1ZZ', 'Nr4ZZ', 'Přispějte', 1, 6);

INSERT INTO `sys_pages` VALUES ('Nr2ZZ', null, 'culture', null, null, null, 'Kontakt', NULL, NULL, 1, now(), NULL, NULL, 1, NULL, 0, 0, NULL);
INSERT INTO `sys_pages` VALUES ('Nr3ZZ',NULL,'culture',NULL,0,'','O nás',	'',	'',1,now(),null,null,1,NULL,0,0,NULL);
INSERT INTO `sys_pages` VALUES ('Nr4ZZ',NULL,'culture',NULL,0,'','Přispějte',	'',	'',1,now(),null,null,1,NULL,0,0,NULL);


truncate sys_leftpanel_functions;
INSERT INTO `sys_leftpanel_functions` VALUES ('login', '', 1, 1, null,null,null);
INSERT INTO `sys_leftpanel_functions` VALUES ('galeryRand', 'Galerie', 1, 1, null,null,null);
INSERT INTO `sys_leftpanel_functions` VALUES ('map', 'Mapa', 1, 1, null,null,'cache:page,pageparam,category,item');
INSERT INTO `sys_leftpanel_functions` VALUES ('categories','Kategorie', 1, 1, null, null,'cache:page,pageparam,category');

truncate sys_leftpanel_defaults;
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'login', 0, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'categories', 30, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('top', 'galeryRand', 10, 1);
INSERT INTO `sys_leftpanel_defaults` VALUES ('default', 'map', 20, 1);
