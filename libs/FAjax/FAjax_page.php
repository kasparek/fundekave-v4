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
			$arr = $cache->getData($user->userVO->userId . '-' .$data['modul'],'uploadify');
			if(!empty($arr)) {
				$fileTarget = $dir.$arr['filenameOriginal'];
				if(file_exists($fileTarget)) unlink($fileTarget);
				rename($arr['filenameTmp'],$fileTarget);
				chmod($fileTarget,0777);
				//---call galery refresh
				$galery = new FGalery();
				$numNewFoto = $galery->refreshImgToDb($user->pageVO->pageId);
				FAjax::addResponse('call','function','galeryLoadThumb;'.$numNewFoto);
			}
			
			
		}
		
	}

}