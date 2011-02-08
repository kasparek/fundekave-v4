<?php
if(!isset($_GET['g'])) exit;

ob_end_clean();
header("Connection: close");
ob_start();
header("Content-Length: 0");
ob_end_flush();
flush();
session_write_close();

$cache = FCache::getInstance('f');
$grps = explode(";",$_GET['g']);
FError::write_log("cron::invalidate - begin - ".$_GET['g']);
foreach($grps as $grp) {
	$inv = explode('/',$grp);
	if(count($inv)>1) $cache->invalidateData($inv[1],$inv[0]);
	else $cache->invalidateGroup($inv[0]);
}
FError::write_log("cron::invalidate - COMPLETE - ".$_GET['g']);

