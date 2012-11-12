<?php
$q = array(
"update sys_pages_items set enclosure=concat('page/event/',enclosure) where typeId='event' and enclosure is not null"
);
MIGRATION:
1.copy files http://fundekave.net/migrate/filescopy.php
2.dump db http://fundekave.net/migrate/dbDump.php  
3.copy db http://fundekave.net/migrate/dbcopy.php
4.import db http://awake33.com/migrate/import.php
4.run ___db_exp/premigration.sql on awake db
5.run following
delete FROM `sys_pages_items` where typeId='' or typeId is null;
update sys_users set avatar=null where avatar like "%nobody%";
update sys_pages set galeryDir='page/event' where pageId='event';
update sys_pages set galeryDir=concat('page/',pageId) where typeId='blog' or typeId='forum';
update sys_pages_items set text = concat(text,"<br /><br />\n",enclosure) where typeId='forum' and enclosure is not null;
update sys_pages_items set text = concat(text,"<br /><br />\n<a href=\"http://fundekave.net/?i=",itemIdBottom,"\">Odkaz</a>") where (itemIdBottom is not null && itemIdBottom != 0);
update sys_pages_items set text = concat(text,"<br /><br />\n<a href=\"http://fundekave.net/?k=",pageIdBottom,"\">Odkaz</a>") where (pageIdBottom is not null && pageIdBottom != 0);
update sys_pages_items set text=concat(text,'<br/> ',enclosure),enclosure=null where typeId='forum' and enclosure is not null and enclosure != '';
update sys_pages_items set dateStart=dateCreated where typeId='forum';
ALTER TABLE `sys_pages_items` DROP `itemIdBottom`,CHANGE `pageIdBottom` `pageIdTop` varchar(5) NULL AFTER `pageId`;
ALTER TABLE `sys_users` DROP `skinId`,DROP `zbanner`,DROP `zavatar`,DROP `zforumico`,DROP `zgalerytype`;
ALTER TABLE `sys_users_friends` DROP `comment`;
DROP sys_menu_secondary;
ALTER TABLE `sys_pages` DROP `menuSecondaryGroup`,DROP `nameshort`,DROP `authorContent`,DROP `pageParams`;
update sys_pages set template=null where typeId in ('forum','blog','galery');
ALTER TABLE `sys_pages_properties` CHANGE `value` `value` text COLLATE 'utf8_general_ci' NOT NULL AFTER `name`;
ALTER TABLE `sys_pages_items_properties` CHANGE `value` `value` text COLLATE 'utf8_general_ci' NOT NULL AFTER `name`;
update sys_pages_items as i set pageIdTop=(select pageIdTop from sys_pages as p where p.pageId=i.pageId);
update `sys_pages` set template = null where template='culture.view.tpl.html';
delete FROM `sys_pages_items_properties` where name='position' and (value='' or value is null or value='Array');
delete FROM `sys_pages_properties` where name='position' and (value='' or value is null or value='Array');
delete FROM `sys_pages_items_properties` where name='position' and value like "% %";
delete FROM `sys_pages_properties` where name='position' and value like "% %";

6.import page data from install
7. migrate sys_pages xml pageparams to properties - home(forum,blog),orderitems(galery)
8.clean cache http://awake33.com/cron-cache.total.clean.cron.job.php