<?php
if(isset($_GET['both'])) {
	$_GET['mod'] = $_GET['both'];
	$_GET['delete'] = $_GET['both'];
}

var_dump(getcwd());
echo '<hr>';
//update folder permissions
if($user->userVO->userId==1 && isset($_GET['mod'])) {
	echo 'Changing permissions';
	$dir = getcwd().'/tmp/'.str_replace('..','',$_GET['mod']);
	$ffile = new FFile();
	$ret = $ffile->chmod_recursive($dir);
	var_dump($ret);
echo '<hr>';	
}

//
if($user->userVO->userId==1 && isset($_GET['delete'])) {
	echo 'Deleting';
	$dir = getcwd().'/tmp/'.str_replace('..','',$_GET['delete']);
	$ffile = new FFile();
	$ret = $ffile->rm_recursive($dir);
	var_dump($ret);
echo '<hr>';	
}

FSystem::fin();