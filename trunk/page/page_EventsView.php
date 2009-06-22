<?php
include_once('iPage.php');
class page_EventsView implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		if($user->pageParam == 'u') {
			page_EventsEdit::process($data);
		}

	}

	static function build() {
		$user = FUser::getInstance();

		if($user->pageParam == 'archiv') $archiv = 1;

		if(empty($user->pageParam)) {
			FSystem::secondaryMenuAddItem(FUser::getUri('','event','archiv'),FLang::$LABEL_EVENTS_ARCHIV);
		}

		if($user->pageParam=='u') {
			
			page_EventsEdit::build();
			
		} else {
			
			$adruh = 0;
			$filtr = '';

			if($user->itemVO->itemId > 0) {

				$itemVO = new ItemVO($user->itemVO->itemId,true
					,array('type'=>'event','showComments'=>true)
				);
				$tpl = new FTemplateIT('events.tpl.html');
				$tpl->setVariable('ITEMS',$itemVO->render());

			} else {
				
				$category = new FCategory('sys_pages_category','categoryId');
				FBuildPage::addTab(array("MAINDATA"=>$category->getList('event')));
				
				$fItems = new FItems('event',false);
				if(isset($_REQUEST['kat'])) $adruh = (int) $_REQUEST['kat'];
				if(isset($_REQUEST['filtr'])) $filtr = trim($_REQUEST['filtr']);
				if($adruh>0) $fItems->addWhere('categoryId="'.$adruh.'"');
				if(!empty($filtr)) $fItems->addWhereSearch(array('location','addon','text'),$filtr,'or');

				if(!isset($archiv)) {
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
						$pager = FSystem::initPager($celkem,$perPage,array('extraVars'=>array('kat'=>$adruh,'filtr'=>$filtr)));
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
			}
			FBuildPage::addTab(array("MAINDATA"=>$tpl->get(),"MAINID"=>'fajaxContent'));
		}
	}
}
