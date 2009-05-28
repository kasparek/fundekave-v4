<?php
if(isset($_REQUEST["fid"])) $_REQUEST["i"] = (int) $_REQUEST["fid"];
if($_REQUEST['i'] > 0){
	require("./local.php");
  require(INIT_FILENAME);
  $galery = new FGalery();
  header('Content-Type: image/jpeg');
	echo $galery->getRaw($user->currentItemId);
} else {
  echo 'NO FOTO SPECIFIED';
}