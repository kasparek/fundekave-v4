<?php
class FEvents {
	static function thumbName($flyerName) {
		$arrTmp = explode('.',$flyerName);
		return str_replace($arrTmp[count($arrTmp)-1],'jpg',$flyerName);
	}
	static function thumbUrl($flyerName) {
		$conf = FConf::getInstance();
		return $conf->a['events']['flyer_cache'] . FEvents::thumbName($flyerName);
	}
	static function flyerUrl($flyerName) {
		$conf = FConf::getInstance();
		return $conf->a['events']['flyer_source'] . $flyerName;
	}
	static function createThumb($imageName) {
		
		$flyerFilename = FEvents::flyerUrl($imageName);
		$flyerFilenameThumb = FEvents::thumbUrl($imageName);
		
		if(!file_exists($flyerFilenameThumb)) {
			//---create thumb
			FImgProcess::process($flyerFilename,$flyerFilenameThumb
			,array('quality'=>FConf::get('events','thumb_quality')
			,'width'=>FConf::get('events','thumb_width'),'height'=>0));
			return true;
		} else {
			return true;
		}
	}
	
	static function editForm( $itemId=0 ) {
		$cache = FCache::getInstance('s');
		if($itemId > 0) {
			$itemVO = new ItemVO();
			$itemVO->itemId = $itemId;
		} else {
			$user = FUser::getInstance();
			$itemVO = $user->itemVO;
		}
		
		if($itemVO->itemId > 0) {
			$itemVO->typeId = 'event';
			$itemVO->load();
		} elseif(false !== ($itemVO = $cache->getData('event','form'))) {
			$cache->invalidateData('event','form');
		} else {
			$itemVO = new ItemVO();
			$itemVO->itemId = 0;
			$itemVO->categoryId = 0;
			$itemVO->dateStartLocal = Date("d.m.Y");
		}
		
		//print_r($itemVO);
		//die();

		$tpl = new FTemplateIT('events.edit.tpl.html');
		$tpl->setVariable('FORMACTION',FUser::getUri());
		$tpl->setVariable('HEADING',(($itemVO->itemId>0)?($itemVO->addon):(FLang::$LABEL_EVENT_NEW)));

		$q = 'select categoryId,name from sys_pages_category where typeId="event" order by ord,name';
		$arrOpt = FDBTool::getAll($q,'event','categ','s');
		$options = '';
		if(!empty($arrOpt)) foreach ($arrOpt as $row) {
			$options .= '<option value="'.$row[0].'"'.(($row[0] == $itemVO->categoryId)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
		}
		$tpl->setVariable('CATOPTIONS',$options);

		$tpl->setVariable('PLACE',$itemVO->location);
		$tpl->setVariable('NAME',$itemVO->addon);
		$tpl->setVariable('DATESTART',$itemVO->dateStartLocal);
		$tpl->setVariable('TIMESTART',$itemVO->timeStart);
		$tpl->setVariable('DATEEND',$itemVO->dateEndLocal);
		$tpl->setVariable('TIMEEND',$itemVO->timeEnd);
		$tpl->setVariable('DESCRIPTION',FSystem::textToTextarea( $itemVO->text ));
		$tpl->addTextareaToolbox('DESCRIPTIONTOOLBOX','event');
		if($itemVO->itemId > 0) {
			$tpl->touchBlock('delakce');
		}

		if(!empty( $itemVO->enclosure )) {
			$tpl->setVariable('FLYERURL',FEvents::flyerUrl( $itemVO->enclosure ));
			$tpl->setVariable('FLYERTHUMBURL',FEvents::thumbUrl( $itemVO->enclosure ));
		}
		return $tpl->get();
	}
	
	static function processForm($data, $redirect=true) {
		$user = FUser::getInstance();
		
		if($user->itemVO->itemId > 0) {
			$itemVO = $user->itemVO; 
		} else {
			$itemVO = new ItemVO();
			$itemVO->typeId = 'event';
			$itemVO->pageId = 'event';
			$itemVO->userId = $user->userVO->userId;
			$itemVO->name = $user->userVO->name;
		}
		
		if(isset($data['del']) && $itemVO->itemId > 0) {
			$itemVO->delete();
			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('eventtip');
			$cache->invalidateGroup('calendarlefthand');
			$user->itemVO = new ItemVO();
			FError::addError(FLang::$LABEL_DELETED_OK);
			if($redirect === true) {
				FHTTP::redirect(FUser::getUri());
			} else {
				return false;
			}
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
							FError::addError(FLang::$MESSAGE_SUCCESS_SAVED);
						}
					}
				}
				$cache = FCache::getInstance('f');
				$cache->invalidateGroup('eventtip');
				$cache->invalidateGroup('calendarlefthand');
				$user = FUser::getInstance();
				$user->itemVO = $itemVO; 
			} else {
				$cache = FCache::getInstance('s');
				$cache->setData($itemVO,$user->pageVO->pageId,'form');
			}
			
			if($redirect==true) {
				FHTTP::redirect(FUser::getUri());
			} else {
				return $itemVO;
			}
		}
	}
}