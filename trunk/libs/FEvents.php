<?php
class FEvents {
	
	static function view($archiv=false) {
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;
		$pageId = $user->pageVO->pageId;
		$itemId = 0;
		if($user->itemVO) $itemId = (int) $user->itemVO->itemId;
		
		if($user->pageParam=='u') {
			
			page_EventsEdit::build();
			
		} else {
			
			
			$ppUrlVar = FConf::get('pager','urlVar');
			$pageNum = 1;
			if(isset($_GET[$ppUrlVar])) $pageNum = (int) $_GET[$ppUrlVar];
			$cache = FCache::getInstance('f',0);
			$cacheKey = $pageId.$user->pageParam.'-'.$pageNum.'-'.$itemId.'-'.(int) $userId;
			$cacheGrp = 'pagelist';
			$ret = $cache->getData($cacheKey,$cacheGrp);
			
			if($ret === false) {
			
				if( $user->itemVO ) {
	
					$itemVO = new ItemVO($user->itemVO->itemId, true ,array('type'=>'event','showComments'=>true) );
					$tpl = FSystem::tpl('events.tpl.html');
					$tpl->setVariable('ITEMS',$itemVO->render());
					$ret = $tpl->get();
	
				} else {
					
					$ret = FEvents::show($archiv);
					
				}
			
				if(isset($cacheKey)) $cache->setData($ret,$cacheKey,$cacheGrp);
			
			}
			FBuildPage::addTab(array("MAINDATA"=>$ret,"MAINID"=>'fajaxContent'));
		}
	}
	
	static function process($data) {
		$user = FUser::getInstance();
		if($user->pageParam == 'u') {
			FEvents::processForm($data, true);
		}
		else 
		{
			FForum::process($data);
		}
	}

	static function thumbName($flyerName) {
		return str_replace(FFile::fileExt($flyerName),'jpg',$flyerName);
	}

	static function thumbUrl($flyerName, $root=URL_FLYER_THUMB) {
		return $root . FEvents::thumbName($flyerName);
	}

	static function flyerUrl($flyerName,$root=URL_FLYER) {
		return $root . $flyerName;
	}

	static function createThumb($imageName) {
		//---create paths
		$flyerFilename = FEvents::flyerUrl($imageName,ROOT_FLYER);
		$flyerFilenameThumb = FEvents::thumbUrl($imageName,ROOT_FLYER_THUMB);
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
		$user = FUser::getInstance();
		
		$adruh = 0;
		$filtr = '';

		$fItems = new FItems('event',false);
		if(isset($_REQUEST['c'])) $adruh = (int) $_REQUEST['c'];
		if(isset($_REQUEST['filtr'])) $filtr = trim($_REQUEST['filtr']);
		if($adruh > 0) $fItems->addWhere('categoryId="'.$adruh.'"');
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
		
		//--page type forum,blog - items with topid klubu
		if($user->pageVO->typeId == 'forum' || $user->pageVO->typeId == 'blog') {
			$fItems->addWhere("pageId = '".$user->pageVO->pageId."'");
		}

		//--listovani
		$celkem = $fItems->getCount();
		$perPage = FConf::get('events','perpage');
		$tpl = FSystem::tpl('events.tpl.html');
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
			$itemVO->public = 1;
			$itemVO->dateStart = Date("Y-m-d");
		}

		$tpl = FSystem::tpl('events.edit.tpl.html');
		$tpl->setVariable('FORMACTION',FSystem::getUri('m=event-submit&u='.FUser::logon()));
		$tpl->setVariable('HEADING',(($itemVO->itemId>0)?($itemVO->addon):(FLang::$LABEL_EVENT_NEW)));
		$tpl->setVariable('ITEMID',$itemVO->itemId);

		$q = 'select categoryId,name from sys_pages_category where typeId="event" order by ord,name';
		$arrOpt = FDBTool::getAll($q,'event','categ','l');
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

		//enhanced settings
		$reminder = $itemVO->prop('reminder');
		$reminderOptions='';
		foreach (FLang::$DIARYREMINDER as $k=>$v) {
			$reminderOptions.='<option value="'.$k.'"'.(($k==$reminder)?(' selected="selected"'):('')).'>'.$v.'</option>';
		}
		$tpl->setVariable('REMINDEROPTIONS',$reminderOptions);

		$reminderEveryday = (int) $itemVO->prop('reminderEveryday');
		if($reminderEveryday==1) $tpl->touchBlock('everydayselected');

		$repeat = $itemVO->prop('repeat');
		$repeatOptions='';
		foreach (FLang::$DIARYREPEATER as $k=>$v) {
			$repeatOptions.='<option value="'.$k.'"'.(($k==$repeat)?(' selected="selected"'):('')).'>'.$v.'</option>';
		}
		$tpl->setVariable('REPEATOPTIONS',$repeatOptions);

		$public = (int) $itemVO->public;
		$tpl->touchBlock('access'.$public);

