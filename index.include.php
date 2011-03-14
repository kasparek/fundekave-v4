<?php
require(INIT_FILENAME);
/**
 *CRON JOBS
 **/
if(isset($_GET['cron'])) {
	require_once(ROOT.'cron/'.$_GET['cron'].'.php');
	exit;
}
/**
 * HEADERS PROCESSING
 */
if(isset($_GET['header_handler'])) {
	$c = $_GET['c'];
	if(strpos($c,'.jpg')!==false) $contentType = 'image/jpeg';
	else if(strpos($c,'.gif')!==false) $contentType = 'image/gif';
	else if(strpos($c,'.png')!==false) $contentType = 'image/png';
	else if(strpos($c,'.ico')!==false) $contentType = 'image/x-icon';
	else if(strpos($c,'.css')!==false) $contentType = 'text/css';
	else if(strpos($c,'.js')!==false) $contentType = 'text/javascript';
	else {
		FError::write_log('header_handler - UNSPECIFIED TYPE - '.$c);
		exit;
	}
	$filesize = 0; $dataLastChange = ''; $data = '';
	if($_GET['header_handler']=='css' && strpos($c,'/')===false) {
		//compile global css with skin css
		$filename = 'css/global.css';
		$dataLastChange = filemtime($filename);
		$fp = fopen($filename, 'rb');
		$data .= fread($fp,filesize($filename));
		$data = str_replace('url(','url(css/',$data);
		fclose($fp);
		//skin file
		$filename = 'css/skin/'.str_replace('.css','',$c).'/screen.css';
		if(filemtime($filename) < $dataLastChange) $dataLastChange = filemtime($filename);
		$fp = fopen($filename, 'rb');
		$data .= str_replace('url(','url(css/skin/'.str_replace('.css','',$c).'/',fread($fp,filesize($filename)));
		fclose($fp);
		//remove comments
		$data = preg_replace('/\/\*(.*)\*\/\r\n|\n\r/i', '', $data);
		$data = preg_replace('/\s\s+/', ' ', $data);
	}
	//TODO: odkomentovat az to bude zive
	/*
	 if($contentType == 'text/javascript') {
		$data = file_get_contents($c);
		$data = preg_replace('/\/\*(.*)\*\/\r\n|\n\r/i', '', $data);
		$data = preg_replace('/\s\s+/', ' ', $data);
		}
		*/
	if(empty($data) && !file_exists($c)) {
		FError::write_log('header_handler - FILE NOT EXISTS - '.$c);
		exit;
	}
	header('Content-Type: '.$contentType);
	header("Cache-control: max-age=290304000, public");
	header("Last-Modified: " . date(DATE_ATOM,($dataLastChange==''?filemtime($c):$dataLastChange)));
	header("Expires: ".gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+31536000));
	if(empty($data)) {
		$fp = fopen($c, 'rb');
		fpassthru($fp);
		fclose($fp);
	} else {
		echo $data;
	}
	exit;
}

/**
 *
 * MAIN PAGE PROCESSING
 *
 **/
if(isset($_GET['authCheck'])) {
	$user->kde(); //---check user / load info / load page content / chechk page exist
	if($user->idkontrol===true) echo '1'; else echo '0';
	FSystem::fin();
}
if($user->userVO->userId==1 && isset($_GET['mod'])) {
	$dir = getcwd().'/'.str_replace('..','',$_GET['mod']);
	$ffile = new FFile();
	$ret = $ffile->chmod_recursive($dir);
	var_dump($ret);
	FSystem::fin();
}

/**
 *
 * FILES UPLOAD PROCESSING
 *
 **/
if(!empty($_GET['fuupconfig'])) {
	$user->kde(); //---check user / load info / load page content / chechk page exist
	FFile::printConfigFile( $_GET['fuupconfig'] );
	exit;
}
if(strpos($_SERVER['REQUEST_URI'],"/files/")===0 || strpos($_SERVER['REQUEST_URI'],"/files.php")!==false) {
	FError::write_log('index::fileManagement START');
	$user->kde(); //---check user / load info / load page content / chechk page exist
	if( $user->idkontrol ) {
		if(isset($_GET['f'])) $f = FSystem::safeText($_GET['f']); else $f='';
		FError::write_log('index::fileManagement OPERATION: '.$f);
		//PARAMS
		$isMultipart = false;
		if(!empty($_FILES)) {
			$file = $_FILES['Filedata'];
			$isMultipart = true;
		} else if(isset($_POST['filename'])) {
			$file['name'] = $_POST['filename'];
			$data['data'] = $_POST['data'];
		}
		if(empty($file)) {
			echo '0';
			FSystem::fin();
		}
		if(empty($_POST['crc'])) {
			echo '0';
			FSystem::fin();
		}
		$crcReceived = $_POST['crc'];

		$seq = (int) $_POST['seq'];
		$total = (int) $_POST['total'];
		$filename = $file['name'];
		$ffile = new FFile(FConf::get("galery","ftpServer"));
		$crcStored = $ffile->storeChunk($file,$seq);
		if($crcStored!=$crcReceived) {
			$ffile->deleteChunk($file,$seq);
			echo '0';
			FSystem::fin();
		}
		
		//---file complete
		if($ffile->hasAllChunks($filename,$total) === true) {
			$filename = FSystem::safeFilename($filename);
			FError::write_log('index::fileManagement ALL CHUNKS READY: '.$filename);
			//--concat all files
			switch($f) {
				case 'tempstore':
					//---upload in tmp folder in user folder and save filename in db cache
					$imagePath = FFile::setTempFilename($filename);
					$imagePath = FConf::get("galery","sourceServerBase") . $imagePath;
					$dirArr=explode('/',$imagePath);
					array_pop($dirArr);
					$dir = implode('/',$dirArr);
					break;
				default:
					$dir = FConf::get("galery","sourceServerBase").$user->pageVO->get('galeryDir');
					$imagePath = $dir.'/'.FFile::safeFilename($filename);
			}
			if(!empty($dir)) $ffile->makeDir($dir);
			$ffile->mergeChunks($imagePath, $filename, $total, $isMultipart);
			FError::write_log('index::fileManagement ALL CHUNKS MERGED');
		}
		echo 1;
	}
  FError::write_log('index::fileManagement COMPLETE');
	FSystem::fin();
}

