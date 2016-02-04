<?php
if(isset($_GET['host'])) $host = $_GET['host'];
define('VERSION', 'v02');
define('WEBROOT', getcwd().'/');
define('HOST_ROOT', '/var/www/vhosts/awake33.com/');
define('LIBS', file_exists(WEBROOT.'base') ? 'base/' : HOST_ROOT.'fdk_libs_'.VERSION.'/');
define('PHPLOG_FILENAME', '/tmp/php.log');
if(!isset($silent)) require_once(LIBS."index.include.php");