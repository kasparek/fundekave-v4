<?php
chdir("../");
$nonIndex = true;
$nonInit = true;
require('index.php');
require(INIT_FILENAME);

$migr = array(
ROOT=>'/subdomains/test/httpdocs/fdk_v5'
,WEBROOT.'css'=>'/subdomains/test/httpdocs/css'
);

$ff = new FFile(FConf::get('settings','ftp'));
foreach( $migr as $source=>$target) {								
$ff->rm_recursive($target);
$ff->makeDir($target);
$ff->sourceFolder = $source;
$ff->targetFolder = $target;
$ff->replicateToFtp();
}