if(isset($_GET['test'])) {
	/*
	 $filename = 'IMG_1308.JPG';
	 $isMultipart=true;
	 $total = 14;

	 $dir = FConf::get("galery","sourceServerBase").$user->pageVO->get('galeryDir');
	 $imagePath = $dir.'/'.FFile::safeFilename($filename);
	 $ffile = new FFile(FConf::get("galery","ftpServer"));
	 if(!empty($dir)) $ffile->makeDir($dir);
	 $ffile->mergeChunks($imagePath, $filename, $total, $isMultipart);
	 */
	echo 'aa';
	FSystem::fin();
}

/**
 *
 * BUILD RSS
 *
 **/
if(strpos($_SERVER['REQUEST_URI'],"/rss")!==false || strpos($_SERVER['REQUEST_URI'],"/frss.php")!==false) {
	$user->kde();
	FRSS::process($_GET);
	FRSS::build($_GET);
	FSystem::fin();
}

//---process ajax requests - or alternative POST requests
if(isset($_REQUEST['m'])) {
	if(isset($_REQUEST['d'])) $data = $_REQUEST['d']; //simple link handling
	if(strpos($_REQUEST['m'],'-x')!==false) {
		if(empty($data)) {
			if(!empty($HTTP_RAW_POST_DATA)) $data = $HTTP_RAW_POST_DATA;
			else FError::add("NO RAW_POST DATA ".$_SERVER['REQUEST_URI']);
		} elseif(strpos($data,'<')===false) $data = urldecode(base64_decode($data));
	}
	if(empty($data)) $data = $_POST; //handling post if not ajax
	$options = array();
	if(!empty($_FILES)) $options['data']['__files'] = $_FILES;
	if(!empty($_GET)) $options['data']['__get'] = $_GET;
	FAjax::prepare($_REQUEST['m'], $data, $options);
	$fajax = FAjax::getInstance();
	if(!empty($fajax->data['i'])) {
		$itemVO = new ItemVO($fajax->data['i'],false);
		if($itemVO->load()) $user->itemVO = $itemVO; else $itemVO=null;
	}
	$user->kde(); //---check user / load info / load page content / chechk page exist
	if($itemVO) $user->itemVO->prepare(); //need to be done after user initialization
	FAjax::process($_REQUEST['m'], $data, $options);
}

/**
 * BUILDING GENERIC PAGE
 * */
 //---process post/get for page - not ajaz processing
$data = $_POST;
if(!empty($_FILES)) $data['__files'] = $_FILES;
if(!empty($_GET)) $data['__get'] = $_GET;
$data = FAjax::preprocessPost($data);	 	

$user->kde(); //---check user / load info / load page content / chechk page exist
if($itemVO) $user->itemVO->prepare(); //need to be done after user initialization
FBuildPage::process( $data );
FProfiler::write('PAGE PROCESS DONE');

//increment hit for items
if($user->itemVO)
if(empty($user->pageParam)) {
		if(!$user->idkontrol || $itemVO->userId != $user->userVO->userId) {
			$itemVO->hit();
		}
	}
	
//---shows message that page is locked
if($user->pageVO) {
	if(($user->pageVO->locked == 2 && $user->userVO->userId != $user->pageVO->userIdOwner) || $user->pageVO->locked == 3) {
		FError::add(FLang::$MESSAGE_PAGE_LOCKED);
		if(!FRules::get($user->userVO->userId,'sadmi',1)) $user->pageAccess = false;
	}
}

//---generate page
$html = FBuildPage::show( $data );


header("Content-Type: text/html; charset=".FConf::get('internationalization','charset'));
if(!isset($_GET['nooutput'])) echo $html;
else echo strlen($html).'Bytes produced';
	
//---profiling
FProfiler::write('PAGE COMPLETE');
//---close connections
FSystem::fin();
