<?php
require("./local.php");
//---just display
if(isset($_GET['f'])) {
	$f = ROOT . $_GET['f']; 
	if(file_exists($f)) {
		header('Content-Type: image/jpeg');
		echo file_get_contents($f);
	} else {
		echo 'FIlE NOT FOUND';
	}
	exit;
}

//---diplay with resize
if(isset($_GET['r'])) {
	$f = ROOT . $_GET['r'];
	if(file_exists($f)) {
		require(ROOT.LIBSDIR.'FImgProcess.php');
		require(ROOT.LIBSDIR.'FError.php');
		$i = new FImgProcess($f,'',array('crop'=>0,'width'=>170,'height'=>0,'quality'=>90));
		header('Content-Type: image/jpeg');
		$ret = $i->data; 
		echo $ret;
	} else {
		echo 'FIlE NOT FOUND';
	}
	exit;
}

//---galery item	 
require(INIT_FILENAME);
if($user->itemVO->itemId > 0){
	header('Content-Type: image/jpeg');
	echo FGalery::getRaw($user->itemVO->itemId);
} else {
	echo 'MISSING PHOTO PARAMETER';
}