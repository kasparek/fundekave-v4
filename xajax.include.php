<?php
//FIXME - do user check
$nonUserInit = true;

require(ROOT.LIBSDIR."fXajax.class.php");
$xajax = fXajax::init();

$tmp = explode("_",$_POST['xjxfun']);
$xFile = ROOT.'./xajax_my/'.$tmp[0].'.xajax.php';

if(file_exists($xFile)) require($xFile);
else exit('Ajax file not found');

require(INIT_FILENAME);

$xajax->processRequest();