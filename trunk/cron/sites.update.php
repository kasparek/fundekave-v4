<?php
$migr = array(
ROOT=>'/subdomains/test/httpdocs/fdk_v5'
,ROOT=>'/subdomains/eboinnaija/httpdocs/fdk_v5'
);

$ff = new FFile(FConf::get('settings','ftp'));
foreach( $migr as $source=>$target) {								
$ff->rm_recursive($target);
$ff->makeDir($target);
$ff->sourceFolder = $source;
$ff->targetFolder = $target;
$ff->replicateToFtp();
}