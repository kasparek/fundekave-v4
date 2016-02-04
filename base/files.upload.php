<?php
$auth = FText::safeText($_POST['auth']);
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
	$fileList = glob(FConf::get("settings","fuup_chunks_path").base64_encode(FFile::safeFilename($_REQUEST['flush'])).'-*.chunk');
	if(!empty($fileList)) foreach($fileList as $filename) if(file_exists($filename)) unlink($filename);
	FSystem::fin('FuupUploader::chunks flushed');
}

//upload file inputs
$file['name'] = FFile::safeFilename($file['name']);
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
$filenameParts = explode("-",substr($fileList[0],0,-6));
$totalNum = array_pop($filenameParts);
$seq = array_pop($filenameParts);
$crc32 = array_pop($filenameParts);
$filenameBase64 = array_pop($filenameParts);

//FError::write_log('Files.upload.php - filename parts('.count($filenameParts).') - '.$filenameBase64.' - '.$seq.' - '.$totalNum);

if(empty($fileList)) {
	FSystem::fin('1');//,'FuupUploader 3: no chunks ready'); //no chunks ready
}

if(count($fileList) != $totalNum) {
	FSystem::fin('1');//,'FuupUploader 4: not all chunks ready '.count($fileList) .'/'. $totalNum); //not all chunks ready
}

//prepare sequencial list of chunks
$chunkList = array();
foreach($fileList as $chunkFilename) {
	$filenameParts = explode("-",substr($chunkFilename,0,-6));
	$totalNum = array_pop($filenameParts);
	$seq = array_pop($filenameParts);
	$crc32 = array_pop($filenameParts);
	$chunkList[$seq] = array('file'=>$chunkFilename,'crc'=>$crc32);
}

//function parameters
$f='';
if(isset($_POST['f'])) $f = FText::safeText($_POST['f']);
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
		FSystem::fin('Bad chunk CRC','FuupUploader 2:CHUNK CRC FAIL - length('.strlen($chunkData).') '.$crcReceived.'='.$crcTest);
	}
	$data .= $chunkData;
	fclose($handle);
	unlink($chunkList[$i]['file']);
}

if($isMultipart===false) $data = base64_decode($data);

//check CRC of complete file
if(md5($data) != $_POST['crcTotal']) FSystem::fin('Bad target file CRC','FuupUploader 2: Data hash does not match.');

FError::write_log('Files.upload.php - '.$targetFile.' - before file copy - data size: '.count($data));

//write checked file
$result = $ff->file_put_contents($targetFile,$data);

FError::write_log('Files.upload.php - after file copy - result: '.$result);

//SUCCESS
FSystem::fin('1');