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

//INPUT
$fileParam = isset($_GET['img']) ? $_GET['img'] : '';
$sideParam = isset($_GET['side']) ? (int) $_GET['side'] : 0;
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
  
$processParams = array('quality'=>$c->quality);
if($cutParam=='crop') $processParams['crop'] = 1;
if($cutParam=='prop') $processParams['proportional'] = 1;
if($c->sharpen===true) $processParams['unsharpMask'] = 1;

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
	
} else {

	require_once($c->libraryBasePath.'libs/FImgProcess.php');
	$imageProps = FImgProcess::getimagesize($sourceImage);
	
	if( $imageProps===false ) {
		
		//TODO: error loging
		echo 'file not found';
		exit;
		
	}
	
	if($imageProps['source']) {
	   $sourceImage = $imageProps['source'];
	}
	
} 

/**
 * $cutParam == 'flush' delete cached images
 */
if($cutParam === 'flush') {
	if($sideParam!=0) {
		$sideOptionList = array($sideParam);
	}
	foreach($sideOptionList as $side) {
		foreach($cutOptionsList as $cut) {
			if($fileParam{strlen($fileParam)}=='/') $fileParam = substr($fileParam,0,-1); //if fileparam is folder with slash at the end
				if($remote===true) {
					//TODO:flush remote
				} else { 
		   	$targetImage = $c->targetBasePath.$side.'/'.$cut.'/'.$fileParam;
		   }
		   if(file_exists($targetImage)) {
		   	if(is_dir($targetImage)) {
		   		require_once($c->libraryBasePath.'libs/FFile.php');
		   		FFile::rm_recursive($targetImage);
				} else {
		   		unlink($targetImage);
		   	}
		   }
		}
	}
	exit;
}  


$ratio = $sideParam/$imageProps[0];

if($cutParam=='prop') {
	/**
	 * output original image if it is really close to current side size
	 */	 	
	if($ratio<$c->maxNoScaleRatio && $ratio>$c->minNoScaleRatio) {
		//display original
		$targetImage = $sourceImage;
		$contentType = $imageProps['mime'];
	} 
}

/**
 * checking if ration is too big from original
 * stop scaling images to much up
 */
if($ratio > $c->maxScaleUpRatio) {
	 $maxWidth = $imageProps[0] * $c->maxScaleUpRatio;
	 //get closest valid width
	 foreach ($sideOptionList as $fib) {
	 	if($maxWidth - $fib > 0) {
        	$diff[$fib] = (int) $maxWidth - $fib;
	 	}
    }
	$fibs = array_flip($diff);
	$sideParam = $fibs[min($diff)];
}

/**
 * cache file if not exist
*/
if(!isset($targetImage)) {
	if($remote===false) {
	$targetImage = $c->targetBasePath.$sideParam.'/'.$cutParam.'/'.$fileParam;
	} else {
	$targetImage = $c->targetBasePath.$sideParam.'/'.$cutParam.'/remote/'.md5($fileParam);
	}
   
	if(!file_exists($targetImage)) {
		//require files only when needed
		require_once($c->libraryBasePath.'libs/FFile.php');
		
		$processParams['width'] = $sideParam;
		$processParams['height'] = $sideParam;
		
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