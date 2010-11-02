<?php
$q = 'select count(1),itemId,max(dateCreated) as orderdate from sys_pages_items_hit group by itemId order by orderdate desc';

$dbc = FConf::get('db');
if (!$db = mysql_connect($dbc['hostspec'], $dbc['username'], $dbc['password'])) { echo 'Could not connect to mysql'; exit; }
if (!mysql_select_db($dbc['database'], $db)) { echo 'Could not select database'; exit; }
	
$result = mysql_query($q,$db);
$total = mysql_num_rows($result);
$counter=0;
if($total>0) {
while($row = mysql_fetch_row($result)) {
if($row[0]>5 && $row[1]>0) {
$q = "update sys_pages_items set hit = '".$row[0]."' where itemId='".$row[1]."';\n";
mysql_query($q,$db);
if($row[2]<'2010-09-28 08:00:00') break; //TODO: use date when last updated
$counter++;
}
}
}
echo 'updated:'.$counter;
