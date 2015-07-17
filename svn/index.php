<?php
$silent=true;
chdir('../');
require('index.php');
set_time_limit(3600);
require_once("svn/phpsvnclient.php");
$phpsvnclient = new phpsvnclient('http://fundekave-v4.googlecode.com/svn/');
$phpsvnclient->createOrUpdateWorkingCopy('trunk/base/', ROOT, true);
if(isset($_GET['root'])) {
	$phpsvnclient->createOrUpdateWorkingCopy('trunk/css/', WEBROOT.'css_'.VERSION.'/', true);
	$phpsvnclient->createOrUpdateWorkingCopy('trunk/js/', WEBROOT.'js_'.VERSION.'/', true);
}