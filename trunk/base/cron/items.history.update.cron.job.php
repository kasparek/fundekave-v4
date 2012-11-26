<?php
/*  CURRENTLY NOT IN USE
chdir("../");
$nonIndex = true;
require('index.php');
require(INIT_FILENAME);
$dbc = FConf::get('db');
if (!$db = mysql_connect($dbc['hostspec'], $dbc['username'], $dbc['password'])) { echo 'Could not connect to mysql'; exit; }
if (!mysql_select_db($dbc['database'], $db)) { echo 'Could not select database'; exit; }

$queries = array(
"select date_format(dateCreated,'%Y') as dateCon,itemId,3,count(itemId) as sumVal from sys_pages_items_hit where date_format(dateCreated,'%Y') >= date_format(now(),'%Y') group by dateCon,itemId"

,"select date_format(dateCreated,'%Y') as dateCon,itemId,4,count(itemId) from sys_pages_items_hit where date_format(dateCreated,'%Y') >= date_format(now(),'%Y') and userId>0 group by dateCon,itemId"

,"select date_format(dateCreated,'%Y-%m') as dateCon,itemId,3,count(itemId) from sys_pages_items_hit where date_format(dateCreated,'%Y%m') >= date_format(now(),'%Y%m') group by dateCon,itemId"

,"select date_format(dateCreated,'%Y-%m') as dateCon,itemId,4,count(itemId) from sys_pages_items_hit where date_format(dateCreated,'%Y%m') >= date_format(now(),'%Y%m') and userId>0 group by dateCon,itemId"

,"select date_format(dateCreated,'%Y-%m-%d') as dateCon,itemId,3,count(itemId) from sys_pages_items_hit where date_format(dateCreated,'%Y%m%d') >= date_format(now(),'%Y%m%d') group by dateCon,itemId"

,"select date_format(dateCreated,'%Y-%m-%d') as dateCon,itemId,4,count(itemId) from sys_pages_items_hit where date_format(dateCreated,'%Y%m%d') >= date_format(now(),'%Y%m%d') and userId>0 group by dateCon,itemId"

,"select date_format(dateCreated,'%Y-W%U') as dateCon,itemId,3,count(itemId) from sys_pages_items_hit where date_format(dateCreated,'%Y%U') >= date_format(now(),'%Y%U') group by dateCon,itemId"

,"select date_format(dateCreated,'%Y-W%U') as dateCon,itemId,4,count(itemId) from sys_pages_items_hit where date_format(dateCreated,'%Y%U') >= date_format(now(),'%Y%U') and userId>0 group by dateCon,itemId"

,"select date_format(dateCreated,'%Y') as dateCon,itemId,1,count(itemId) from sys_pages_items_tag where date_format(dateCreated,'%Y') >= date_format(now(),'%Y') and weight > 0 group by dateCon,itemId"

,"select date_format(dateCreated,'%Y-%m') as dateCon,itemId,1,count(itemId) from sys_pages_items_tag where date_format(dateCreated,'%Y%m') >= date_format(now(),'%Y%m') and weight > 0 group by dateCon,itemId"

,"select date_format(dateCreated,'%Y-%m-%d') as dateCon,itemId,1,count(itemId) from sys_pages_items_tag where date_format(dateCreated,'%Y%m%d') >= date_format(now(),'%Y%m%d') and weight > 0 group by dateCon,itemId"

,"select date_format(dateCreated,'%Y-W%U') as dateCon,itemId,1,count(itemId) from sys_pages_items_tag where date_format(dateCreated,'%Y%U') >= date_format(now(),'%Y%U') and weight > 0 group by dateCon,itemId"
);

$page = 3000;
$from = 0;

foreach($queries as $query) {
  $res = 0;
  $totalTotal = 0;
  $from = 0;
  do {
    $dot = $query.' limit '.$from.','.($page+1);
    $result = mysql_query($dot,$db);
    $total = mysql_num_rows($result);
    $totalTotal += $total;
    if($total > 0) {
      //--- update
      while($row = mysql_fetch_row($result)) {
        $ins = "insert into sys_pages_items_history values ('".$row[0]."','".$row[1]."','".$row[2]."','".$row[3]."') on duplicate key update valueSum = '".$row[3]."'";
        $log = $row[0].",".$row[2];
        mysql_unbuffered_query($ins,$db);
        $res++;
      }
      $from += $page;
    }
    mysql_free_result($result);

  } while ($total > $page);
  
  $cron = "insert into sys_cron_log values (now(),'history::".$log."::".$totalTotal.':'.$res."')";
    mysql_query($cron,$db);  
  
}
*/


