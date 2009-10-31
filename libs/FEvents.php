<?php
class FEvents {

	static function thumbName($flyerName) {
		return str_replace(FFile::fileExt($flyerName),'jpg',$flyerName);
	}
	
	static function thumbUrl($flyerName) {
		return FConf::get('events','flyer_cache') . FEvents::thumbName($flyerName);
	}
	
	static function flyerUrl($flyerName) {
		return FConf::get('events','flyer_source') . $flyerName;
	}
	
	static function createThumb($imageName) {
		//---create paths
		$flyerFilename = FEvents::flyerUrl($imageName);
		$flyerFilenameThumb = FEvents::thumbUrl($imageName);
		//---delete old
		if(file_exists($flyerFilenameThumb)) { unlink($flyerFilenameThumb); }
		//---generate thumb
		if(!file_exists($flyerFilenameThumb)) {
			//---create thumb
			FImgProcess::process($flyerFilename,$flyerFilenameThumb
			,array('quality'=>FConf::get('events','thumb_quality')
			,'width'=>FConf::get('events','thumb_width'),'height'=>0));
			return true;
		} else {
			return false;
		}
	}

	static function show($archiv=false) {
		$adruh = 0;
		$filtr = '';
		
		$category = new FCategory('sys_pages_category','categoryId');
		FBuildPage::addTab(array("MAINDATA"=>$category->getList('event')));

		$fItems = new FItems('event',false);
		if(isset($_REQUEST['kat'])) $adruh = (int) $_REQUEST['kat'];
		if(isset($_REQUEST['filtr'])) $filtr = trim($_REQUEST['filtr']);
		if($adruh>0) $fItems->addWhere('categoryId="'.$adruh.'"');
		if(!empty($filtr)) $fItems->addWhereSearch(array('location','addon','text'),$filtr,'or');

		if($archiv===false) {
			//---future
			$fItems->addWhere("(dateStart >= date_format(NOW(),'%Y-%m-%d') or (dateEnd is not null and dateEnd >= date_format(NOW(),'%Y-%m-%d')))");
			$fItems->setOrder('dateStart');
		} else {
			//---archiv
			$fItems->addWhere("dateStart < date_format(NOW(),'%Y-%m-%d')");
			$fItems->setOrder('dateStart desc');
		}

		//--listovani
		$celkem = $fItems->getCount();
		$perPage = FConf::get('events','perpage');
		$tpl = new FTemplateIT('events.tpl.html');
		if($celkem > 0) {
			if($celkem > $perPage) {
				$pager = new FPager($celkem,$perPage,array('extraVars'=>array('kat'=>$adruh,'filtr'=>$filtr)));
				$od = ($pager->getCurrentPageID()-1) * $perPage;
			} else $od=0;
			if($celkem > $perPage) {
				$tpl->setVariable('LISTTOTAL',$celkem);
				$tpl->setVariable('PAGER',$pager->links);
			}
			$tpl->setVariable('ITEMS',$fItems->render($od,$perPage));
		} else {
			$tpl->touchBlock('notanyevents');
		}
		return $tpl->get();
	}

	static function editForm( $itemId=0, $tplBlock='' ) {
		$cache = FCache::getInstance('s');
		
		if($itemId > 0) {
			$itemVO = new ItemVO($itemId,true,array('type'=>'event'));
		} elseif(false !== ($itemVO = $cache->getData('event','form'))) {
			$cache->invalidateData('event','form');
		} else {
			$itemVO = new ItemVO();
			$itemVO->itemId = 0;
			$itemVO->categoryId = 0;
			$itemVO->dateStartLocal = Date("d.m.Y");
		}

		$tpl = new FTemplateIT('events.edit.tpl.html');
		$tpl->setVariable('FORMACTION',FSystem::getUri('m=event-submit&u='.FUser::logon()));
		$tpl->setVariable('HEADING',(($itemVO->itemId>0)?($itemVO->addon):(FLang::$LABEL_EVENT_NEW)));
		$tpl->setVariable('ITEMID',$itemVO->itemId);

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
		$tpl->setVariable('TIMESTART',$itemVO->dateStartTime);
		$tpl->setVariable('DATEEND',$itemVO->dateEndLocal);
		$tpl->setVariable('TIMEEND',$itemVO->dateEndTime);
		$tpl->setVariable('DESCRIPTION',FSystem::textToTextarea( $itemVO->text ));
		if($itemVO->itemId > 0) {
			$tpl->touchBlock('delakce');
		}

		if(!empty( $itemVO->enclosure )) {
			$tpl->setVariable('DELFLY',FSystem::getUri('m=event-delFlyer&d=item:'.$itemVO->itemId));
			$tpl->setVariable('FLYERURL',FEvents::flyerUrl( $itemVO->enclosure ));
			$tpl->setVariable('FLYERTHUMBURL',FEvents::thumbUrl( $itemVO->enclosure ));
		}

		if(!empty($tplBlock)) {
			$tpl->parse($tplBlock);
			return $tpl->get($tplBlock);
		} else {
			return $tpl->get();
		}
	}

