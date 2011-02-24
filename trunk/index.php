<?php
$host = $_SERVER['HTTP_HOST'];
$hostArr = explode('.',$host);
$host = $hostArr[0]=='www' ? $hostArr[1] : $hostArr[0];

$root = $_SERVER['DOCUMENT_ROOT']."/fdk_v5/";
if($host=='awake33') $root = $_SERVER['DOCUMENT_ROOT']."/fdk_v6/"; 

define('ROOT', $root);
define('WEBROOT', $_SERVER['DOCUMENT_ROOT'].'/');
define('INIT_FILENAME', ROOT.'system.init.php');
define('PHPLOG_FILENAME', WEBROOT.'tmp/php.log');
//---application index
if(!isset($nonIndex)) require(ROOT."index.include.php");