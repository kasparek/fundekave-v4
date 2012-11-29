<?php
//internet speed and fail testing
$r = rand(1,30);
sleep($r/10);
$r = rand(0,100);
if($r>80) die('died accidentaly');
/**/

$seq = (int)  $_POST['seq'];
$total = (int)  $_POST['total'];

if(isset($_REQUEST['flush'])) {
	$filename = safeFilename($_REQUEST['flush']);
	var_dump($filename);
	$fileList = glob('chunks/'.$filename.'-*.chunk');
	foreach($fileList as $file) {
		var_dump($file);
		if(file_exists($file)) unlink($file);
	}
	die('Flushed');
}

//save chunk
if(!empty($_FILES)) {
	$filename = safeFilename($_POST['Filename']);
	$file = $_FILES['Filedata'];
	$crcReceived = $_POST['crc'] * 1;
	$chunkFilename = 'chunks/'.$filename.'-'.$crcReceived.'-'.$seq.'-'.$total.'.chunk';
	move_uploaded_file($file["tmp_name"], $chunkFilename );
	if(!CRC32Validate($_POST['crc'] * 1, $chunkFilename, 2)) {
		unlink($chunkFilename);
		die('Chunk file CRC32 not matching');
	}
} else {
	//file data not present
	die('Missing file');
}

//merge chunks into file
$fileList = glob('chunks/'.$filename.'-*.chunk');
list($filename,$crc32,$seq,$totalNum) = explode("-",substr($fileList[0],0,-6));
if(empty($fileList)) die('1');
if(count($fileList) != $totalNum) die('1');

$chunkList = array();
foreach($fileList as $chunkFilename) {
	list($filename,$crc32,$seq,$total) = explode("-",substr($chunkFilename,0,-6));
	$chunkList[$seq] = array('file'=>$chunkFilename,'crc'=>$crc32);
}

$targetFile = 'images/'.str_replace("chunks/","",$filename);
if(file_exists($targetFile)) unlink($targetFile);
$handleW = fopen($targetFile, "a");
for($i=0;$i<$totalNum;$i++) {
	$handle = fopen($chunkList[$i]['file'], "rb");
	if(!CRC32Validate($chunkList[$i]['crc'], $chunkList[$i]['file'], 2)) {
		unlink($chunkList[$i]['file']);
		die('Chunk CRC not match');
	}
	fwrite($handleW, fread($handle, filesize($chunkList[$i]['file'])-2));
	fclose($handle);
	unlink($chunkList[$i]['file']);
}
fclose($handleW);

//validate file with CRC32
if (!CRC32Validate($_POST['crcTotal'] * 1, $targetFile)) {
	unlink($targetFile);
	die('Target file CRC32 not matching');
}

//success
die('1');

//-----------------------------------------------------------------------------
//FUNCTIONS
function safeFilename($filename) {
	return str_replace("-","_",$filename);
}

function getFileCRC32($filename,$endOffset=0) {
	$fsize = filesize($filename);
	$handle = fopen($filename, "rb");
	$data = fread($handle, $fsize-$endOffset);
	fclose($handle);
	return sprintf("%u\n", crc32($data));
}

function CRC32Validate($crc32, $filename, $endOffset=0) {
	$crc32 = $crc32 * 1;
	if(empty($crc32)) {
		echo 'ERROR complete crc missing';
		return false;
	}
	$crc32OfFile = getFileCRC32($filename,$endOffset);
	if($crc32OfFile!=$crc32) {
		echo 'ERROR crc32 not matching '.$crc32OfFile.'!='.$crc32;
		return false;
	}
	return true;
}