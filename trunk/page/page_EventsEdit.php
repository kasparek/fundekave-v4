<?php
include_once('iPage.php');
class page_EventsEdit implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		
		if($user->itemVO->itemId > 0) {
			$itemVO = $user->itemVO; 
		} else {
			$itemVO = new ItemVO();
			$itemVO->typeId = 'event';
			$itemVO->userId = $user->userVO->userId;
			$itemVO->name = $user->userVO->name;
			$itemVO->dateCreated = 'NOW()';
			$itemVO->pageId = $user->pageVO->pageIdTop;
		}
		
		if(isset($data['del']) && $itemVO->itemId > 0) {
			$itemVO->delete();
			$cache = FCache::getInstance('f');
			$cache->invalidateDate('eventtip');
			$cache->invalidateDate('calendarlefthand');
			FError::addError(FLang::$LABEL_DELETED_OK);
			FHTTP::redirect(FUser::getUri());
		}

		if(isset($data["nav"])) {
			//---check flyer to upload
			if(isset($data['delfly']) && !empty($itemId)){
				if($itemVO->enclosure!='') {
					if(file_exists(FConf::get('events','flyer_source').$itemVO->enclosure)) unlink(FConf::get('events','flyer_source').$itemVO->enclosure);
					if(file_exists(FConf::get('events','flyer_cache').$itemVO->enclosure)) unlink(FConf::get('events','flyer_cache').$itemVO->enclosure);
				}
				$itemVO->enclosure = '';
			}
			//---check time
			$timeStart = '';
			$timeEnd = '';
			$timeStartTmp = trim($data['timestart']);
			if(FSystem::isTime($timeStartTmp)) $timeStart = ' '.$timeStartTmp;
			$timeEndTmp = trim($data['timeend']);
			if(FSystem::isTime($timeEndTmp)) $timeEnd = ' '.$timeEndTmp;
			//---check time
			$dateStart = FSystem::textins($data['datestart'],array('plainText'=>1));
			$dateStart = FSystem::switchDate($dateStart);
			if(FSystem::isDate($dateStart)) $dateStart .= $timeStart;
			else FError::addError(FLang::$ERROR_DATE_FORMAT);

			//---save array
			$itemVO->location = FSystem::textins($data['place'],array('plainText'=>1));
			$itemVO->addon = FSystem::textins($data['name'],array('plainText'=>1));
			$itemVO->dateStart = $dateStart;
			$itemVO->dateStartLocal = $data['datestart'];
			$itemVO->text = FSystem::textins($data['description']);

			$dateEnd = FSystem::textins($data['dateend'],array('plainText'=>1));
			$itemVO->dateEndLocal = $data['dateend'];
			$dateEnd = FSystem::switchDate($dateEnd);
			if(FSystem::isDate($dateEnd)) $itemVO->dateEnd = $dateEnd.$timeEnd;

			if($data['category'] > 0) $itemVO->categoryId = (int) $data['category'];

			if(empty($itemVO->addon)) FError::addError(FLang::$ERROR_NAME_EMPTY);

			if(!FError::isError()) {
				$itemId = $itemVO->save();
				if(!empty($data['akceletakurl'])) {
					$filename = "flyer".$itemId.'.jpg';
					if($file = file_get_contents($data['akceletakurl'])) {
						file_put_contents(FConf::get('events','flyer_source').$filename,$file);
					}
					$cachedThumb = FEvents::thumbUrl($filename);
					if(file_exists($cachedThumb)) { @unlink($cachedThumb); }

					$fImg = new FImgProcess(FConf::get('events','flyer_source') . $filename
					,$cachedThumb, array('quality'=>FConf::get('events','thumb_quality')
					,'width'=>FConf::get('events','thumb_width'),'height'=>0));

					$itemVO->enclosure = $filename;
					$itemVO->save();
					$user->cacheRemove('eventtip','calendarlefthand');
				} elseif(isset($data['__files'])) {
					if($data['__files']['akceletak']['error'] == 0) {
						$flypath = FConf::get('events','flyer_source');
						$arr = explode('.',$data['__files']['akceletak']['name']);
						$data['__files']['akceletak']['name'] = "flyer".$itemId.'.'.strtolower($arr[count($arr)-1]);
						if(FSystem::upload($data['__files']['akceletak'],$flypath,800000)) {
							$cachedThumb = FEvents::thumbUrl($data['__files']['akceletak']['name']);
							if(file_exists($cachedThumb)) { @unlink($cachedThumb); }
							//---create thumb
							$fImg = new FImgProcess(FConf::get('events','flyer_source') . $data['__files']['akceletak']['name']
							,$cachedThumb, array('quality'=>FConf::get('events','thumb_quality')
							,'width'=>FConf::get('events','thumb_width'),'height'=>0));
							$itemVO->enclosure = $data['__files']['akceletak']['name'];
							$itemVO->save();
							$user->cacheRemove('eventtip','calendarlefthand');
							FError::addError(FLang::$MESSAGE_SUCCESS_SAVED);
						}
					}
				}
			} else {
				$cache = FCache::getInstance('s');
				$cache->setData($itemVO,$user->pageVO->pageId,'form');
			}

			FHTTP::redirect(FUser::getUri());
		}


	}

	static function build() {

		$user = FUser::getInstance();
		
		FBuildPage::addTab( array("MAINDATA"=>FEvents::editForm( $user->itemVO->itemId ) ) );
	}
}