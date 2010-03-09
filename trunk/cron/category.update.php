<?php
$queries = array("update sys_pages_category as c join sys_pages as p on p.pageId=c.typeId set c.num = (select count(1) from sys_pages where categoryId=c.categoryId) where p.typeId='top'"
		  ,"update sys_pages_category as c join sys_pages as p on p.pageId=c.typeId set c.num = (select count(1) from sys_pages_items where categoryId=c.categoryId) where p.typeId!='top' and p.typeId!='event'"
		  ,"update sys_pages_category as c join sys_pages as p on p.pageId=c.typeId set c.num = (select count(1) from sys_pages_items where (dateStart >= date_format(NOW(),'%Y-%m-%d') or (dateEnd is not null and dateEnd >= date_format(NOW(),'%Y-%m-%d'))) and categoryId=c.categoryId) where p.typeId='event'"
		);
		
chdir("../");
$nonIndex = true;
require('index.php');
require(INIT_FILENAME);
$dbc = FConf::get('db');
if (!$db = mysql_connect($dbc['hostspec'], $dbc['username'], $dbc['password'])) { echo 'Could not connect to mysql'; exit; }
if (!mysql_select_db($dbc['database'], $db)) { echo 'Could not select database'; exit; }

foreach($queries as $query) {
	
	mysql_query($query,$db);
  
}