	static function processForm($data, $redirect=true) {
		$user = FUser::getInstance();
		if(isset($data['item'])) {
			if($data['item']>0) {
				$itemVO = new ItemVO($data['item'],true,array('type'=>'event'));
			}
		}

		if(!isset($itemVO)) {
			$itemVO = new ItemVO();
			$itemVO->typeId = 'event';
			$itemVO->pageId = 'event';
			$itemVO->userId = $user->userVO->userId;
			$itemVO->name = $user->userVO->name;
		}

		$action = '';
		if(isset($data['action'])) {
			$action = $data['action'];
		}
		if(isset($data['del'])) {
			$action = 'del';
		}
		if(isset($data['nav'])) {
			$action = 'nav';
		}
			

		if($action=='del' && $itemVO->itemId > 0) {
			$itemVO->delete();
			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('eventtip');
			$cache->invalidateGroup('calendarlefthand');
			$user->itemVO = new ItemVO();
			FError::addError(FLang::$LABEL_DELETED_OK);
			if($redirect === true) {
				FHTTP::redirect(FSystem::getUri());
			} else {
				return false;
			}
		}

		if($action=='nav') {
				
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
					$ext = FFile::fileExt($data['akceletakurl']);
					if($ext=='gif' || $ext=='jpg' || $ext=='jpeg' || $ext=='png') {
						$flyerName = FEvents::createFlyerName($itemVO->itemId, $data['akceletakurl']);
						//---delete old files
						if(!empty($itemVO->enclosure) && file_exists(FConf::get('events','flyer_source').$itemVO->enclosure)) unlink(FConf::get('events','flyer_source').$itemVO->enclosure);
						if(file_exists(FConf::get('events','flyer_source').$flyerName)) unlink(FConf::get('events','flyer_source').$flyerName);
						//---save file
						if($file = file_get_contents($data['akceletakurl'])) {
							file_put_contents(FConf::get('events','flyer_source').$flyerName,$file);
						}
						FEvents::createThumb($flyerName);
						
						$itemVO->enclosure = $flyerName;
						$itemVO->save();
					}
				} elseif(isset($data['__files'])) {
					if($data['__files']['akceletak']['error'] == 0) {
						$flyerName = $data['__files']['akceletak']['name'] = FEvents::createFlyerName($itemVO->itemId, $data['__files']['akceletak']['name']);
						//---delete old files
						if(!empty($itemVO->enclosure) && file_exists(FConf::get('events','flyer_source').$itemVO->enclosure)) unlink(FConf::get('events','flyer_source').$itemVO->enclosure);
						if(file_exists(FConf::get('events','flyer_source').$flyerName)) unlink(FConf::get('events','flyer_source').$flyerName);
						//---upload file
						if(FSystem::upload($data['__files']['akceletak'],FConf::get('events','flyer_source'),800000)) {
							FEvents::createThumb($data['__files']['akceletak']['name']);
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
				
			if($redirect === true) {
				FHTTP::redirect(FSystem::getUri());
			} else {
				return $itemVO;
			}
		}

		if(isset($data['uploadify'])) {
				
			$user = FUser::getInstance();
				
			$cache = FCache::getInstance('d');
			$cacheGrpId = $user->userVO->userId.'-event-submit-up';
			
			$arr = $cache->getGroup( $cacheGrpId );
			
			if(!empty($arr)) {
				//---for flyer just one file
				$arr = $arr[0];
				if(empty($itemVO->itemId)) {
					$itemVO->save();
				}
				//---set flyer
				$flyerName = FEvents::createFlyerName($itemVO->itemId, $arr['filenameOriginal']);
				if(!empty($itemVO->enclosure) && file_exists(FConf::get('events','flyer_source').$itemVO->enclosure)) unlink(FConf::get('events','flyer_source').$itemVO->enclosure);
				$flyerTarget = FConf::get('events','flyer_source').$flyerName;
				if(file_exists($flyerTarget)) unlink($flyerTarget);
				rename($arr['filenameTmp'], $flyerTarget);
				chmod($flyerTarget, 0777);
				FFile::makeDir(FConf::get('events','flyer_cache'));
				FEvents::createThumb( $flyerName );
				$itemVO->enclosure = $flyerName;
				$itemVO->save();
				$cache->invalidateGroup( $cacheGrpId );
				return $itemVO;
			}
		}
	}
		
	static function createFlyerName($itemId, $origFilename) {
		return "flyer-".$itemId.'-'.date("U").'.'.FFile::fileExt($origFilename);
	}
}