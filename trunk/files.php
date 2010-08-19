<?php
function chunkFilename($ident,$iter) {
	$user = FUser::getInstance();
	if(isset($_GET['f'])) $f = '-'.$_GET['f']; else $f='';
	return  $file = FConf::get("settings","fuup_chunks_path").'chunk-'.$user->userVO->name.$f.'-'.$ident.'-'.$iter.'.txt';
}

if( $user->idkontrol ) {
	if(isset($_GET['f'])) $f = $_GET['f']; else $f='';
	if($f=='cnf') {
		$c = $_GET['c'];
		switch($c) {
			case 'uava':
				$tpl = FSystem::tpl('fuup.avatar.config.xml');
				break;
			case 'pava':
				$tpl = FSystem::tpl('fuup.pageAvatar.config.xml');
				break;
			case 'futip':
				$tpl = FSystem::tpl('fuup.event.config.xml');
				break;
			default:
				$tpl = FSystem::tpl('fuup.galery.config.xml');
		}
		$tpl->setVariable('URL','files.php?k='.$pageId.(($c)?('&f='.$c):('')));
		$tpl->show();
		exit;
	}
	
	//---SAVE FILE CHUNK
	$isMultipart = false;
	$seq = (int)  $_POST['seq'];
	$total = (int)  $_POST['total'];
	FFile::makeDir(FConf::get("settings","fuup_chunks_path"));
	if(!empty($_FILES)) {
		$file = $_FILES['Filedata'];
		$filename = $file["name"];
		move_uploaded_file($file["tmp_name"], chunkFilename($filename,$seq) );
		$isMultipart = true;
	} else {
		$filename = $_POST['filename'];
		$data = $_POST['data'];
		if(!empty($data)) {
  		file_put_contents(chunkFilename($filename,$seq),$data);
		}
	}
	
	//---CHECK FILE CHUNKS
	$allExists = true;
	for($i=0;$i<$total;$i++) {
		if(!file_exists(chunkFilename($filename,$i)))  {
			$allExists = false;
		}
	}

	//---file complete
	if($allExists === true) {
		//--concat all files
		switch($f) {
			case 'uava':
				$user = FUser::getInstance();
				$imageName = FAvatar::createName($filename);
				$dir = ROOT_AVATAR . $user->userVO->name;
				$imagePath = $dir . '/' . $imageName;
				FFile::makeDir($dir);
				//delete old
				if($user->userVO->avatar) {
					if(file_exists(ROOT_AVATAR.$user->userVO->avatar)) {
						unlink(ROOT_AVATAR.$user->userVO->avatar);
					}
				}
				$folderSize = FFile::folderSize($dir) / 1024;
				if($folderSize < FConf::get('settings','personal_foto_limit')) {
					//OK to save file
				} else {
					FError::addError(FLang::$PERSONAL_FOTO_FOLDER_FULL);
					$imagePath = '';
				}
				break;
			case 'pava':
				$imageName = 'pageAvatar-'.$pageId.'.jpg';
				$imagePath = ROOT_PAGE_AVATAR.$imageName;
				//update db
				$pageVO = new PageVO($pageId,true);
				$pageVO->saveOnlyChanged = true;
				$pageVO->set('pageIco',$imageName);
				$pageVO->save();
				break;
			case 'futip':
				$user = FUser::getInstance();
				//---upload in tmp folder in user folder and save filename in db cache
				$dir = FConf::get("settings","upload_tmp") . $user->userVO->name;
				$imagePath = $dir . '/' .  $filename;
				FFile::makeDir($dir);
				$cache = FCache::getInstance('d');
				$cache->setData($filename,'event','user-'.$user->userVO->userId);
				break;
			default:
				$pageVO = new PageVO($pageId,true);
				$galeryUrl = $pageVO->galeryDir;
				$imageName = strtolower($filename);
				$ext = FFile::fileExt($imageName);
				$imageName =str_replace('.'.$ext,'',$imageName);
				FFile::makeDir(ROOT_GALERY.$galeryUrl);
				$imagePath = ROOT_GALERY.$galeryUrl.'/'.FSystem::safeText($imageName).'.'.$ext;
		}

		//---PUT CHUNKS TOGETHER
		if(!empty($imagePath)) {
			if(file_exists($imagePath)) unlink($imagePath);
			$handleW = fopen($imagePath, "w");
		  for($i=0;$i<$total;$i++) {
				$fileChunk = chunkFilename($filename,$i);
				$handle = fopen($fileChunk, "rb");
				fwrite($handleW, fread($handle, filesize($fileChunk)-($isMultipart===true?2:0)));
				fclose($handle);
				unlink($fileChunk);
			}
		  fclose($handleW);
		}
		
		//---BASE64 DECODE IF NOT TRANSFERED VIE FILES / MULTIPART 
		if($isMultipart===false) {
			file_put_contents($imagePath, base64_decode( file_get_contents($imagePath) ));
		}
		
	}
	echo 1;
}