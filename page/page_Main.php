<?php
include_once('iPage.php');
class page_Main implements iPage {
	
	static function process($data) {
		
	}
	
	static function build() {
		
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;
		$tpl = new FTemplateIT('maina.tpl.html');
		
		$cache = FCache::getInstance('f',0);
		//--------------LAST-FORUM-POSTS
		$data = $cache->getData(($user->userVO->userId*1).'-main','lastForumPost');
		if($data === false) {
			$fPages = new FPages('forum', $userId);
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId > 0)?(',(p.cnt-f.cnt) as newMess'):(',0')).',pplastitem.value,p.typeId');
			$fPages->addJoin('left join sys_pages_properties as pplastitem on pplastitem.pageId=p.pageId and pplastitem.name = "itemIdLast"');
			if($user->idkontrol!==true) {
				$fPages->addWhere('p.locked < 2');
			} else {
				$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			}
			$fPages->setOrder("(pplastitem.value+0.0) desc");
			$arr = $fPages->getContent(0,4);
			
			$data = FPages::printPagelinkList($arr);
			$cache->setData($data);
		}
		if(!empty($data)) $tpl->setVariable('LASTFORUMPOSTS',$data);
		/**/
		
		//---------------LAST-BLOG-POSTS
		$dataArr = $cache->getData(($user->userVO->userId*1).'-main','lastBlogPost');
		if($dataArr===false) {
			$dataArr = array();
			
			$fPages = new FPages('blog', $userId);
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId > 0)?(',(p.cnt-f.cnt) as newMess'):(',0')).',pplastitem.value,p.typeId');
			$fPages->addJoin('left join sys_pages_properties as pplastitem on pplastitem.pageId=p.pageId and pplastitem.name = "itemIdLast"');
			if($user->idkontrol!==true) {
				$fPages->addWhere('p.locked < 2');
			} else {
				$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			}
			$fPages->setOrder("(pplastitem.value+0.0) desc");
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

		//------LAST-CREATED-PAGES
		if(($tmptext = $cache->getData(($user->userVO->userId*1).'-main','lastCreated')) === false) {
			$fPages = new FPages(array('blog','galery','forum'),$user->userVO->userId);
			$fPages->setOrder('p.dateCreated desc');
			$fPages->addWhere('p.locked < 2');
			$fPages->setLimit(0,5);
			$fPages->setSelect('p.pageId,p.typeId,p.name,p.description');
			$arr = $fPages->getContent();
			
			while($arr) {
				$row = array_shift($arr);
				$tpl->setCurrentBlock('newpage');
				$tpl->setVariable('NEWPAGEURL',FSystem::getUri('',$row[0]));
				$tpl->setVariable('NEWPAGETITLE',FSystem::textins($row[3],array('plainText'=>1)));
				$tpl->setVariable('NEWPAGETEXT',$row[2].' ['. FLang::$TYPEID[$row[1]].']');
				$tpl->parseCurrentBlock();
			}
			$tmptext = $tpl->get('newpage');
			$cache->setData( $tmptext );
		} else {
			$tpl->setVariable('NEWPAGECACHED',$tmptext);
		}
		
		//------MOST-VISITED-PAGES
		if(($tmptext = $cache->getData(($user->userVO->userId*1).'-main','mostVisited')) === false) {
			$arr = FDBTool::getCol("select pc.pageId from sys_pages_counter as pc join sys_pages as p on p.pageId=pc.pageId 
			where pc.dateStamp > date_sub(now(), interval 1 week) 
			and p.locked < 2
			and pc.typeId in ('galery','forum','blog') group by pc.pageId order by sum(hit) desc limit 0,10");
			//---cache result
			$x = 0;
			while($arr && $x < 6) {
				$pageId = array_shift($arr);
				if(FRules::get($user->userVO->userId,$pageId)) {
					$row = FDBTool::getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
					$tpl->setCurrentBlock('mostvisitedpage');
					$tpl->setVariable('MOSTVISITEDEURL',FSystem::getUri('',$row[0]));
					$tpl->setVariable('MOSTVISITEDTITLE',FSystem::textins($row[3],array('plainText'=>1)));
					$tpl->setVariable('MOSTVISITEDTEXT',$row[2].' ['.FLang::$TYPEID[$row[1]].']');
					$tpl->parseCurrentBlock();
					$x++;
				}
			}
			$tmptext = $tpl->get('mostvisitedpage');
			$cache->setData( $tmptext );
		} else {
			$tpl->setVariable('MOSTVISITEDECACHED',$tmptext);
		}
		
		//------MOST-ACTIVE-PAGES
		if(($tmptext = $cache->getData(($user->userVO->userId*1).'-main','mostActive')) === false) {
			$arr = FDBTool::getCol("select pc.pageId from sys_pages_counter as pc join sys_pages as p on p.pageId=pc.pageId 
			where pc.dateStamp > date_sub(now(), interval 1 week)  
			and p.locked < 2 
			and pc.typeId in ('galery','forum','blog') group by pc.pageId order by sum(ins) desc limit 0,10");
			//---cache result
			$x = 0;
			while($arr && $x < 6) {
				$pageId = array_shift($arr);
				if(FRules::get($user->userVO->userId,$pageId)) {
					$row = FDBTool::getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
					$tpl->setCurrentBlock('mostactivepage');
					$tpl->setVariable('MOSTACTIVEURL',FSystem::getUri('',$row[0]));
					$tpl->setVariable('MOSTACTIVETITLE',FSystem::textins($row[3],array('plainText'=>1)));
					$tpl->setVariable('MOSTACTIVETEXT',$row[2].' ['.FLang::$TYPEID[$row[1]].']');
					$tpl->parseCurrentBlock();
					$x++;
				}
			}
			$tmptext = $tpl->get('mostactivepage');
			$cache->setData( $tmptext );
		} else {		
			$tpl->setVariable('MOSTACTIVECACHED',$tmptext);
		}

		//------MOST-FAVOURITE-PAGES
		if(($tmptext = $cache->getData(($user->userVO->userId*1).'-main','mostFavourite')) === false) {
			$arr = FDBTool::getCol("select pf.pageId from sys_pages_favorites as pf join sys_pages as p on p.pageId=pc.pageId 
			where pf.book=1 and p.locked < 2 group by pf.pageId order by sum(pf.book) desc limit 0,10");
			//---cache result
			$x = 0;
			while($arr && $x < 6) {
				$pageId = array_shift($arr);
				if(FRules::get($user->userVO->userId,$pageId)) {
					$row = FDBTool::getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
					$tpl->setCurrentBlock('mostfavouritepage');
					$tpl->setVariable('MOSTFAVOURITEURL',FSystem::getUri('',$row[0]));
					$tpl->setVariable('MOSTFAVOURITETITLE',FSystem::textins($row[3],array('plainText'=>1)));
					$tpl->setVariable('MOSTFAVOURITETEXT',$row[2].' ['.FLang::$TYPEID[$row[1]].']');
					$tpl->parseCurrentBlock();
					$x++;
				}
			}
			$tmptext = $tpl->get('mostfavouritepage');
			$cache->setData($tmptext);
		} else {		
			$tpl->setVariable('MOSTFAVOURITECACHED',$tmptext);
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
	}
	
}