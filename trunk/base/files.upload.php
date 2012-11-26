<?php
$user->kde(); //---check user / load info / load page content / chechk page exist
if(!$user->idkontrol) {
	FError::write_log('files.upload - not logged user trying to upload file');
	FSystem::fin();
}

//PARAMS
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

if(empty($file)) {
	echo '0';
	//FError::write_log('index::fileManagement: MISSING FILE');
	FSystem::fin();
}
$file['name'] = FSystem::safeFilename($file['name']);

$total = (int) $_POST['total'];
$ffile = new FFile(FConf::get("galery","ftpServer"));
if(isset($_POST['flush'])) {
	$file = array('name'=>FSystem::safeFilename($_POST['flush']));
	for($i=0;$i<$total;$i++) $ffile->deleteChunk($file,$i);
	echo 'FuupUploader::chunks flushed';
	FSystem::fin();
}
$seq = (int) $_POST['seq'];
//chunk crc check
if(empty($_POST['crc'])) {
	echo '0';
	//FError::write_log('index::fileManagement: MISSING CRC');
	FSystem::fin();
}
$crcReceived = $_POST['crc'] * 1;
$crcStored = $ffile->storeChunk($file,$seq);
if($crcStored!=$crcReceived) {
	$ffile->deleteChunk($file,$seq);
	//FError::write_log('index::fileManagement: CRC DOES NOT MATCH');
	echo '0';
	FSystem::fin();
}

//---file complete
if($ffile->hasAllChunks($file['name'],$total) === true) {
	//FError::write_log('index::fileManagement ALL CHUNKS READY: '.$file['name']);
	//--concat all files
	$f='';
	if(isset($_POST['f'])) $f = FSystem::safeText($_POST['f']);
	switch($f) {
		case 'tempstore':
			//---upload in tmp folder in user folder and save filename in db cache
			$imagePath = FFile::setTempFilename($file['name']);
			$imagePath = FConf::get("galery","sourceServerBase") . $imagePath;
			$dirArr=explode('/',$imagePath);
			array_pop($dirArr);
			$dir = implode('/',$dirArr);
			break;
		default:
			$dir = FConf::get("galery","sourceServerBase").$user->pageVO->get('galeryDir');
			$imagePath = $dir.'/'.FFile::safeFilename($file['name']);
	}
	if(!empty($dir)) $ffile->makeDir($dir);
	$ffile->mergeChunks($imagePath, $file['name'], $total, $isMultipart);
	//FError::write_log('index::fileManagement ALL CHUNKS MERGED');
}

echo 1;

//FError::write_log('index::fileManagement COMPLETE');
FSystem::fin();