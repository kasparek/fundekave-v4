<?php
if (!empty($_FILES)) {
	chdir('../');
	require("./local.php");
	require(INIT_FILENAME);
	$targetPath = ROOT . ROOT_UPLOADIFY;
	FSystem::makeDir($targetPath);

	$tempFile = $_FILES['Filedata']['tmp_name'];
	$uId = uniqid();
	$targetFile =  $targetPath . $uId;

	$toCache = array(
	   'filenameTmp'=> $targetFile,
	   'filenameOriginal'=> $_FILES['Filedata']['name']
	);

	$cache = FCache::getInstance('d');
	$user = FUser::getInstance();
	$cache->setData($toCache,$_GET['u'].'-'.$_GET['m'],'uploadify');

	move_uploaded_file($tempFile,$targetFile);
}
echo "1";