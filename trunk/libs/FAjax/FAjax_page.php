<?php
class FAjax_page {
	static function edit($data) {
		
		if(isset($data['uploadify'])) {
			$numNewFoto = 0;
			$user = FUser::getInstance();
			//---copy foto
			$dir = WEB_REL_GALERY . $user->pageVO->galeryDir;
			FSystem::makeDir($dir);
			if($dir{count($dir)-1}!='/') $dir.='/';
			$cache = FCache::getInstance('d');
			$grpName = $user->userVO->userId . '-' .$data['modul'].'-upload';
			$arrData = $cache->getGroup($grpName);
			if(!empty($arrData)) {
				
				while($arrData) {
					$arr = array_shift($arrData);
					$fileTarget = $dir.$arr['filenameOriginal'];
					if(file_exists($fileTarget)) unlink($fileTarget);
					rename($arr['filenameTmp'],$fileTarget);
					chmod($fileTarget,0777);
					$cache->invalidateData($arr['uid'],$grpName);
				}
				//---call galery refresh
				$galery = new FGalery();
				$numNewFoto = $galery->refreshImgToDb($user->pageVO->pageId);
				if($numNewFoto > 0) {
					FAjax::addResponse('function','call','galeryLoadThumb;'.$numNewFoto);
				}
			}
			
			
		}
		
		page_PageEdit::process($data);
		
	}

}