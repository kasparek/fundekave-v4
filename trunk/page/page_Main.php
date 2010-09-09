<?php
include_once('iPage.php');
class page_Main implements iPage {
	
	static function process($data) {
		
	}
	
	static function build($data=array()) {
		
		$user = FUser::getInstance();
		$user->pageVO->showHeading = false;
		$userId = $user->userVO->userId;
		$tpl = FSystem::tpl('maina.tpl.html');
		
		$cache = FCache::getInstance('f',0);
		//--------------LAST-FORUM-POSTS
		//$data = $cache->getData(($user->userVO->userId*1).'-mainforum','pagelist');
		$data=false;
		if($data === false) {
			$fPages = new FPages('forum', $userId);
			$fPages->joinOnPropertie('itemIdLast',4);
			$arr = $fPages->getContent(0,4);
			$data = FPages::printPagelinkList($arr);
			$cache->setData($data);
		}
		if(!empty($data)) $tpl->setVariable('LASTFORUMPOSTS',$data);
		/**/
		
		//---------------LAST-BLOG-POSTS
		$dataArr = $cache->getData(($user->userVO->userId*1).'-mainblog','pagelist');
		if($dataArr===false) {
			$fPages = new FPages('blog', $userId);
			$fPages->joinOnPropertie('itemIdLast',4);
			$arr = $fPages->getContent(0,4);
			$dataArr = array();
			if(!empty($arr)) {
				$dataArr[] = FPages::printPagelinkList(array(array_shift($arr)));
				$dataArr[] = FPages::printPagelinkList($arr);
			}
			$cache->setData($dataArr);
		}
		
		if(!empty($dataArr)) {
			$tpl->setVariable('LASTBLOGPOST',$dataArr[0]);
			if(!empty($dataArr)) $tpl->setVariable('LASTBLOGPOSTS',$dataArr[1]);
		}

		/*
		//------LAST-CREATED-PAGES
		if(($tmptext = $cache->getData(($user->userVO->userId*1).'-main','lastCreated')) === false) {
			$fPages = new FPages(array('blog','galery','forum'),$user->userVO->userId);
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,p.typeId');
			$fPages->setOrder('p.dateCreated desc');
			$fPages->addWhere('p.locked < 2');
			$fPages->setLimit(0,5);
			$arr = $fPages->getContent();
			$tmptext = FPages::printPagelinkList($arr);
			$cache->setData( $tmptext );
		}
		$tpl->setVariable('NEWPAGECACHED',$tmptext);
		
		//------MOST-VISITED-PAGES
		if(($tmptext = $cache->getData(($user->userVO->userId*1).'-main','mostVisited')) === false) {
			$fPages = new FPages(array('blog','galery','forum'),$user->userVO->userId);
			$fPages->addJoin('join sys_pages_counter as pc on pc.pageId=p.pageId');
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,p.typeId');
			$fPages->addWhere('p.locked < 2');
			$fPages->addWhere('pc.dateStamp > date_sub(now(), interval 1 week)');
			$fPages->setGroup('pc.pageId');
			$fPages->setOrder('sum(pc.hit) desc');
			$fPages->setLimit(0,5);
			$arr = $fPages->getContent();
			$tmptext = FPages::printPagelinkList($arr);
			$cache->setData( $tmptext );
		}
		$tpl->setVariable('MOSTVISITEDECACHED',$tmptext);
		
		//------MOST-ACTIVE-PAGES
		if(($tmptext = $cache->getData(($user->userVO->userId*1).'-main','mostActive')) === false) {
			$fPages = new FPages(array('blog','galery','forum'),$user->userVO->userId);
			$fPages->addJoin('join sys_pages_counter as pc on pc.pageId=p.pageId');
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,p.typeId');
			$fPages->addWhere('p.locked < 2');
			$fPages->addWhere('pc.dateStamp > date_sub(now(), interval 1 week)');
			$fPages->setGroup('pc.pageId');
			$fPages->setOrder('sum(pc.ins) desc');
			$fPages->setLimit(0,5);
			$arr = $fPages->getContent();
			$tmptext = FPages::printPagelinkList($arr);
			$cache->setData( $tmptext );
		}
		$tpl->setVariable('MOSTACTIVECACHED',$tmptext);

		//------MOST-FAVOURITE-PAGES
		if(($tmptext = $cache->getData(($user->userVO->userId*1).'-main','mostFavourite')) === false) {
			$fPages = new FPages(array('blog','galery','forum'),$user->userVO->userId);
			$fPages->addJoin('join sys_pages_favorites as pf on pf.pageId=p.pageId');
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,p.typeId');
			$fPages->addWhere('p.locked < 2');
			$fPages->addWhere('pf.book=1');
			$fPages->setGroup('pf.pageId');
			$fPages->setOrder('sum(pf.book) desc');
			$fPages->setLimit(0,5);
			$arr = $fPages->getContent();
			$tmptext = FPages::printPagelinkList($arr);
			$cache->setData( $tmptext );
		}
		$tpl->setVariable('MOSTFAVOURITECACHED',$tmptext);
		*/
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
	}
	
}