		if( !empty($tplBlock) ) {
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
				$itemVO = new ItemVO();
				$itemVO->itemId = $data['item'];
				$itemVO->load();
			}
		}

		if(!isset($itemVO)) {
			$itemVO = new ItemVO();
			$itemVO->typeId = 'event';
			if($user->pageVO->typeId == 'forum' || $user->pageVO->typeId == 'blog') {
				$itemVO->pageId = $user->pageVO->pageId;
			} else {
				$itemVO->pageId = 'event';	
			}
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

		if($action=='delFlyer') {
			if($itemVO->itemId>0) {
				//del and update db
				if($itemVO->enclosure!='') {
					$rootFlyer = ROOT_FLYER.$itemVO->enclosure;
					$rootFlyerThumb = ROOT_FLYER_THUMB.$itemVO->enclosure;
					if(file_exists($rootFlyer)) unlink($rootFlyer);
					if(file_exists($rootFlyerThumb)) unlink($rootFlyerThumb);
				}
				$itemVO->enclosure = 'null';
				$itemVO->save();
			} else {
				//del temporary
				$cache = FCache::getInstance('d');

				$filename = $cache->getData('event','user-'.$user->userVO->userId);
				unlink(ROOT . 'tmp/upload/'.$user->userVO->name.'/'.$filename);

				$cache->invalidateData('event','user-'.$user->userVO->userId);
			}
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
			if(!empty($data['categoryNew'])) {
				$data['category'] = FCategory::tryGet( $data['categoryNew'],'event');
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

			$itemVO->public = (int) $data['dpublic'];

			if(!FError::isError()) {
				$itemId = $itemVO->save();
				if(!empty($data['akceletakurl'])) {
					$ext = FFile::fileExt($data['akceletakurl']);
					if($ext=='gif' || $ext=='jpg' || $ext=='jpeg' || $ext=='png') {
						$flyerName = FEvents::createFlyerName($itemVO->itemId, $data['akceletakurl']);
						//---delete old files
						if(!empty($itemVO->enclosure) && file_exists(ROOT_FLYER.$itemVO->enclosure)) unlink(ROOT_FLYER.$itemVO->enclosure);
						if(file_exists(ROOT_FLYER.$flyerName)) unlink(ROOT_FLYER.$flyerName);
						//---save file
						if($file = file_get_contents($data['akceletakurl'])) {
							file_put_contents(ROOT_FLYER.$flyerName,$file);
						}
						FEvents::createThumb($flyerName);

						$itemVO->enclosure = $flyerName;
						$itemVO->save();
					}
				} elseif(isset($data['__files'])) {
					if($data['__files']['akceletak']['error'] == 0) {
						$flyerName = $data['__files']['akceletak']['name'] = FEvents::createFlyerName($itemVO->itemId, $data['__files']['akceletak']['name']);
						//---delete old files
						if(!empty($itemVO->enclosure) && file_exists(ROOT_FLYER.$itemVO->enclosure)) unlink(ROOT_FLYER.$itemVO->enclosure);
						if(file_exists(ROOT_FLYER.$flyerName)) unlink(ROOT_FLYER.$flyerName);
						//---upload file
						if(FSystem::upload($data['__files']['akceletak'],ROOT_FLYER,800000)) {
							FEvents::createThumb($data['__files']['akceletak']['name']);
							$itemVO->enclosure = $data['__files']['akceletak']['name'];
							$itemVO->save();
							FError::addError(FLang::$MESSAGE_SUCCESS_SAVED);
						}
					}
				}
				//enhanced settings
				$itemVO->prop('reminder',$data['dpripomen']);
				$itemVO->prop('reminderEveryday',$data['dopakovat']);
				$itemVO->prop('repeat',$data['drepeat']);

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
				FHTTP::redirect(FSystem::getUri('#dd'));
			}
		}

		//---try image if is in cache
		if(!FError::isError()) {
			if(!isset($user)) $user = FUser::getInstance();
			$cache = FCache::getInstance('d');
			$filename = $cache->getData('event','user-'.$user->userVO->userId);
			$cache->invalidateData('event','user-'.$user->userVO->userId);
			if(!empty($filename)) {
				//---set flyer
				$flyerName = FEvents::createFlyerName($itemVO->itemId, $filename);
				if(!empty($itemVO->enclosure) && file_exists(ROOT_FLYER.$itemVO->enclosure)) unlink(ROOT_FLYER.$itemVO->enclosure);
				$flyerTarget = ROOT_FLYER.$flyerName;
				if(file_exists($flyerTarget)) unlink($flyerTarget);
				rename(FConf::get('settings','upload_tmp').$user->userVO->name.'/'.$filename, $flyerTarget);
				chmod($flyerTarget, 0777);
				FFile::makeDir(FConf::get('events','root_flyer_thumb'));
				FEvents::createThumb( $flyerName );
				$itemVO->enclosure = $flyerName;
				$itemVO->save();
			}
		}
		return $itemVO;
	}

	static function createFlyerName($itemId, $origFilename) {
		return "flyer-".$itemId.'-'.date("U").'.'.FFile::fileExt($origFilename);
	}
}