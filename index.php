<?php
define('ROOT', $_SERVER['DOCUMENT_ROOT']."/fdk5/");
define('WEBROOT', $_SERVER['DOCUMENT_ROOT']."/fdk5/");
define('INIT_FILENAME', ROOT.'system.init.php');
define('PHPLOG_FILENAME', WEBROOT.'tmp/php.log');
//---application index
if(!isset($nonIndex)) require(ROOT."index.include.php");