<?php
$auth = FSystem::safeText($_POST['auth']);
//TODO: instead of using session use auth to verify / might speed up response

$user->kde(); //---check user / load info / load page content / chechk page exist
if(!$user->idkontrol) {
	FError::write_log('files.upload - not logged user trying to upload file');
	FSystem::fin('end-user fail');
}

//external parameters
$isMultipart = false;
if(!empty($_FILES)) {
	$file = $_FILES['Filedata'];
	$isMultipart = true;
} else if(isset($_POST['filename'])) {
	$file['name'] = $_POST['filename'];
	$data['data'] = $_POST['data'];
} else if(isset($_POST['flush'])) {
	$file['name'] = $_POST['flush'];
}

//verify input data
if(empty($file)) FSystem::fin('missing file data');


//delete old chunks
if(isset($_REQUEST['flush'])) {
	$fileList = glob(FConf::get("settings","fuup_chunks_path").base64_encode(FSystem::safeFilename($_REQUEST['flush'])).'-*.chunk');
	if(!empty($fileList)) foreach($fileList as $filename) if(file_exists($filename)) unlink($filename);
	FSystem::fin('FuupUploader::chunks flushed');
}

//upload file inputs
$file['name'] = FSystem::safeFilename($file['name']);
$total = (int) $_POST['total'];
$seq = (int) $_POST['seq'];

//chunk crc check
$crcReceived = $_POST['crc'];
if(empty($crcReceived)) FSystem::fin('missing chunk CRC');

//setup chunk names
$chunkDir = FConf::get("settings","fuup_chunks_path");
$chunkFilename = base64_encode($file['name']).'-'.$crcReceived.'-'.$seq.'-'.$total.'.chunk';

//chunks directory
if(!is_dir($chunkDir)) {
	$ff=new FFile();
	$ff->makeDir($chunkDir);
}

//write chunk
if(!empty($file['tmp_name'])) {
	move_uploaded_file($file["tmp_name"], $chunkDir.$chunkFilename );
	FError::write_log('FuupUploader::chunk saved '.$seq.'/'.$total);
} else if(!empty($file['data'])) {
	file_put_contents($chunkDir.$chunkFilename,$file["data"]);
	$chunkData = $file["data"];
}
//verify chunk
if(!file_exists($chunkDir.$chunkFilename)) {
	FSystem::fin('missing chunk','FuupUploader 1:CHUNK MISSING '.$chunkFilename);
}
$handle = fopen($chunkDir.$chunkFilename, "rb");
$chunkData = fread($handle, filesize($chunkDir.$chunkFilename)-2);
fclose($handle);
$crcTest = md5($chunkData);
if($crcTest != $crcReceived) {
	unlink($chunkDir.$chunkFilename);
	FSystem::fin('bad chunk CRC','FuupUploader 1:CHUNK CRC FAIL - length('.strlen($chunkData).') '.$crcReceived.'='.$crcTest);
}

//try to get all chunks
$fileList = glob($chunkDir.base64_encode($file['name']).'-*.chunk');
list($filenameBase64,$crc32,$seq,$totalNum) = explode("-",substr($fileList[0],0,-6));
if(empty($fileList)) FSystem::fin('1'); //no chunks ready
if(count($fileList) != $totalNum) FSystem::fin('1'); //not all chunks ready

//prepare sequencial list of chunks
$chunkList = array();
foreach($fileList as $chunkFilename) {
	list($filename,$crc32,$seq,$total) = explode("-",substr($chunkFilename,0,-6));
	$chunkList[$seq] = array('file'=>$chunkFilename,'crc'=>$crc32);
}

//function parameters
$f='';
if(isset($_POST['f'])) $f = FSystem::safeText($_POST['f']);
switch($f) {
	case 'tempstore':
		$targetFile = FConf::get("galery","sourceServerBase") . FFile::setTempFilename($file['name']);
		break;
	default:
		$targetFile = FConf::get("galery","sourceServerBase").$user->pageVO->get('galeryDir').'/'.FFile::safeFilename($file['name']);
}

//check that directory exists
$ff = new FFile(FConf::get("galery","ftpServer"));
$ff->makeDir(substr($targetFile,0,strrpos($targetFile,'/')));
//merge chunks
$data='';
for($i=0;$i<$totalNum;$i++) {
	$handle = fopen($chunkList[$i]['file'], "rb");
	$chunkData = fread($handle, filesize($chunkList[$i]['file'])-2);
	$crcTest = md5($chunkData);
	if($chunkList[$i]['crc'] != $crcTest) {
		unlink($chunkList[$i]['file']);
		FSystem::fin('bad chunk CRC','FuupUploader 2:CHUNK CRC FAIL - length('.strlen($chunkData).') '.$crcReceived.'='.$crcTest);
	}
	$data .= $chunkData;
	fclose($handle);
	unlink($chunkList[$i]['file']);
}
if($isMultipart===false) $data = base64_decode($data);

//check CRC of complete file
if(md5($data) != $_POST['crcTotal']) FSystem::fin('bar target file CRC');

//write checked file
$ff->file_put_contents($targetFile,$data);

//SUCCESS
FSystem::fin('1');