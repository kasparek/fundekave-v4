<?php
include_once('iPage.php');
class page_EventsView implements iPage {

	static function process() {
		$user = FUser::getInstance();
		if($user->pageParam == 'u') {
			page_EventsEdit::process();
		}

	}

	static function build() {
		$user = FUser::getInstance();

		if($user->pageParam == 'archiv') $archiv = 1;

		if($user->pageParam=='archiv' || $user->pageParam=='a' || $user->pageParam=='e') {
			FSystem::secondaryMenuAddItem(FUser::getUri('','event',''),FLang::$BUTTON_PAGE_BACK);
		} else {
			FSystem::secondaryMenuAddItem(FUser::getUri('','eventarchiv'),FLang::$LABEL_EVENTS_ARCHIV);
		}

		if($user->pageParam=='u') {
			page_EventsEdit::build();
		} else {
			$category = new FCategory('sys_pages_category','categoryId');
			FBuildPage::addTab(array("MAINDATA"=>$category->getList('event')));
			
			$fItems = new FItems();
			$fItems->initData('event',false,true);

			$adruh = 0;
			$filtr = '';

			if($user->itemVO->itemId>0) {

				$fItems->showComments = true;
				$fItems->initDetail($user->itemVO->ItemId);

			} else {
				
				if(isset($_REQUEST['kat'])) $adruh = (int) $_REQUEST['kat'];
				if(isset($_REQUEST['filtr'])) $filtr = trim($_REQUEST['filtr']);
				if($adruh>0) $fItems->addWhere('i.categoryId="'.$adruh.'"');
				if(!empty($filtr)) $fItems->addWhereSearch(array('i.location','i.addon','i.text'),$filtr,'or');

				if(!isset($archiv)) {
					//---future
					$fItems->addWhere("(i.dateStart >= date_format(NOW(),'%Y-%m-%d') or (i.dateEnd is not null and i.dateEnd >= date_format(NOW(),'%Y-%m-%d')))");
					$fItems->setOrder('i.dateStart');
				} else {
					//---archiv
					$fItems->addWhere("i.dateStart < date_format(NOW(),'%Y-%m-%d')");
					$fItems->setOrder('i.dateStart desc');
				}
			}

			//--listovani
			$celkem = $fItems->getCount();
			$conf = FConf::getInstance();
			$perPage = $conf->a['events']['perpage'];
			$tpl = new fTemplateIT('events.tpl.html');
			if($celkem > 0) {
				if($celkem > $perPage) {
					$pager = FSystem::initPager($celkem,$perPage,array('extraVars'=>array('kat'=>$adruh,'filtr'=>$filtr)));
					$od = ($pager->getCurrentPageID()-1) * $perPage;
				} else $od=0;
				 
				$fItems->getData($od,$perPage);

				if($user->itemVO->itemId == 0) {
					
					if($celkem > $perPage) {
						$tpl->setVariable('LISTTOTAL',$celkem);
						$tpl->setVariable('PAGER',$pager->links);
					}
					
				} else {
					
					$fItems->showHeading = false;
					
				}
				//---items parsing
				while ($fItems->arrData) {
					$fItems->parse();
				}
				 
				if($user->itemVO->itemId > 0) $user->pageVO->name = $fItems->currentHeader;
				 
				$tpl->setVariable('ITEMS',$fItems->show());
			} else {
				$tpl->touchBlock('notanyevents');
			}
			FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		}
	}
}
