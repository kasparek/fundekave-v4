<?php
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
 * 7. externalize config file, generate config file from main config
 * require('image.conf.php'); 
 *  
 */

//INPUT
$fileParam = isset($_GET['img']) ? $_GET['img'] : '';
$sideParam = isset($_GET['side']) ? (int) $_GET['side'] : 0;
$cutParam = isset($_GET['cut']) ? $_GET['cut'] : ''; 

//CONFIGURATION
require('image.conf.php');
$c = new image_conf();
$contentType = $c->contentType;

/**
 * validate parameters
 */
$sideOptionList = explode(',',$c->sideOptions);
$cutOptionsList = explode(',',$c->cutOptions);
if(!in_array($sideParam, $sideOptionList)) {
	if(empty($sideParam)) {
		$sideParam = $c->sideDefault;
	} else {
	 //get closest valid width
	 foreach ($sideOptionList as $fib) {
        $diff[$fib] = (int) abs($sideParam - $fib);
    }
		$fibs = array_flip($diff);
		$sideParam = $fibs[min($diff)];
	}
}

if(!in_array($cutParam, $cutOptionsList)) $cutParam = $c->cutDefault;

$processParams = array('quality'=>90,'width'=>$sideParam,'height'=>$sideParam);
if($cutParam=='crop') $processParams['crop'] = 1;
if($cutParam=='prop') $processParams['proportional'] = 1;
if($c->sharpen===true) $processParams['unsharpMask'] = 1;

/**
 * validate source filename
 */ 
$fileParam = str_replace(array('https://','http://','..','\\'),'',$fileParam);
if($fileParam{0}=='/') $fileParam = substr($fileParam,1);
$sourceImage = $c->sourceBasePath . $fileParam;

/**
 * check source file exists
 */ 
if(!file_exists($sourceImage)) {
	
	//TODO: error loging
	echo 'file not found';
	exit;
	
} else if(is_dir($sourceImage)) {
	
	//TODO: error loging
	echo 'file is dir';
	exit;
	
}

/**
 * $cutParam == 'flush' delete cached images
 */
if($cutParam === 'flush') {
	foreach($sideOptionList as $side) {
		foreach($cutOptionsList as $cut) {
		   $targetImage = $c->targetBasePath.$side.'/'.$cut.'/'.$fileParam;
		   if(file_exists($targetImage)) {
		   	unlink($targetImage);
		   }
		}
	}
	exit;
}  

$imageSize = getimagesize($sourceImage);
$ratio = $sideParam/$imageSize[0];

if($cutParam=='prop') {
	/**
	 * output original image if it is really close to current side size
	 */	 	
	if($ratio<$c->maxNoScaleRatio && $ratio>$c->minNoScaleRatio) {
		//display original
		$targetImage = $sourceImage;
		$contentType = $imageSize['mime'];
	} 
}

/**
 * checking if ration is too big from original
 * stop scaling images to much up
 */
if($ratio > $c->maxScaleUpRatio) {
	 $maxWidth = $imageSize[0] * $c->maxScaleUpRatio;
	 //get closest valid width
	 foreach ($sideOptionList as $fib) {
        $diff[$fib] = (int) abs($maxWidth - $fib);
    }
	$fibs = array_flip($diff);
	$sideParam = $fibs[min($diff)];
}

/**
 * cache file if not exist
*/
if(!isset($targetImage)) {
	
	$targetImage = $c->targetBasePath.$sideParam.'/'.$cutParam.'/'.$fileParam;
   
	if(!file_exists($targetImage)) {
		//require files only when needed
		require($c->libraryBasePath.'libs/FImgProcess.php');
		require($c->libraryBasePath.'libs/FFile.php');
		
		//check if directory exists
		$dirArr = explode('/',$targetImage);
		array_pop($dirArr);
		FFile::makeDir(implode('/',$dirArr));
				
		//process new file
		FImgProcess::process($sourceImage,$targetImage,$processParams);
	}
} 

/**
 * output cached file
*/
if($c->output===true) {
	$fp = fopen($targetImage, 'rb');
	
	header('Content-Type: '.$contentType);
	header("Content-Length: " . filesize($targetImage));
	fpassthru($fp);
	 
	fclose($fp);
}
