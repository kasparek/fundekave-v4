<?php
if(isset($_REQUEST["fid"])) $_REQUEST["i"] = (int) $_REQUEST["fid"];
if($_REQUEST['i'] > 0){
	require("./local.php");
  require(INIT_FILENAME);
  $galery = new fGalery();
	echo $galery->getPopup($user->currentItemId);
} else {
  echo 'NO FOTO SPECIFIED';
}