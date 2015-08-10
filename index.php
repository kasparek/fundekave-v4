<?php
if(isset($_GET['host'])) $host = $_GET['host'];
define('VERSION', 'v02');
define('WEBROOT', getcwd().'/');
define('ROOT', getcwd().'/'.(file_exists(WEBROOT.'base') ? 'base/' : 'fdk_'.VERSION.'/'));
define('PHPLOG_FILENAME', '/tmp/php.log');
if(!isset($silent)) require_once(ROOT."index.include.php");
