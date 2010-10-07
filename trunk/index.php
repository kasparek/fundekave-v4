<?php
//local settings for current server
define('ROOT', "D:/_web/fdk5/");
define('WEBROOT', "D:/_web/fdk5/");
define('CONFIG_FILENAME', ROOT.'config/fdk3.conf.ini');
define('INIT_FILENAME', ROOT.'system.init.php');
define('PHPLOG_FILENAME', WEBROOT.'tmp/php.log');
//---application index
if(!isset($nonIndex)) require(ROOT."index.include.php");