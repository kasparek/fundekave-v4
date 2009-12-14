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

			$fItems = new FItems('',$user->userVO->userId);
			$fItems->addJoin('join sys_pages as p on p.pageId=sys_pages_items.pageId');
			$fItems->addWhere('sys_pages_items.public > 0');
				
			$fItems->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			$fItems->setSelect("p.pageId,p.categoryId,p.name,p.pageIco,p.typeId".(($userId > 0)?(',(p.cnt-f.cnt)'):(',0'))." as newMess,sys_pages_items.itemId,sys_pages_items.typeId");
			
			if(SITE_STRICT == 1) {
				$fItems->addWhere("p.pageIdTop = '".HOME_PAGE."'");
			}
				
			if(!empty($date)) {
				$fItems->addSelect("if( sys_pages_items.typeId='forum', sys_pages_items.dateCreated, sys_pages_items.dateStart) as dateLive");
				$fItems->addWhere("(sys_pages_items.typeId='forum' and '".$date."'=date_format(sys_pages_items.dateCreated,'%Y%m%d'))
					or (sys_pages_items.typeId in ('blog','galery') and '".$date."'=date_format(sys_pages_items.dateStart,'%Y%m%d')) 
					or (sys_pages_items.typeId='event' and '".$date."'>=date_format(sys_pages_items.dateStart,'%Y%m%d') and '".$date."'<=date_format(sys_pages_items.dateEnd,'%Y%m%d'))");
				$fItems->setOrder('dateLive desc');
			} else {
				$fItems->setOrder('sys_pages_items.itemId desc');
			}
				
			$pager = new FPager(0,$localPerPage,array('noAutoparse'=>1));
			$from = ($pager->getCurrentPageID()-1) * $localPerPage;
			$fItems->map = false;
				
			//$fItems->debug=1;
				
			$fItems->getList($from,$localPerPage+1);
			$totalItems = count($fItems->data);

			$maybeMore = false;
			if($totalItems > ($localPerPage-$fItems->itemsRemoved)) {
				$maybeMore = true;
				array_pop($fItems->data);
			}

			if($from > 0) $totalItems += $from;
			$ret = '';
			if($totalItems > 0) {
				$pager->totalItems = $totalItems;
				$pager->maybeMore = $maybeMore;
				$pager->getPager();
				$tmptext = FPages::printPagelinkList($fItems->data);
				if ($totalItems > $localPerPage) $tmptext .= $pager->links;
				$ret = $tmptext;
			}
			$cache->setData($ret,$cacheKey,$cacheGrp);
		}
		if(!empty($ret)) FBuildPage::addTab(array("MAINDATA"=>$ret));
	}
}