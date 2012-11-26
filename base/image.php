<?php
/**
 * check size
 *
 * @param number $sideParam
 * @param array $sideOptionList
 * @param number $default
 * @return number validated size
 * 
 *TODO: handle errors - error log
 *TODO: file not found - show image
 *TODO: flush remote  
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
require('config/image.conf.php');
$c = new image_conf();
$contentType = $c->contentType;

if(!empty($c->log)) {
	require_once($c->libraryBasePath.'libs/FError.php');
	FError::init($c->log);
}

/**
 * validate parameters
 */
$sideOptionList = explode(',',$c->sideOptions);
$cutOptionsList = explode(',',$c->cutOptions);

if(!in_array($cutParam, $cutOptionsList)) $cutParam = $c->cutDefault;
if($cutParam=='flush') $sideOptionList[] = 0;

$widthParam = $widthParam > $c->maxSize ? $c->maxSize : $widthParam;
$heightParam = $widthParam > $c->maxSize ? $c->maxSize : $heightParam;

if($widthParam==$heightParam) $widthParam = $heightParam = getClosest($widthParam,$sideOptionList);
else {
  $widthParam = getClosest($widthParam,$sideOptionList);
  $heightParam = getClosest($heightParam,$sideOptionList);
}

$processParams = array('quality'=>$c->quality);
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
	$remotePartList = explode('/',$fileParam);
	if($remotePartList[1] != md5($c->salt.$remotePartList[2])) {
		echo 'invalid remote file parameters';
		exit;
	}
	$remote = true;
	$fileParam = base64_decode($remotePartList[2]);
	$sourceImage = $fileParam;

	$targetImage = $c->targetBasePath.$widthParam.'x'.$heightParam.'/'.$cutParam.'/remote/'.md5($fileParam);
	if(!file_exists($targetImage)) $targetImage = null;

} else {
	$fileParam = str_replace(array('https://','http://','..','\\'),'',$fileParam);
	$sourceImage = $c->sourceBasePath . $fileParam;
}

/**
 * check source file exists
 */
if(is_dir($sourceImage) && $cutParam != 'flush') {
	//TODO: error loging
	echo 'file is dir';
	exit;

} else if($cutParam != 'flush') {
	if(empty($targetImage)) {
		require_once($c->libraryBasePath.'libs/FImgProcess.php');
		$imageProps = FImgProcess::getimagesize($sourceImage);
		if( $imageProps===false ) {
			//TODO: error loging
			echo 'file not found';
			exit;
		}
		if(isset($imageProps['source'])) {
			$sourceImage = $imageProps['source'];
		}
	}
}

/**
 * $cutParam == 'flush' delete cached images
 */
if($cutParam === 'flush') {
	if(!in_array($widthParam,$sideOptionList)) {
		$sideOptionList[] = $widthParam;
	}
	if(!in_array($heightParam,$sideOptionList)) {
		$sideOptionList[] = $heightParam;
	}
	require_once($c->libraryBasePath.'libs/FFile.php');
	$ffile = new FFile();
	foreach($sideOptionList as $width) {
		foreach($sideOptionList as $height) {
			foreach($cutOptionsList as $cut) {
				if($fileParam{(strlen($fileParam)-1)}=='/') {
					$fileParam = substr($fileParam,0,-1); //if fileparam is folder with slash at the end
				}
				if($remote===true) {
					//TODO:flush remote
				} else {
					$targetImage = $c->targetBasePath.$width.'x'.$height.'/'.$cut.'/'.$fileParam;
				}
				if(file_exists($targetImage)) {
					if(is_dir($targetImage)) {
						$ffile->rm_recursive($targetImage);
					} else {
						$ffile->unlink($targetImage);
					}
				}
			}
		}
	}
	exit;
}

if(isset($imageProps))
if($imageProps[0]>0 && $imageProps[1]>0) {
	$widthParamTmp = $widthParam>$imageProps[0]?$imageProps[0]:$widthParam;
	$heightParamTmp = $heightParam>$imageProps[1]?$imageProps[1]:$heightParam;
	if($widthParam==$heightParam) {
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

	if($ratio < 0.3 && $c->optimize===true) {
		$processParams['optimize'] = 1;
	}

	if($cutParam=='prop') {
		/**
		 * output original image if it is really close to current side size
		 */
		if($ratio <= $c->maxNoScaleRatio && $ratio > $c->minNoScaleRatio) {
			//display original
			$targetImage = $sourceImage;
			$contentType = $imageProps['mime'];
		}
	} else if($cutParam=='crop') {
		/**
		 *output original image if size is exactly same
		 */
		if($widthParam==$imageProps[0] && $heightParam==$imageProps[1]) {
			//display original
			$targetImage = $sourceImage;
			$contentType = $imageProps['mime'];
		}
	}
	
	/**
	 * cache file if not exist
	 */
	if(!isset($targetImage)) {
		if($remote===false) {
			$targetImage = $c->targetBasePath.$widthParam.'x'.$heightParam.'/'.$cutParam.'/'.$fileParam;
		} else {
			$targetImage = $c->targetBasePath.$widthParam.'x'.$heightParam.'/'.$cutParam.'/remote/'.md5($fileParam);
		}

		if(!file_exists($targetImage)) {
			//require files only when needed
			require_once($c->libraryBasePath.'libs/FFile.php');

			$processParams['width'] = $widthParam;
			$processParams['height'] = $heightParam;

			//check if directory exists
			$dirArr = explode('/',$targetImage);
			array_pop($dirArr);
			$file = new FFile();
			$file->makeDir(implode('/',$dirArr));

			//process new file
			FImgProcess::process($sourceImage,$targetImage,$processParams);
		}
	}
}
/**
 * output cached file
 */
if($c->output===true) {
	$fp = fopen($targetImage, 'rb');
	header('Content-Type: '.$contentType);
	header("Content-Length: ".filesize($targetImage));
	header('Content-Transfer-Encoding: binary');
	header("Cache-control: max-age=290304000, public");
	header("Last-Modified: " . date(DATE_ATOM,filemtime($remote===true?$targetImage:$sourceImage)));
	header("Expires: ".gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+31536000));
	fpassthru($fp);
	fclose($fp);
}