<?php
require("./local.php");
require(INIT_FILENAME);
if($user->itemVO->itemId > 0){
	header('Content-Type: image/jpeg');
	echo FGalery::getRaw($user->itemVO->itemId);
} else {
	echo 'MISSING PHOTO PARAMETER';
}