<?php
$migr = array(
//array('src'=>ROOT,'tgt'=>'/subdomains/eboinnaija/httpdocs/fdk_'.VERSION),
//array('src'=>ROOT,'tgt'=>'/subdomains/upsidedown/httpdocs/fdk_'.VERSION),
array('src'=>ROOT,'tgt'=>'/subdomains/sail/httpdocs/fdk_'.VERSION)
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
