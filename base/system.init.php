<?php
if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
    if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
        ob_start("ob_gzhandler");
        header('Content-Encoding: gzip');
    }
}

$_REQUEST = array_merge($_GET, $_POST);
//---host name
if (empty($host)) {
    $host    = $_SERVER['HTTP_HOST'];
    $hostArr = explode('.', $host);
    $host    = $hostArr[0] == 'www' ? $hostArr[1] : $hostArr[0];
    if ($host == 'localhost') {
        $host = 'fundekave';
    }

}
//--------------------------------------------------------------class autoloader
function class_autoloader($c)
{
    if (strpos($c, 'page_') !== false) {
        $c = LIBS . 'page/' . $c;
    } elseif (strpos($c, 'fajax_') !== false) {
        $c = LIBS . 'fajax/' . ucfirst(str_replace('fajax_', '', $c));
    } elseif (strpos($c, 'sidebar_') !== false) {
        $c = LIBS . 'sidebar/' . $c;
    } elseif (strpos($c, 'VO') !== false) {
        $c = LIBS . 'model/' . $c;
    } else {
        $c = LIBS . 'libs/' . $c;
    }
    $filename = $c . '.php';
    if(file_exists($filename)) {
	    try {
	        include_once $filename;
	    } catch (Exception $e) {;}
	}
}
spl_autoload_register("class_autoloader");
//--------------------------------------------------------error handler
FError::init(PHPLOG_FILENAME);
//--------------------------------------------------------config + constant init
$confDir = file_exists(WEBROOT . 'conf') ? 'conf/' : HOST_ROOT . 'fdk_conf_' . VERSION . '/';
FConf::getInstance(array($confDir . 'global.conf.ini', $confDir . $host . '.conf.ini', $confDir . 'localhost.conf.ini'), $host);
require_once $confDir . '/image.conf.php';
require_once 'vendor/autoload.php';

use \Rollbar\Rollbar;
Rollbar::init(
    array(
        'access_token' => '67f8fac727fb4042be2f53db8e23b506',
        'environment'  => 'production',
    )
);

date_default_timezone_set(FConf::get('internationalization', 'timezone'));
setlocale(LC_CTYPE, FConf::get('internationalization', 'setlocale'));
setlocale(LC_COLLATE, FConf::get('internationalization', 'setlocale'));
if (FConf::get('internationalization', 'lang')) {
    require FConf::get('internationalization', 'lang');
}

//---session settings
ini_set("session.gc_maxlifetime", SESSIONLIFETIME);
ini_set('session.gc_probability', 1);
if (!is_dir(ROOT_SESSION)) {
    $ff = new FFile();
    $ff->makeDir(ROOT_SESSION);
}
ini_set('session.save_path', ROOT_SESSION);
session_start();
FProfiler::write('START - session started');

//startup user
$user = FUser::getInstance();
$user->init();
FProfiler::write('USER - initialized - "' . ($user->userVO ? $user->userVO->userId : 'anonym') . '"');
if (isset($_GET['auth'])) {
    $user->setRemoteAuthToken(FText::safeText($_GET['auth']));
}

//initial pageid retrieve
if (isset($_REQUEST['k'])) {
    $pageId = $_REQUEST['k'];
}

//---backward compatibility
if (isset($_GET['kam'])) {
    $add = '';if ($_GET['kam'] > 33000) {
        $add = 'f';
        $kam = $_GET['kam'] - 33000;} elseif ($_GET['kam'] > 23000 && $_GET['kam'] < 33000) {
        $add = 'g';
        $kam = $_GET['kam'] - 23000;}
    if (!empty($kam)) {
        $els = '';for ($x = 0; $x < (4 - strlen($kam)); $x++) {
            $els .= 'l';
        }

        $pageId = $add . $els . $kam;
    }
}
//check for item
$itemId = 0;
if (!empty($_REQUEST["i"])) {
    $itemId = (int) $_REQUEST['i'];
} elseif (isset($_REQUEST['nid'])) {
    $itemId = (int) $_REQUEST['nid'];
}
//---backwards compatibility
if ($itemId > 0) {
    $user->itemVO = FactoryVO::get('ItemVO', $itemId, true, false);
    if ($user->itemVO) {
        if (empty($pageId)) {
            $pageId = $user->itemVO->pageId;
        }

        if ($user->itemVO->itemIdTop > 0) {
            $user->itemVO = FactoryVO::get('ItemVO', $user->itemVO->itemIdTop, true);
        }

    }
}
//check category
if (isset($_REQUEST['c'])) {
    $user->categoryVO = FactoryVO::get('CategoryVO', (int) $_REQUEST['c'], true);
}
if (isset($_REQUEST['date'])) {
    $user->inDate($_REQUEST['date']);
}

//recheck pageId
if (empty($pageId)) {
    $pageId = HOME_PAGE;
}

$pageId = FSystem::processK($pageId);
//setup userVO
$user->pageId = $pageId;
FProfiler::write('SYSTEM INIT - page params - page=' . $pageId . ($user->itemVO ? ' item=' . $user->itemVO->itemId : ''));
if (isset($_REQUEST['who'])) {
    $user->setWhoIs($_REQUEST['who']);
}

//---logout action
if (isset($_GET['logout']) && $user->userVO->userId > 0) {
    FUser::logout($user->userVO->userId);
    FError::add(FLang::$MESSAGE_LOGOUT_OK, 1);
    FHTTP::redirect(FSystem::getUri('', '', ''));
}
