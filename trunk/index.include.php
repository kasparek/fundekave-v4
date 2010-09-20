<?php
/**
 *
 * HEADERS PROCESSING
 *
 */
if(isset($_GET['header_handler'])) {
	date_default_timezone_set("Europe/Prague");
	$c = $_GET['c'];
	if(strpos($c,'.jpg')!==false) {
		$contentType = 'image/jpeg';
	} else if(strpos($c,'.gif')!==false) {
		$contentType = 'image/gif';
	} else if(strpos($c,'.png')!==false) {
		$contentType = 'image/x-png';
	} else if(strpos($c,'.ico')!==false) {
		$contentType = 'image/x-icon';
	} else if(strpos($c,'.css')!==false) {
		$contentType = 'text/css';
	} else {
		header('Content-type: text/javascript');
	}

	if($_GET['header_handler']=='css' && strpos($c,'/')===false) {
		//compile global css with skin css
		$data = '';
		$filename = 'css/global.css';
		$dataLastChange = filemtime($filename);
		$fp = fopen($filename, 'rb');
		$filesize = filesize($filename);
		$data .= fread($fp,$filesize);
		$data = str_replace('url(','url(css/',$data);
		fclose($fp);
		
		$filename = 'css/skin/'.str_replace('.css','',$c).'/screen.css';
		if(filemtime($filename) < $dataLastChange) $dataLastChange = filemtime($filename);
		$filesize += filesize($filename);
		$fp = fopen($filename, 'rb');
		$data .= str_replace('url(','url(css/skin/'.str_replace('.css','',$c).'/',fread($fp,filesize($filename)));
		fclose($fp);
		
		$fp=null;
		                                
		$data = preg_replace('/\/\*(.*)\*\/\r\n|\n\r/i', '', $data);
		
	} else {
		$fp = fopen($c, 'rb');
		$dataLastChange = filemtime($c);
		$filesize = filesize($c);
	}
	if(isset($contentType)) header('Content-Type: '.$contentType);
	header("Content-Length: " . $filesize);
	header("Cache-control: max-age=290304000, public");
	header("Last-Modified: " . date(DATE_ATOM,$dataLastChange));
	header("Expires: ".gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+31536000));
	ob_start("ob_gzhandler");
	if(!empty($fp)) {
		fpassthru($fp);
		fclose($fp);
	} else if(!empty($data)) {
		echo $data;
	}
	exit;
}

/**
 * CROSSDOMAIN.XML
 * */
if(isset($_GET['cross'])) {
	header('Content-Type: text/xml');
	echo file_get_contents(ROOT.'template/crossdomain.xml');
	exit;
}

/**
 *
 * MAIN PAGE PROCESSING
 *
 **/
require(INIT_FILENAME);

$processMain = true;

/**
 *
 * FILES UPLOAD PROCESSING
 *
 **/
