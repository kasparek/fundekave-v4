<?php
//update folder permissions
if($user->userVO->userId==1 && isset($_GET['mod'])) {
	$dir = getcwd().'/'.str_replace('..','',$_GET['mod']);
	$ffile = new FFile();
	$ret = $ffile->chmod_recursive($dir);
	var_dump($ret);
	FSystem::fin();
}