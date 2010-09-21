<?php
//local settings for current server
define('PHPLOG', "D:/_web/fdk5/tmp/php.log");
define('ROOT', "D:/_web/fdk5/");
define('CONFIGDIR', 'config/');
define('LIBSDIR', 'libs/');
define('CONFIG_FILENAME', ROOT.CONFIGDIR.'fdk3.conf.ini');
define('INIT_FILENAME', ROOT.'system.init.php');
//---application index
if(!isset($nonIndex)) {
require(ROOT."index.include.php");
}