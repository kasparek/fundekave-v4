<?php
require_once('setup.php');

/**
 * check size
 *
 * @param number $sideParam
 * @param array $sideOptionList
 * @param number $default
 * @return number validated size
 *   
 */
date_default_timezone_set('Europe/Prague');

function getClosest($val,$list) {
	if($val==0) return 0;
	if(in_array($val,$list)) return $val;
	//get closest valid value
	foreach ($list as $fib) {
		$d = (int) $val - $fib;
		if($d >= 0) $diff[$fib] = $d;
	}
	$fibs = array_flip($diff);
	return (int) $fibs[min($diff)];
}

//INPUT
$fileParam = isset($_GET['img']) ? $_GET['img'] : '';
$widthParam = 0;
$heightParam = 0;
$quality = 0;

if(isset($_GET['side'])) {
	$getSide = $_GET['side'];
	if(strpos($getSide,'x')!==false) {
		$getSideList = explode('x',$getSide);
		$widthParam = (int) $getSideList[0];
		$heightParam = (int) $getSideList[1];
		if(isset($getSideList[2])) $quality = (int) $getSideList[2];
	} else {
		$widthParam = $heightParam = (int) $getSide;
	}
}
$cutParam = isset($_GET['cut']) ? $_GET['cut'] : '';

//CONFIGURATION
$confDir = file_exists(WEBROOT.'conf') ? 'conf/' : HOST_ROOT.'fdk_conf_'.VERSION.'/';
require_once($confDir.'/image.conf.php');
$contentType = ImageConfig::$contentType;

if(PHPLOG_FILENAME) {
	require_once(LIBS.'libs/FError.php');
	FError::init(PHPLOG_FILENAME);
}

/**
 * validate parameters
 */
$sideOptionList = explode(',',ImageConfig::$sideOptions);
$cutOptionsList = explode(',',ImageConfig::$cutOptions);

if(!in_array($cutParam, $cutOptionsList)) $cutParam = ImageConfig::$cutDefault;
if($cutParam=='flush') $sideOptionList[] = 0;

$widthParam = $widthParam > ImageConfig::$maxSize ? ImageConfig::$maxSize : $widthParam;
$heightParam = $widthParam > ImageConfig::$maxSize ? ImageConfig::$maxSize : $heightParam;

if($widthParam==$heightParam) $widthParam = $heightParam = getClosest($widthParam,$sideOptionList);
else {
  $widthParam = getClosest($widthParam,$sideOptionList);
  $heightParam = getClosest($heightParam,$sideOptionList);
}

$processParams = array('quality'=>ImageConfig::$quality);
if($quality>10) $processParams['quality']=$quality; 
if($cutParam=='crop') $processParams['crop'] = 1;
if($cutParam=='prop') $processParams['proportional'] = 1;

/**
 * validate source filename
 */
if($fileParam{0}=='/') $fileParam = substr($fileParam,1);

$remote = false;
if(strpos($fileParam,'remote')===0) {
	//remote file
	$remoteHash = substr($fileParam,7,32);
	$remoteUrl = substr($fileParam,39);
	if($cutParam!='flush' && $remoteHash != md5(ImageConfig::$salt.$remoteUrl)) {
		FError::write_log("IMAGE - invalid remote file parameters - ".$fileParam);
		echo 'invalid remote file parameters';
		exit;
	}
	$remote = true;
	$sourceImage = base64_decode($remoteUrl);
} else {
	$fileParam = str_replace(array('https://','http://','..','\\'),'',$fileParam);
	$sourceImage = ImageConfig::$sourceBasePath . $fileParam;
}
$targetImage = ImageConfig::$targetBasePath.$widthParam.'x'.$heightParam.'/'.$cutParam.'/'.$fileParam;

if($remote) {
	require_once(LIBS.'libs/FFile.php');
	if(!FFile::remoteFileExists($sourceImage)) {
		echo 'file not found';
		exit;
	}
}

/**
 * check source file exists
 */
if(is_dir($sourceImage) && $cutParam != 'flush') {
	FError::write_log("IMAGE - source is directory and parameter is not FLUSH - ".$sourceImage);
	echo 'File is directory';
	exit;
}

/**
 * $cutParam == 'flush' delete cached images
 */
if($cutParam === 'flush') {
	require_once(LIBS.'libs/FFile.php');
	$ffile = new FFile();
	//get list of all size folders
	$sizeFolderList = $ffile->fileList(WEBROOT.ImageConfig::$targetBasePath);
	foreach($sizeFolderList as $sizeFolder) {
		$cutFolderList = $ffile->fileList(WEBROOT.ImageConfig::$targetBasePath.$sizeFolder.'/');
		foreach($cutFolderList as $cutFolder) {
			$targetImage = WEBROOT.ImageConfig::$targetBasePath.$sizeFolder.'/'.$cutFolder.'/'.$fileParam;
			$ffile->rm_recursive($targetImage);
		}
	}
	echo 'Deleted files:'.$ffile->numModified;
	exit;
}

if(file_exists($targetImage)) {
	header('Location: /'.$targetImage);
}

require_once(LIBS.'libs/FImgProcess.php');
$imageProps = FImgProcess::getimagesize($sourceImage);

if(!isset($imageProps) || $imageProps===false) {
	//missing source image
	FError::write_log("IMAGE - missing source image - ".$sourceImage);
	exit;
}
if(isset($imageProps['source'])) $sourceImage = $imageProps['source'];

if($imageProps[0]==0 || $imageProps[1]==0) {
	FError::write_log("IMAGE - zero image size - ".$sourceImage);
	exit;
}

/**
* image output
**/
$widthParamTmp = $widthParam > $imageProps[0] ? $imageProps[0] : $widthParam;
$heightParamTmp = $heightParam > $imageProps[1] ? $imageProps[1] : $heightParam;
if($widthParam == $heightParam) {
	if($widthParamTmp > $heightParamTmp) {
		$widthParam = $widthParamTmp;
		$heightParam = $widthParamTmp; 
	} else {
		$widthParam = $heightParamTmp;
		$heightParam = $heightParamTmp;
	}
} else {
	$widthParam = $widthParamTmp;
	$heightParam = $heightParamTmp;
}
	
$ratio = $widthParam/$imageProps[0];
if($ratio < 0.3 && ImageConfig::$optimize===true) {
	$processParams['optimize'] = 1;
}

if($cutParam=='prop' && $ratio <= ImageConfig::$maxNoScaleRatio && $ratio > ImageConfig::$minNoScaleRatio) {
	//output original image if it is really close to current side size 
	require_once(LIBS.'libs/FFile.php');
	$file = new FFile();
	$file->copy($sourceImage,$targetImage);
} else if($cutParam=='crop' && $widthParam==$imageProps[0] && $heightParam==$imageProps[1]) {
	//--- croping - output original image if size is exactly same - display original
	require_once(LIBS.'libs/FFile.php');
	$file = new FFile();
	$file->copy($sourceImage,$targetImage);
}
	
/**
 * cache file if not exist
 */
if(!file_exists($targetImage)) {
	$processParams['width'] = $widthParam;
	$processParams['height'] = $heightParam;
	//check if directory exists
	$dirArr = explode('/',$targetImage);
	array_pop($dirArr);
	$targetDir = implode('/',$dirArr);
	if(!is_dir($targetDir)) {
		require_once(LIBS.'libs/FFile.php');
		$file = new FFile();
		$file->makeDir($targetDir);
	}
	//process new file
	FImgProcess::process($sourceImage,$targetImage,$processParams);
}


/**
 * output cached file
 * redirect to physical file
 */
header('Location: /'.$targetImage);
