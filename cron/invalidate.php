<?php
if(!isset($_GET['g'])) exit;
$grps = explode(";",$_GET['g']);
foreach($grps as $grp) {
	$inv = explode('/',$grp);
	$cache = FCache::getInstance('f');
	if(count($inv)>1) $cache->invalidateData($inv[1],$inv[0]);
	$cache->invalidateGroup($inv[0]);
}

