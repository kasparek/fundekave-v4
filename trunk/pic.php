<?php
if(isset($_REQUEST["fid"])) $_REQUEST["i"] = (int) $_REQUEST["fid"];
if($_REQUEST['i'] > 0){
	require("./local.php");
	require(INIT_FILENAME);
	header('Content-Type: image/jpeg');
	echo FGalery::getRaw($user->itemVO->itemId);
} else {
	echo 'MISSING PHOTO PARAMETER';
}