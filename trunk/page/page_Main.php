<?php
include_once('iPage.php');
class page_Main implements iPage {
	
	static function process() {
		
	}
	
	static function build() {
		
		$user = FUser::getInstance();
		$tpl = new FTemplateIT('maina.tpl.html');
		
		$cache = FCache::getInstance('f',3600);
		//--------------LAST-FORUM-POSTS
		$data = $cache->getData(FUser::logon(),'lastForumPost');
		if($data === false) {
			
			$arr = FDBTool::getCol("SELECT max(ItemId) as maxid FROM sys_pages_items where typeId='forum' group by pageId order by maxid desc limit 0,6");
			$data = '';
			if(!empty($arr)) {
				$strItemId = implode(',',$arr);
				
				$itemRenderer = new FItemsRenderer();
				$itemRenderer->showPageLabel = true;
				$fItems = new FItems('forum',$user->userVO->userId,$itemRenderer);
				$fItems->addWhere('itemId in ('.$strItemId.')');
				$fItems->addOrder('dateCreated desc');
				$fItems->getList(0,3);
				while($fItems->data) $fItems->parse();
				$data = $fItems->show();
			}
			$cache->setData($data);
		}
		if(!empty($data)) $tpl->setVariable('LASTFORUMPOSTS',$data);
		/**/
		//---------------LAST-BLOG-POSTS
		$data = $cache->getData(FUser::logon(),'lastBlogPost');
		if($data===false) {
			$dataArr = array();
			$arr = FDBTool::getCol("SELECT itemId FROM sys_pages_items where public = 1 and typeId='blog' and itemIdTop is null order by dateCreated desc limit 0,10");
			if(!empty($arr)) {
				$itemRenderer = new FItemsRenderer();
				$itemRenderer->showHeading = true;
				$fItems = new FItems('blog',$user->userVO->userId,$itemRenderer);
				$fItems->addWhere('itemId in ('.implode(',',$arr).')');
				$fItems->addOrder('dateCreated desc');
				$fItems->getList(0,5);
				$firstPost = true;
				while($fItems->data) {
					$fItems->parse();
					$dataArr[] = $fItems->show();
				}
			}
			$cache->setData($dataArr);
		}
		if(!empty($dataArr)) {
			$tpl->setVariable('LASTBLOGPOST',array_shift($dataArr));
			if(!empty($dataArr)) $tpl->setVariable('LASTBLOGPOSTS',implode("\n",$dataArr));
		}

		//------LAST-CREATED-PAGES
		if(($tmptext = $cache->getData($user->userVO->userId,'lastCreated')) === false) {
			$fPages = new FPages(array('blog','galery','forum'),$user->userVO->userId);
			$fPages->setOrder('p.dateCreated desc');
			$fPages->addWhere('p.locked < 2');
			$fPages->setLimit(0,5);
			$fPages->setSelect('p.pageId,p.typeId,p.name,p.description');
			$arr = $fPages->getContent();
			
			while($arr) {
				$row = array_shift($arr);
				$tpl->setCurrentBlock('newpage');
				$tpl->setVariable('NEWPAGEURL','?k='.$row[0]);
				$tpl->setVariable('NEWPAGETITLE',FSystem::textins($row[3],array('plainText'=>1)));
				$tpl->setVariable('NEWPAGETEXT',$row[2].' ['. FLang::$TYPEID[$row[1]].']');
				$tpl->parseCurrentBlock();
			}
			$tmptext = $tpl->get('newpage');
			$cache->setData( $tmptext );
		}
		$tpl->setVariable('NEWPAGECACHED',$tmptext);
		
		//------MOST-VISITED-PAGES
		if(($tmptext = $cache->getData($user->userVO->userId,'mostVisited')) === false) {
			$arr = FDBTool::getCol("select pageId from sys_pages_counter where dateStamp > date_sub(now(), interval 1 week) and typeId in ('galery','forum','blog') group by pageId order by sum(hit) desc limit 0,10");
			//---cache result
			$x = 0;
			while($arr && $x < 6) {
				$pageId = array_shift($arr);
				if(FRules::get($user->userVO->userId,$pageId)) {
					$row = FDBTool::getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
					$tpl->setCurrentBlock('mostvisitedpage');
					$tpl->setVariable('MOSTVISITEDEURL','?k='.$row[0]);
					$tpl->setVariable('MOSTVISITEDTITLE',FSystem::textins($row[3],array('plainText'=>1)));
					$tpl->setVariable('MOSTVISITEDTEXT',$row[2].' ['.FLang::$TYPEID[$row[1]].']');
					$tpl->parseCurrentBlock();
					$x++;
				}
			}
			$tmptext = $tpl->get('mostvisitedpage');
			$cache->setData( $tmptext );
		}
		
		$tpl->setVariable('MOSTVISITEDECACHED',$tmptext);
		
		//------MOST-ACTIVE-PAGES
		if(($tmptext = $cache->getData($user->userVO->userId,'mostActive')) === false) {
			$arr = FDBTool::getCol("select pageId from sys_pages_counter where dateStamp > date_sub(now(), interval 1 week) and typeId in ('galery','forum','blog') group by pageId order by sum(ins) desc limit 0,10");
			//---cache result
			$x = 0;
			while($arr && $x < 6) {
				$pageId = array_shift($arr);
				if(FRules::get($user->userVO->userId,$pageId)) {
					$row = FDBTool::getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
					$tpl->setCurrentBlock('mostactivepage');
					$tpl->setVariable('MOSTACTIVEURL','?k='.$row[0]);
					$tpl->setVariable('MOSTACTIVETITLE',FSystem::textins($row[3],array('plainText'=>1)));
					$tpl->setVariable('MOSTACTIVETEXT',$row[2].' ['.FLang::$TYPEID[$row[1]].']');
					$tpl->parseCurrentBlock();
					$x++;
				}
			}
			$tmptext = $tpl->get('mostactivepage');
			$cache->setData( $tmptext );
		}
		
		$tpl->setVariable('MOSTACTIVECACHED',$tmptext);
		

		//------MOST-FAVOURITE-PAGES
		if(($tmptext = $cache->getData($user->userVO->userId,'mostFavourite')) === false) {
			$arr = FDBTool::getCol("select pageId from sys_pages_favorites where book=1 group by pageId order by sum(book) desc limit 0,10");
			//---cache result
			$x = 0;
			while($arr && $x < 6) {
				$pageId = array_shift($arr);
				if(FRules::get($user->userVO->userId,$pageId)) {
					$row = FDBTool::getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
					$tpl->setCurrentBlock('mostfavouritepage');
					$tpl->setVariable('MOSTFAVOURITEURL',FUser::getUri($row[0]));
					$tpl->setVariable('MOSTFAVOURITETITLE',FSystem::textins($row[3],array('plainText'=>1)));
					$tpl->setVariable('MOSTFAVOURITETEXT',$row[2].' ['.FLang::$TYPEID[$row[1]].']');
					$tpl->parseCurrentBlock();
					$x++;
				}
			}
			$tmptext = $tpl->get('mostfavouritepage');
			$cache->setData($tmptext);
		} 
		
		$tpl->setVariable('MOSTFAVOURITECACHED',$tmptext);

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
	}
	
}