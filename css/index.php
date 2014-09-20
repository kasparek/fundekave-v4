<?php
if(isset($_SERVER['HTTP_ACCEPT_ENCODING']))
if(strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip')!==false) {
	ob_start("ob_gzhandler");
	header('Content-Encoding: gzip');
}
//$filename 
//echo file_get_contents();