<?php
set_time_limit(3000);
require('ftpTransport.php');
/*
$ftp = new FTPTransport('/home/www/fundekave.net/subdomeny/www/obr/','/httpdocs/obr/',true);
$ftp->connect();
$ftp->copy();
$ftp->close();

$ftp = new FTPTransport('/home/www/fundekave.net/subdomeny/www/kaucuk/','/httpdocs/kaucuk/',true);
$ftp->connect();
$ftp->copy();
$ftp->close();

$ftp = new FTPTransport('/home/www/fundekave.net/subdomeny/www/kostka/','/httpdocs/kostka/',true);
$ftp->connect();
$ftp->copy();
$ftp->close();

$ftp = new FTPTransport('/home/www/fundekave.net/subdomeny/www/ruscha/','/httpdocs/ruscha/',true);
$ftp->connect();
$ftp->copy();
$ftp->close();

$ftp = new FTPTransport('/home/www/fundekave.net/subdomeny/www/wajbrou/','/httpdocs/wajbrou/',true);
$ftp->connect();
$ftp->copy();
$ftp->close();

$ftp = new FTPTransport('/home/www/fundekave.net/subdomeny/www/o_O/','/httpdocs/o_O/',true);
$ftp->connect();
$ftp->copy();
$ftp->close();

$ftp = new FTPTransport('/home/www/fundekave.net/subdomeny/www/data/events/','/httpdocs/obr/page/event/',true);
$ftp->connect();
$ftp->copy();
$ftp->close();

$ftp = new FTPTransport('/home/www/fundekave.net/subdomeny/www/data/aico/','/httpdocs/obr/page/avatar/',true);
$ftp->connect();
$ftp->copy();
$ftp->close();

//migrating profile pictures
$root = '/home/www/fundekave.net/subdomeny/www/data/idfoto';
$arr = scandir($root);
foreach($arr as $dir) {
if($dir!='..' && $dir!='.' && is_dir($root.'/'.$dir)) {
//echo $root.'/'.$dir.' => /httpdocs/obr/'.strtolower($dir).'/profile<br>';
$ftp = new FTPTransport($root.'/'.$dir,'/httpdocs/obr/'.strtolower($dir).'/profile',true);
$ftp->connect();
$ftp->createFolder('/httpdocs/obr/'.strtolower($dir));
$ftp->createFolder('/httpdocs/obr/'.strtolower($dir).'/profile');
$ftp->copy();
$ftp->close();
}
}
*/

//migrating avatars
$root = '/home/www/fundekave.net/subdomeny/www/data/idfoto';
$ftp = new FTPTransport();
$ftp->connect();
mysql_connect("", "", "");
mysql_select_db("");
$q = "select userId,name,avatar from sys_users where deleted=0 and avatar is not null and avatar!='' and avatar!='http://fundekave.net/data/skin/funde/img/nobody.jpg'";
$res = mysql_query($q);
while ($row = mysql_fetch_array($res, MYSQL_NUM)) {
$ftp->createFolder('/httpdocs/obr/'.strtolower($row[1]));
$ftp->createFolder('/httpdocs/obr/'.strtolower($row[1]).'/profile');
//echo $root.'/'.$row[2].' => /httpdocs/obr/'.strtolower($row[1]).'/profile/'.$row[2].'<br>';
$ftp->copyfile($root.'/'.$row[2],'/httpdocs/obr/'.strtolower($row[1]).'/profile/'.$row[2]);
}
$ftp->close();

