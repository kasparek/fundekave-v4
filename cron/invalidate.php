<?php
if(!isset($_GET['invalidate'])) exit;
$inv = explode('/',$_GET['invalidate']);
$cache = FCache::getInstance('f');
if(count($inv)>1) $cache->invalidateData($inv[1],$inv[0]);
$cache->invalidateGroup($inv[0]);

