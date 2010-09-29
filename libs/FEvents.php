<?php
class FEvents {
	
	static function view($archiv=false) {
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;
		$pageId = $user->pageVO->pageId;
		$itemId = 0;
		if($user->itemVO) $itemId = (int) $user->itemVO->itemId;
		
		if($user->pageParam=='u') {
			
			page_EventsEdit::build(); //TODO: deprecated
			
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

	
}