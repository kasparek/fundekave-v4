<?php
//internet speed and fail testing
sleep(0.5);
$r = rand(0,100);
if($r>80) {
	echo 'died';
	die();
}
/**/

$seq = (int)  $_POST['seq'];
$total = (int)  $_POST['total'];
$filename = $_POST['Filename'];

if(isset($_POST['flush'])) {
	$filename = $_POST['flush'];
	for($i=0;$i<$total;$i++) {
		$chunkFilename = 'chunks/chunk-'.$filename.'-'.$i.'.txt';
		if(file_exists($chunkFilename))  {
			unlink($chunkFilename);
		}
	}
	echo 'flushed';
	exit;
}

//save chunk
if(!empty($_FILES)) {
	$file = $_FILES['Filedata'];
	$chunkFilename = 'chunks/chunk-'.$filename.'-'.$seq.'.txt';
	move_uploaded_file($file["tmp_name"], $chunkFilename );
	//validating chunk with CRC32
	$crcReceived = $_POST['crc'] * 1;
	//validate file with CRC32
	if (!CRC32Validate($_POST['crc'] * 1, $chunkFilename, 2)) {
		echo 'Chunk file CRC32 not matching';
		exit;
	}
} else {
	//file data not present
	echo 'Missing file';
}

//check all chunks saved
$allExists = true; 
for($i=0;$i<$total;$i++) {
	if(!file_exists('chunks/chunk-'.$filename.'-'.$i.'.txt'))  {
		$allExists = false;
	}
}

//merge chunks into file
$targetFile = 'images/'.$filename.'.jpg';
if($allExists === true) {	
	$handleW = fopen($targetFile, "a");
	for($i=0;$i<$total;$i++) {
		$file = 'chunks/chunk-'.$filename.'-'.$i.'.txt';
		$handle = fopen($file, "rb");
		fwrite($handleW, fread($handle, filesize($file)-2));
		fclose($handle);
		unlink($file);
	}
	fclose($handleW);
	
	//validate file with CRC32
	if (!CRC32Validate($_POST['crcTotal'] * 1, $targetFile)) {
		echo 'Target file CRC32 not matching';
		exit;
	}
	
}

echo 1;

//-----------------------------------------------------------------------------
//FUNCTIONS
function getFileCRC32($filename,$endOffset=0) {
	$fsize = filesize($filename);
	$handle = fopen($filename, "rb");
	$data = fread($handle, $fsize-$endOffset);
	fclose($handle);
	return sprintf("%u\n", crc32($data));
}

function CRC32Validate($crc32, $filename, $endOffset=0) {
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