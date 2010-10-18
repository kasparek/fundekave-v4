<?php
/**
 * check size
 *
 * @param number $sideParam
 * @param array $sideOptionList
 * @param number $default
 * @return number validated size
 */
function validateSideParam( $sideParam, $sideOptionList, $default ) {

	if(!in_array($sideParam, $sideOptionList)) {
		if(empty($sideParam)) {
			return $default;
		} else {
			$diff = array();
			//get closest valid width
			foreach ($sideOptionList as $fib) {
				$diff[$fib] = (int) abs($sideParam - $fib);
			}
			$fibs = array_flip($diff);
			return $fibs[min($diff)];
		}
	} else {
		return $sideParam;
	}
}
/**
 * checking if ration is too big from original
 * stop scaling images to much up
 */
function getMaxScaleUp($sideParam, $sideOriginal, $sideOptionList, $maxScaleUpRatio) {
	if(empty($sideOriginal)) return $sideParam;
	$diff = array();
	$ratio = $sideParam/$sideOriginal;
	if($ratio > $maxScaleUpRatio) {
		$maxSize = $sideOriginal * $maxScaleUpRatio;
		//get closest valid width
		foreach ($sideOptionList as $fib) {
			if($maxSize - $fib >= 0) {
				$diff[$fib] = (int) $maxSize - $fib;
			}
		}
		if($diff) {
			$fibs = array_flip($diff);
			return $fibs[min($diff)];
		}
	}
	return $sideParam;
}

/**
 *
 *
 * TODO:
 * -0. if side not specified use default
 * -1. if size is close to original show original
 * -2. upsize only to 130%
 * 3. if changed delete gens
 * 4. generate in batch
 * -5. validate input - file exist, max size, cut param
 * 6. ERROR loging into file
 *
 * test different res 200x300
 * test 170x0 resize only by one side
 * somehow overide - not use - list of resolution, be able to use any and flush???
 * $c->salt
 * do remote images /image/size/prop/remote/md5(salt+base64encoded(url))/base64encoded(url)
 *
 * do page images /obr/page/pageId/... - for event flyers or any other page images - blog?
 * do page / item images /obr/page/pageId/ItemId/
 * do user images /obr/username/profile/...
 *
 * use getimagesize_remote if getimagesize fails - esception handling - use it as file exists
 *
 * 7. externalize config file, generate config file from main config
 * require('image.conf.php');
 *
 */
date_default_timezone_set('Europe/Prague');

//INPUT
$fileParam = isset($_GET['img']) ? $_GET['img'] : '';
$widthParam = 0;
$heightParam = 0;
$customSize = false;
if(isset($_GET['side'])) {
	$getSide = $_GET['side'];
	if(strpos($getSide,'x')!==false) {
		$getSideList = explode('x',$getSide);
		$widthParam = (int) $getSideList[0];
		$heightParam = (int) $getSideList[1];
		$customSize = true;
	} else {
		$widthParam = $heightParam = (int) $getSide;
	}
}
$cutParam = isset($_GET['cut']) ? $_GET['cut'] : '';

//CONFIGURATION
require('image.conf.php');
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

if($customSize) {
	$widthParam = $widthParam > $c->maxSize ? $c->maxSize : $widthParam;
	$heightParam = $widthParam > $c->maxSize ? $c->maxSize : $heightParam;
} else {
	$widthParam = validateSideParam($widthParam, $sideOptionList, $c->sideDefault);
	$heightParam = validateSideParam($heightParam, $sideOptionList, $c->sideDefault);
}

$processParams = array('quality'=>$c->quality);
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
	if($widthParam!=0) {
		$sideOptionList = array($widthParam);
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

if(isset($imageProps)) {
	
	$widthParamTmp = getMaxScaleUp($widthParam,$imageProps[0],$sideOptionList,$c->maxScaleUpRatio);
	$heightParamTmp = getMaxScaleUp($heightParam,$imageProps[1],$sideOptionList,$c->maxScaleUpRatio);
	
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
	header("Content-Length: " . filesize($targetImage));
	header("Cache-control: max-age=290304000, public");
	header("Last-Modified: " . date(DATE_ATOM,filemtime($remote===true?$targetImage:$sourceImage)));
	header("Expires: ".gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+31536000));

	fpassthru($fp);

	fclose($fp);
}