<?php
$migr = array(
array('src'=>ROOT,'tgt'=>'/subdomains/test/httpdocs/fdk_v6')
,array('src'=>ROOT,'tgt'=>'/subdomains/eboinnaija/httpdocs/fdk_v6')
,array('src'=>ROOT,'tgt'=>'/subdomains/upsidedown/httpdocs/fdk_v6')
);

$ff = new FFile(FConf::get('settings','ftp'));
foreach( $migr as $site) {
$source = $site['src'];								
$target = $site['tgt']; 
$ff->rm_recursive($target);
$ff->makeDir($target);
$ff->sourceFolder = $source;
$ff->targetFolder = $target;
$ff->replicateToFtp();
}
