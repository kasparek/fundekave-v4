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
	
					$itemVO = new ItemVO($user->itemVO->itemId, true ,array('type'=>'event','showDetail'=>true) );
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
	
	static function thumbUrl($flyerName) {
		return FConf::get('galery','targetUrlBase') .FConf::get('events','thumb_width').'x0/prop/page/event/'. ($flyerName);
	}

	static function flyerUrl($flyerName) {
		return FConf::get('galery','sourceUrlBase') .'page/event/'. $flyerName;
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

		$tpl = FSystem::tpl('form.event.tpl.html');
		$tpl->setVariable('FORMACTION',FSystem::getUri('m=event-submit&u='.FUser::logon()));
		$tpl->setVariable('ITEMID',$itemVO->itemId);

		//categories
		if($opt = FCategory::getOptions($user->pageVO->pageId,$itemVO->categoryId,true,'')) $tpl->setVariable('CATEGORYOPTIONS',$opt);

		$tpl->setVariable('ADDON',$itemVO->addon);
		$tpl->setVariable('PLACE',$itemVO->location);
		$tpl->setVariable('DATESTART',$itemVO->dateStartLocal);
		$tpl->setVariable('TIMESTART',$itemVO->dateStartTime);
		$tpl->setVariable('DATEEND',$itemVO->dateEndLocal);
		$tpl->setVariable('TIMEEND',$itemVO->dateEndTime);
		
		$tpl->setVariable('DESCRIPTION',FSystem::textToTextarea( $itemVO->text ));
		if($itemVO->itemId > 0) {
			$tpl->touchBlock('delete');
		}

		if(!empty( $itemVO->enclosure )) {
			$tpl->setVariable('DELFLY',FSystem::getUri('m=event-delFlyer&d=item:'.$itemVO->itemId));
			$tpl->setVariable('FLYERURL',FEvents::flyerUrl( $itemVO->enclosure ));
			$tpl->setVariable('FLYERTHUMBURL',FEvents::thumbUrl( $itemVO->enclosure ));
		}

		//enhanced settings
		$tpl->touchBlock('remindrepeat'.$itemVO->prop('reminderEveryday'));
		$tpl->touchBlock('remindbefore'.$itemVO->prop('reminder'));
		$tpl->touchBlock('repeat'.$itemVO->prop('repeat'));

		$public = (int) $itemVO->public;
		$tpl->touchBlock('public'.$public);

		if( !empty($tplBlock) ) {
			$tpl->parse($tplBlock);
			return $tpl->get($tplBlock);
		} else {
			return $tpl->get();
		}
	}

	static function processForm($data, $redirect=true) {
		$user = FUser::getInstance();
		if(isset($data['itemId'])) {
			if($data['itemId']>0) {
				$itemVO = new ItemVO();
				$itemVO->itemId = $data['itemId'];
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
				$itemVO->deleteImage();
				$itemVO->save();
			} else {
				//del temporary
				$temp = FFile::getTemplFilename();
				if($temp!==false) {
					$ffile = new FFile();
					$ffile->unlink();
				}
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
				$data['categoryId'] = FCategory::tryGet( $data['categoryNew'],'event');
			}

			//---check time
			$timeStart = '';
			$timeEnd = '';
			$timeStartTmp = trim($data['dateStartTime']);
			if(FSystem::isTime($timeStartTmp)) $timeStart = ' '.$timeStartTmp;
			$timeEndTmp = trim($data['dateEndTime']);
			if(FSystem::isTime($timeEndTmp)) $timeEnd = ' '.$timeEndTmp;
			//---check time
			$dateStart = FSystem::textins($data['dateStartLocal'],array('plainText'=>1));
			$dateStart = FSystem::switchDate($dateStart);
			if(FSystem::isDate($dateStart)) $dateStart .= $timeStart;
			else FError::addError(FLang::$ERROR_DATE_FORMAT);

			//---save array
			$itemVO->location = FSystem::textins($data['place'],array('plainText'=>1));
			$itemVO->addon = FSystem::textins($data['addon'],array('plainText'=>1));
			$itemVO->dateStart = $dateStart;
			$itemVO->dateStartLocal = $data['dateStartLocal'];
			$itemVO->text = FSystem::textins($data['text']);

			$dateEnd = FSystem::textins($data['dateEndLocal'],array('plainText'=>1));
			$itemVO->dateEndLocal = $data['dateEndLocal'];
			$dateEnd = FSystem::switchDate($dateEnd);
			if(FSystem::isDate($dateEnd)) $itemVO->dateEnd = $dateEnd.$timeEnd;

			if($data['categoryId'] > 0) $itemVO->categoryId = (int) $data['categoryId'];

			if(empty($itemVO->addon)) FError::addError(FLang::$ERROR_NAME_EMPTY);

			$itemVO->public = (int) $data['public'];

			if(!FError::isError()) {
				$itemId = $itemVO->save();
				if(!empty($data['imageUrl'])) {
					$ext = FFile::fileExt($data['imageUrl']);
					if($ext=='gif' || $ext=='jpg' || $ext=='jpeg' || $ext=='png') {
						$flyerName = FEvents::createFlyerName($itemVO->itemId, $data['imageUrl']);
						//---delete old files
						$itemVO->deleteImage();
						//---load file from URL and save to folder
						if($file = file_get_contents($data['imageUrl'])) {
							//TODO: write data to file / FTP from string
							//$galdir = $this->conf['sourceServerBase'] . $this->pageVO->galeryDir.'/';
							file_put_contents(ROOT_FLYER.$flyerName,$file);
							$itemVO->enclosure = $flyerName;
							$itemVO->save();	
						}
					}
				} elseif(isset($data['__files'])) {
					if($data['__files']['imageFile']['error'] == 0) {
						$flyerName = $data['__files']['imageFile']['name'] = FEvents::createFlyerName($itemVO->itemId, $data['__files']['akceletak']['name']);
						//---delete old files
						$itemVO->deleteImage();
						//---upload file
						if(FSystem::upload($data['__files']['imageFile'],ROOT_FLYER,800000)) {
							$itemVO->enclosure = $data['__files']['imageFile']['name'];
							$itemVO->save();
							FError::addError(FLang::$MESSAGE_SUCCESS_SAVED);
						}
					}
				}
				//enhanced settings
				$itemVO->prop('reminder',$data['reminder']);
				$itemVO->prop('reminderEveryday',$data['reminderEveryday']);
				$itemVO->prop('repeat',$data['repeat']);

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
			
			$filename = FFile::getTemplFilename();
			if($filename!==false) {
				//---set flyer
			
				//delete old flyer
				$itemVO->deleteImage();
				
				$filenameArr = explode('/',$filename);
				
				$pageVO = new PageVO($itemVO->pageId,true);
				$galdir = FConf::get('galery','sourceServerBase') . $pageVO->galeryDir.'/';
				
				$flyerTarget = $galdir.array_pop($filenameArr);
				
				$ffile = new FFile(FConf::get("galery","ftpServer"),FConf::get("galery","ftpUser"),FConf::get("galery","ftpPass"));
				$ffile->move_uploaded_file($filename,$flyerTarget);
								
				$itemVO->enclosure = $flyerName;
				$itemVO->save();
			}
		}
		return $itemVO;
	}
}