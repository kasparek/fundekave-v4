<?php
include_once('iPage.php');
class page_EventsView implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		if($user->pageParam == 'u') {
			page_EventsEdit::process($data);
		}
		else 
		{
			FForum::process($data);
		}

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;
		$pageId = $user->pageVO->pageId;
		$itemId = 0;
		if($user->itemVO) $itemId = (int) $user->itemVO->itemId;

		if(empty($user->pageParam)) {
			FMenu::secondaryMenuAddItem(FSystem::getUri('','eveac'),FLang::$LABEL_EVENTS_ARCHIV);
		}

		if($user->pageParam=='u') {
			
			page_EventsEdit::build();
			
		} else {
			
			
			$ppUrlVar = FConf::get('pager','urlVar');
			$pageNum = 1;
			if(isset($_GET[$ppUrlVar])) $pageNum = (int) $_GET[$ppUrlVar];
			$cache = FCache::getInstance('f',0);
			$cacheKey = $pageId.'-'.$pageNum.'-'.$itemId.'-'.(int) $userId;
			$cacheGrp = 'pagelist';
			$ret = $cache->getData($cacheKey,$cacheGrp);
			
			if($ret === false) {
			
				if( $user->itemVO ) {
	
					$itemVO = new ItemVO($user->itemVO->itemId, true ,array('type'=>'event','showComments'=>true) );
					$tpl = FSystem::tpl('events.tpl.html');
					$tpl->setVariable('ITEMS',$itemVO->render());
					$ret = $tpl->get();
	
				} else {
					
					$ret = FEvents::show();
					
				}
			
				if(isset($cacheKey)) $cache->setData($ret,$cacheKey,$cacheGrp);
			
			}
			FBuildPage::addTab(array("MAINDATA"=>$ret,"MAINID"=>'fajaxContent'));
		}
	}
}
