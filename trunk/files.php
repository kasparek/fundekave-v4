<?php

require("./local.php");
require(INIT_FILENAME);

function chunkFilename($ident,$iter) {
	$user = FUser::getInstance();
	return  $file = 'chunks/chunk-'.$user->userVO->name.'-'.$ident.'-'.$iter.'.txt';
}

if($user->idkontrol) {

	$data = $_POST['data'];
	$seq = (int)  $_POST['seq'];
	$total = (int)  $_POST['total'];
	$filename = $_POST['filename'];
	if(!empty($data)) {
		file_put_contents(chunkFilename($filename,$seq),$data);
	}

	$allExists = true; 
	for($i=0;$i<$total;$i++) {
		if(!file_exists(chunkFilename($filename,$i)))  {
			$allExists = false;
		}
	}

//---file complete
if($allExists === true) {
	//--concat all files
	$encData = '';
	for($i=0;$i<$total;$i++) {
		$encData .= trim(file_get_contents(chunkFilename($filename,$i)));
	}

	file_put_contents('images/'.$filename.'.jpg',base64_decode( $encData ));
	
	for($i=0;$i<$total;$i++) {
	  unlink(chunkFilename($filename,$i));
	}
}

echo 1;
}