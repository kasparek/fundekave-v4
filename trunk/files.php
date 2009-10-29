<?php
require("./local.php");
require(INIT_FILENAME);

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
				$tpl = new FTemplateIT('fuup.avatar.config.xml');
				break;
			case 'pava':
				$tpl = new FTemplateIT('fuup.pageAvatar.config.xml');
				break;
			default:
				$tpl = new FTemplateIT('fuup.galery.config.xml');
		}
		$tpl->setVariable('URL','files.php?k='.$pageId.(($c)?('&f='.$c):('')));
		$tpl->show();
		exit;
	}

	$data = $_POST['data'];
	$seq = (int)  $_POST['seq'];
	$total = (int)  $_POST['total'];
	$filename = $_POST['filename'];
	if(!empty($data)) {
		FFile::makeDir(FConf::get("settings","fuup_chunks_path"));
		file_put_contents(chunkFilename($filename,$seq),$data);
	}

	$allExists = true; 
	for($i=0;$i<$total;$i++) {
		if(!file_exists(chunkFilename($filename,$i)))  {
			$allExists = false;
		}
	}

//---file complete
if($allExists === true) {

	//--concat all files
	$encData = '';
	for($i=0;$i<$total;$i++) {
		$encData .= trim(file_get_contents(chunkFilename($filename,$i)));
	}
	switch($f) {
		case 'uava':
			$user = FUser::getInstance();
			$imageName = FAvatar::createName($filename);
			$imagePath = ROOT.ROOT_WEB.WEB_REL_AVATAR.$imageName;
			//delete old
			if($user->userVO->avatar) {
				if(file_exists(ROOT.ROOT_WEB.WEB_REL_AVATAR.$user->userVO->avatar)) {
					unlink(ROOT.ROOT_WEB.WEB_REL_AVATAR.$user->userVO->avatar);
				}
			}
			//update db
			$user->userVO->saveOnlyChanged = true;
			$user->userVO->set('avatar', $imageName);
			$user->userVO->save();
			break;
		case 'pava':
			$imageName = 'pageAvatar-'.$pageId.'.jpg';
			$imagePath = ROOT.ROOT_WEB.WEB_REL_PAGE_AVATAR.$imageName;
			//update db
			$pageVO = new PageVO($pageId,true);
			$pageVO->saveOnlyChanged = true;
			$pageVO->set('pageIco',$imageName);
			$pageVO->save();
			break;
		default:
			$pageVO = new PageVO($pageId,true);
			$galeryUrl = $pageVO->galeryDir;
			$imageName = strtolower($filename);
			$ext = FFile::fileExt($imageName);
			$imageName =str_replace('.'.$ext,'',$imageName); 
			$imagePath = ROOT.ROOT_WEB.WEB_REL_GALERY.$galeryUrl.'/'.FSystem::safeText($imageName).'.'.$ext;
	}
	
	file_put_contents($imagePath, base64_decode( $encData ));
	for($i=0;$i<$total;$i++) {
	  unlink(chunkFilename($filename,$i));
	}
}

echo 1;
}