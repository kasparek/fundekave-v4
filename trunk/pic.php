<?php
/**
 *
 * TOBE DELETED AFTER TESTING IMAGE FUNC
 * 
 */  
//---just display
/**
 *only used in Fajax_event to display temporary saved image in non public folder
 *TODO: - save tmp images in /obr/tmp/
 *  
 * 
 */
if(isset($_GET['f'])) {
	$f = ROOT . $_GET['f'];
	if(file_exists($f)) {
		header('Content-Type: image/jpeg');
		echo file_get_contents($f);
	} else {
		echo 'FIlE NOT FOUND';
	}
	exit;
}

//---diplay with resize
/**
 *only used in Fajax_event to display temporary saved image in non public folder
 *TODO: - use tmp images in /image/170x0/crop/tmp/image.jpg 
 * 
 */
if(isset($_GET['r'])) {
	$f = $_GET['r'];
	$f = ROOT . $f;
	if(file_exists($f)) {
		require(ROOT.LIBSDIR.'FImgProcess.php');
		require(ROOT.LIBSDIR.'FError.php');
		$i = new FImgProcess($f,'',array('crop'=>0,'width'=>170,'height'=>0,'quality'=>90));
		header('Content-Type: image/jpeg');
		$ret = $i->data;
		echo $ret;
	} else {
		echo 'FIlE NOT FOUND';
	}
	exit;
}

/**
 *TODO:
 *  used only in FSystem line 363 to resize images in forum
 * /image/size/prop/remote/md5(salt+base64encoded(url))-base64encoded(url)
 *  
 */  
//--remote
if(isset($_GET['re'])) {

	//save in tmp place
	require(ROOT.LIBSDIR.'FConf.php');
	$tmpDir = FConf::get('settings','remote_tmp');
	
	$URL = base64_decode($_GET['re']);
	
	//$filename = str_replace('http://','',$URL);
	//$filename = str_replace('/','-',$filename);
	$filename = md5($URL);
	
	if(!file_exists($tmpDir.'/cache/'.$filename)) {
	
		if(!file_exists($tmpDir.$filename)) {
			$str = file_get_contents( $URL );
			file_put_contents($tmpDir.$filename,$str);
		}
		if(file_exists($tmpDir.$filename)) {
	
			if(!file_exists($tmpDir.'/cache/'.$filename)) {
				$cacheDir = $tmpDir.'/cache';
				if(!is_dir($cacheDir)) mkdir($cacheDir,0777,true);
				require(ROOT.LIBSDIR.'FImgProcess.php');
				require(ROOT.LIBSDIR.'FError.php');
				$i = new FImgProcess($tmpDir.$filename,$tmpDir.'/cache/'.$filename,array('upsize'=>false,'proportional'=>1,'width'=>300,'height'=>300,'quality'=>90));
			}
			
		}
	}
	
	header('Content-Type: image/jpeg');
	if(!file_exists($tmpDir.'/cache/'.$filename)) {
		echo file_get_contents($tmpDir.$filename);
	} else {
		echo file_get_contents($tmpDir.'/cache/'.$filename);
	}
	exit;
}