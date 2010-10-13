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
 *
 * MAIN PAGE PROCESSING
 *
 **/
require(INIT_FILENAME);

if(isset($_GET['authCheck'])) {
	if($user->idkontrol===true) echo '1';
	else echo '0';
	exit;
}
if(isset($_GET['mod'])) {
	$dir = getcwd().'/'.str_replace('..','',$_GET['mod']);
	$ffile = new FFile();
	$ret = $ffile->chmod_recursive($dir);
	var_dump($ret);
	die();
}

$processMain = true;

/**
 *
 * FILES UPLOAD PROCESSING
 *
 **/
if(!empty($_GET['fuupconfig'])) {
  FFile::printConfigFile( $_GET['fuupconfig'] );
	exit;
}
if(strpos($_SERVER['REQUEST_URI'],"/files/")===0 || strpos($_SERVER['REQUEST_URI'],"/files.php")!==false) {

	if( $user->idkontrol ) {
		if(isset($_GET['f'])) $f = FSystem::safeText($_GET['f']); else $f='';
		
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

		$ffile = new FFile(FConf::get("galery","ftpServer"));
		$ffile->storeChunk($file,$seq);

		//---file complete
		if($ffile->hasAllChunks($filename,$total) === true) {
			//--concat all files
			switch($f) {
				/*case 'uava':
					//TODO: refactor to use tempstore
					$user = FUser::getInstance();
					$dir = FAvatar::profileBasePath();
					$folderSize = FFile::folderSize($dir) / 1024;
					if($folderSize < FConf::get('settings','personal_foto_limit')) {
						$imagePath = $dir . '/' . FFile::safeFilename($filename);
						FFile::makeDir($dir);
					} else {
						FError::add(FLang::$PERSONAL_FOTO_FOLDER_FULL);
						$imagePath = '';
					}
					break;   */
				case 'tempstore':
					//---upload in tmp folder in user folder and save filename in db cache
					$imagePath = FFile::setTempFilename($filename);
					$imagePath = FConf::get("galery","sourceServerBase") . $imagePath;
					$dir = FConf::get("galery","sourceServerBase") . FConf::get("galery","tempStore");
					break;
				default:
					$pageVO = new PageVO($pageId,true);
					$galeryUrl = $pageVO->galeryDir;
					$dir = FConf::get("galery","sourceServerBase").$galeryUrl;
					$imagePath = $dir.'/'.FFile::safeFilename($filename);
			}
			if(!empty($dir)) $ffile->makeDir($dir);
			$ffile->mergeChunks($imagePath, $filename, $total, $isMultipart);

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
				if(!empty($HTTP_RAW_POST_DATA)) {
					$data = $HTTP_RAW_POST_DATA;
				} else {
					FError::add("NO RAW_POST DATA ".$_SERVER['REQUEST_URI']);
				}
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

	FProfiler::write('FAJAX PROCESSED DONE');

	//---process post/get for page
	$data = $_POST;
	if(!empty($_FILES))  $data['__files'] = $_FILES;
	if(!empty($_GET))  $data['__get'] = $_GET;
	FBuildPage::process( $data );

	FProfiler::write('PAGE PROCESS DONE');

	//---shows message that page is locked
	if($user->pageVO)
	if(($user->pageVO->locked == 2 && $user->userVO->userId != $user->pageVO->userIdOwner) || $user->pageVO->locked == 3)  {
		FError::add(FLang::$MESSAGE_PAGE_LOCKED);
		if(!FRules::get($user->userVO->userId,'sadmi',1)) $user->pageAccess = false;
	}

	//TODO: create headers
	//header("Cache-control: max-age=290304000, public");
	//header("Last-Modified: " . date(DATE_ATOM,$dataLastChange));
	//header("Expires: ".gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+31536000));

	//---generate page
	FBuildPage::show( $data );

}
//---profiling
FProfiler::write('PAGE COMPLETE');

//---close resources
session_write_close();
$db = FDBConn::getInstance();
$db->kill();