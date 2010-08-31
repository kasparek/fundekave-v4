<?php
/**
 * 
 * 
 * TODO:
 * 0. if side not specified use default
 * 1. if size is close to original show original
 * 2. upsize only to 130%
 * 3. if changed delete gens
 * 4. generate in batch
 * 5. validate input - file exist, max size, cut param
 * 
 */
$validParams = array(
	'side'=>array('default'=>600,'options'=>array(400,600,800,1000,1200,1400)),
	'cut'=>array('default'=>'prop','options'=>array('prop','crop'))
);

$sideParam = isset($_GET['side']) ? (int) $_GET['side'] : 0;
if(!in_array($sideParam, $validParams['side']['options'])) $sideParam = $validParams['side']['default'];

$cutParam = isset($_GET['cut']) ? $_GET['cut'] : '';
if(!in_array($cutParam, $validParams['cut']['options'])) $cutParam = $validParams['cut']['default'];


require('libs/FImgProcess.php');
require('libs/FFile.php');

//IMG_0265.JPG

//$side = $_SESSION['client']['width'];


$processParams = array(
'quality'=>90,'width'=>$sideParam,'height'=>$sideParam
//,'reflection'=>1
//,'unsharpMask'=>1
);

//$processParams['width'] = round($processParams['width']/100)*100;
//$processParams['height'] = round($processParams['height']/100)*100; 


if($cutParam=='crop') $processParams['crop'] = 1;
if($cutParam=='prop') $processParams['proportional'] = 1;

$fileParam = isset($_GET['img']) ? $_GET['img'] : '';
$originalImage = 'obr/'.$fileParam;
if(!file_exists($originalImage)) {
	echo 'file not found';
	exit;
} else if(is_dir($originalImage)) {
	echo 'file is dir';
	exit;
} else if($cutParam=='prop') {
	//check size
	$imageSize = getimagesize($originalImage);
	$ratio = $sideParam/$imageSize[0];
	if($ratio<1.1 && $ratio>0.9) {
		//display original
		header('Content-type: '.$imageSize['mime']);
		$fp = fopen($originalImage, 'rb');
		header("Content-Length: " . filesize($originalImage));
		fpassthru($fp); 
		fclose($fp);
		exit;
	} 
}


header('Content-Type: image/jpeg');

$filename = $_GET['img'];
$cacheFilename = 'image/'.$sideParam.'/'.$cutParam.'/'.$filename;
if(!file_exists($cacheFilename)) {
	$dirArr = explode('/',$cacheFilename);
	array_pop($dirArr);
	$cacheFilenameDir = implode('/',$dirArr);
	FFile::makeDir($cacheFilenameDir);
	$fProcess = new FImgProcess('obr/'.$filename,null,$processParams);
	$fp = fopen($cacheFilename, 'w');
	fwrite($fp,$fProcess->data);
	fclose($fp);

	echo $fProcess->data;
} else {
	$fp = fopen($cacheFilename, 'rb');
	header("Content-Length: " . filesize($cacheFilename));
	fpassthru($fp); 
	fclose($fp);
}