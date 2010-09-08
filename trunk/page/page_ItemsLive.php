<?php
include_once('iPage.php');
class page_ItemsLive implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$userId = (int) $user->userVO->userId;
		$user->pageVO->showHeading = false;

		$ret=false;

		$cache = FCache::getInstance('f',0);
		$p = 1;
		$urlVar = FConf::get('pager','urlVar');
		if(isset($_GET[$urlVar])) $p = (int) $_GET[$urlVar];
		if(isset($_GET['date'])) $date = (int) $_GET['date'];
		$cacheKey = 'live-u-'.$userId.(($p>1)?('-p-'.$p):('')).((!empty($date))?('-'.$date):(''));
		$cacheGrp = 'pagelist';
		$ret = $cache->getData($cacheKey,$cacheGrp);

		if($ret === false) {
			$localPerPage = $user->pageVO->perPage();
			
			$pages = new FPages(null, $userId);
			$pages->addJoin('right join sys_pages_items as sys_pages_items on sys_pages_items.pageId=sys_pages.pageId');
			//TODO: do not use pplastitem
			$pages->addSelect('itemId as pplastitem');
			
			if(SITE_STRICT == 1) {
				$pages->addWhere("sys_pages.pageIdTop = '".HOME_PAGE."'");
			}
				
			if(!empty($date)) {
			  //used for sorting
				$pages->addSelect("if( sys_pages_items.typeId='forum', sys_pages_items.dateCreated, sys_pages_items.dateStart) as dateLive");
				
				$pages->addWhere("(sys_pages_items.typeId='forum' and '".$date."'=date_format(sys_pages_items.dateCreated,'%Y%m%d'))
					or (sys_pages_items.typeId in ('blog','galery') and '".$date."'=date_format(sys_pages_items.dateStart,'%Y%m%d')) 
					or (sys_pages_items.typeId='event' and '".$date."'>=date_format(sys_pages_items.dateStart,'%Y%m%d') and '".$date."'<=date_format(sys_pages_items.dateEnd,'%Y%m%d'))");
				
				$pages->setOrder('dateLive desc');
				
			} else {
			
				$pages->setOrder('sys_pages_items.itemId desc');
				
			}
				
			$pager = new FPager(0,$localPerPage,array('noAutoparse'=>1));
			$from = ($pager->getCurrentPageID()-1) * $localPerPage;
							
			$data = $pages->getContent($from,$localPerPage+1);
			$totalItems = count($data);

			$maybeMore = false;
			if($totalItems > $localPerPage) {
				$maybeMore = true;
				array_pop($data);
			}

			if($from > 0) $totalItems += $from;
			$ret = '';
			if($totalItems > 0) {
				$pager->totalItems = $totalItems;
				$pager->maybeMore = $maybeMore;
				$pager->getPager();
				$tmptext = FPages::printPagelinkList($data);
				if ($totalItems > $localPerPage) $tmptext .= $pager->links;
				$ret = $tmptext;
			}
			$cache->setData($ret,$cacheKey,$cacheGrp);
		}
		if(!empty($ret)) FBuildPage::addTab(array("MAINDATA"=>$ret));
	}
}