if(strpos($_SERVER['REQUEST_URI'],"/files/")===0 || strpos($_SERVER['REQUEST_URI'],"/files.php")!==false) {

	if( $user->idkontrol ) {
		if(isset($_GET['f'])) $f = $_GET['f']; else $f='';
		if($f=='cnf') {
			FFile::printConfigFile( $_GET['c'], $pageVO );
			exit;
		}

		//PARAMS
		$isMultipart = false;
		$seq = (int)  $_POST['seq'];
		$total = (int)  $_POST['total'];
		if(!empty($_FILES)) {
			$file = $_FILES['Filedata'];
			$isMultipart = true;
		} else if(isset($_POST['filename'])) {
			$file['name'] = $_POST['filename'];
			$data['data'] = $_POST['data'];
		}
		$filename = $file['name'];

		FFile::storeChunk($file,$seq);

		//---file complete
		if(FFile::hasAllChunks($filename,$total) === true) {
			//--concat all files
			switch($f) {
				case 'uava':
					//TODO: only upload to profile, no avatar set. check filename
					$user = FUser::getInstance();
					$dir = FAvatar::profileBasePath();
					$imagePath = $dir . '/' . FFile::safeFilename($filename);
					FFile::makeDir($dir);
					$folderSize = FFile::folderSize($dir) / 1024;
					if($folderSize < FConf::get('settings','personal_foto_limit')) {
						//OK to save file
					} else {
						FError::addError(FLang::$PERSONAL_FOTO_FOLDER_FULL);
						$imagePath = '';
					}
					break;
				case 'pava':
					$imageName = 'pageAvatar-'.$pageId.'.jpg';
					$imagePath = ROOT_PAGE_AVATAR.$imageName;
					//update db
					$pageVO = new PageVO($pageId,true);
					$pageVO->saveOnlyChanged = true;
					$pageVO->set('pageIco',$imageName);
					$pageVO->save();
					break;
				case 'futip':
					$user = FUser::getInstance();
					//---upload in tmp folder in user folder and save filename in db cache
					$dir = FConf::get("settings","upload_tmp") . $user->userVO->name;
					$filename = FFile::safeFilename($filename);
					$imagePath = $dir . '/' . $filename;
					FFile::makeDir($dir);
					$cache = FCache::getInstance('d');
					$cache->setData($filename,'event','user-'.$user->userVO->userId);
					break;
				default:
					$pageVO = new PageVO($pageId,true);
					$galeryUrl = $pageVO->galeryDir;
					FFile::makeDir(FConf::get("galery","sourceServerBase").$galeryUrl);
					$imagePath = FConf::get("galery","sourceServerBase").$galeryUrl.'/'.FFile::safeFilename($filename);
			}

			FFile::mergeChunks($imagePath, $filename, $total, $isMultipart);

		}
		echo 1;
	}

	$processMain = false;
}

/**
 *
 * BUILD RSS
 *
 **/
if(strpos($_SERVER['REQUEST_URI'],"/rss")!==false || strpos($_SERVER['REQUEST_URI'],"/frss.php")!==false) {
	FRSS::process($_GET);
	FRSS::build($_GET);
	$processMain = false;
}

if($processMain===true) {

	//---process ajax requests - or alternative POST requests
	if(isset($_REQUEST['m'])) {
		if(isset($_REQUEST['d'])) {
			$data = $_REQUEST['d'];
		}
		if(strpos($_REQUEST['m'],'-x')!==false) {
			if(empty($data)) {
				$data = $HTTP_RAW_POST_DATA;
			} else {
				if(strpos($data,'<')===false) {
					$data = base64_decode($data);
					$data = urldecode($data);
				}
			}
		}
		if(empty($data)) {
			$data = $_POST;
		}
		FAjax::process( $_REQUEST['m'], $data );
	}

	FProfiler::profile('FAJAX PROCESSED DONE');

	//---process post/get for page
	$data = $_POST;
	if(!empty($_FILES))  $data['__files'] = $_FILES;
	if(!empty($_GET))  $data['__get'] = $_GET;
	FBuildPage::process( $data );

	FProfiler::profile('PAGE PROCESSED DONE');

	if($user->pageAccess == true) {
		//---tag toolbar set up
		if($user->idkontrol === true) {
			FItemsToolbar::setTagToolbar();
		}
	}

	FProfiler::profile('PAGE STAT/TOOLBAR');

	//---shows message that page is locked
	if($user->pageVO)
	if(($user->pageVO->locked == 2 && $user->userVO->userId != $user->pageVO->userIdOwner) || $user->pageVO->locked == 3)  {
		FError::addError(FLang::$MESSAGE_PAGE_LOCKED);
		if(!FRules::get($user->userVO->userId,'sadmi',1)) $user->pageAccess = false;
	}

	FProfiler::profile('PAGE BEFORE SHOW');

	//TODO: create headers
	//header("Cache-control: max-age=290304000, public");
	//header("Last-Modified: " . date(DATE_ATOM,$dataLastChange));
	//header("Expires: ".gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+31536000));

	//---generate page
	FBuildPage::show();

}
//---profiling
FProfiler::profile('PAGE COMPLETE');
FProfiler::profileLog();
FDBTool::profileLog();

//---close resources
session_write_close();
$db = FDBConn::getInstance();
$db->kill();