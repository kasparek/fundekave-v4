<?php
chdir("../");
$nonIndex = true;
require('index.php');
require(INIT_FILENAME);

require_once('FLeftPanel/rh_galerie_rnd.php');
require_once('FLeftPanel/rh_akce_rnd.php');

$cache = FCache::getInstance('f', '86400');

$cache->invalidateData('rh_galerie_rnd','lp');
$cache->invalidateData('rh_akce_rnd','lp');

rh_galerie_rnd::show();
rh_akce_rnd::show();