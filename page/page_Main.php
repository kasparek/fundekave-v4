<?php
include_once('iPage.php');
class page_Main implements iPage {
	
	static function process() {
		
	}
	
	static function build() {
		
		$tpl = new FTemplateIT('maina.tpl.html');

		//--------------LAST-FORUM-POSTS
		$cache = FCache::getInstance('f',3600);
		$data = $cache->getData('lastForumPost');
		if($data === false) {
			$fItems = new fItems();
			$arr = FDBTool::getCol("SELECT max(ItemId) as maxid FROM sys_pages_items where typeId='forum' group by pageId order by maxid desc limit 0,6");
			$data = '';
			if(!empty($arr)) {
				$strItemId = implode(',',$arr);
				$fItems->showPageLabel = true;
				$fItems->initData('forum',$user->userVO->userId,true);
				$fItems->addWhere('i.itemId in ('.$strItemId.')');
				$fItems->addOrder('i.dateCreated desc');
				//$fItems->setGroup('i.pageId');
				$fItems->getData(0,3);
				while($fItems->arrData) $fItems->parse();
				$data = $fItems->show();
			}
			$cache->setData($data);
		}
		if(!empty($data)) $tpl->setVariable('LASTFORUMPOSTS',$data);

		//---------------LAST-BLOG-POSTS
		$firstPostSeparator = ';|||;';
		$data = $cache->getData('lastBlogPost');
		if($data===false) {
			$data = '';
			//$arr = $db->getCol("SELECT max(ItemId) as maxid FROM sys_pages_items where typeId='blog' and itemIdTop is null group by pageId order by dateCreated desc limit 0,10");
			$arr = FDBTool::getCol("SELECT itemId FROM sys_pages_items where public = 1 and typeId='blog' and itemIdTop is null order by dateCreated desc limit 0,10");
			if(!empty($arr)) {
				$fItems = new fItems();
				$fItems->showPageLabel = true;
				$fItems->initData('blog',$user->userVO->userId,true);
				//$fItems->addWhere('itemIdTop is null');
				$fItems->addWhere('i.itemId in ('.implode(',',$arr).')');
				$fItems->addOrder('i.dateCreated desc');
				$fItems->getData(0,5);
				$firstPost = true;
				while($fItems->arrData) {
					$fItems->parse();
					if($firstPost==true) {
						$firstPostStr = $fItems->show();
						$firstPost=false;
					}
				}
				$data = $firstPostStr . $firstPostSeparator . $fItems->show();
			}
			$cache->setData($data);
		}

		if(!empty($data)) {
			list($firstPostStr,$restPosts) = explode($firstPostSeparator,$data);
			if(!empty($firstPostStr)) $tpl->setVariable('LASTBLOGPOST',$firstPostStr);
			if(!empty($restPosts)) $tpl->setVariable('LASTBLOGPOSTS',$restPosts);
		}

		//------LAST-CREATED-PAGES
		if(($tmptext = $cache->getData('lastCreated')) !== false) {
			$fPages = new fPages(array('blog','galery','forum'),$user->userVO->userId);
			$fPages->setOrder('p.dateCreated desc');
			$fPages->addWhere('p.locked < 2');
			$fPages->setLimit(0,5);
			$fPages->setSelect('p.pageId,p.typeId,p.name,p.description');
			$arr = $fPages->getContent();
			while($arr) {
				$row = array_shift($arr);
				$tpl->setCurrentBlock('newpage');
				$tpl->setVariable('NEWPAGEURL','?k='.$row[0]);
				$tpl->setVariable('NEWPAGETITLE',fSystem::textins($row[3],array('plainText'=>1)));
				$tpl->setVariable('NEWPAGETEXT',$row[2].' ['.$TYPEID[$row[1]].']');
				$tpl->parseCurrentBlock();
			}
			$cache->setData( $tpl->get('newpage') );
		} else {
			$tpl->setVariable('NEWPAGECACHED',$tmptext);
		}
		//------MOST-VISITED-PAGES
		if(($tmptext = $cache->getData('mostVisited')) !== false) {
			$arr = $db->getCol("select pageId from sys_pages_counter where dateStamp > date_sub(now(), interval 1 week) and typeId in ('galery','forum','blog') group by pageId order by sum(hit) desc limit 0,10");
			//---cache result
			$x = 0;
			while($arr && $x < 6) {
				$pageId = array_shift($arr);
				if(fRules::get($user->userVO->userId,$pageId)) {
					$row = $db->getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
					$tpl->setCurrentBlock('mostvisitedpage');
					$tpl->setVariable('MOSTVISITEDEURL','?k='.$row[0]);
					$tpl->setVariable('MOSTVISITEDTITLE',fSystem::textins($row[3],array('plainText'=>1)));
					$tpl->setVariable('MOSTVISITEDTEXT',$row[2].' ['.$TYPEID[$row[1]].']');
					$tpl->parseCurrentBlock();
					$x++;
				}
			}
			$cache->setData($tpl->get('mostvisitedpage'));
		} else {
			$tpl->setVariable('MOSTVISITEDECACHED',$tmptext);
		}

		//------MOST-ACTIVE-PAGES
		if(($tmptext = $cache->getData('mostActive')) !== false) {
			$arr = $db->getCol("select pageId from sys_pages_counter where dateStamp > date_sub(now(), interval 1 week) and typeId in ('galery','forum','blog') group by pageId order by sum(ins) desc limit 0,10");
			//---cache result
			$x = 0;
			while($arr && $x < 6) {
				$pageId = array_shift($arr);
				if(fRules::get($user->userVO->userId,$pageId)) {
					$row = $db->getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
					$tpl->setCurrentBlock('mostactivepage');
					$tpl->setVariable('MOSTACTIVEURL','?k='.$row[0]);
					$tpl->setVariable('MOSTACTIVETITLE',fSystem::textins($row[3],array('plainText'=>1)));
					$tpl->setVariable('MOSTACTIVETEXT',$row[2].' ['.$TYPEID[$row[1]].']');
					$tpl->parseCurrentBlock();
					$x++;
				}
			}
			$cache->setData($tpl->get('mostactivepage'));
		} else {
			$tpl->setVariable('MOSTACTIVECACHED',$tmptext);
		}

		//------MOST-FAVOURITE-PAGES
		if(($tmptext = $cache->getData('mostFavourite')) !== false) {
			$arr = $db->getCol("select pageId from sys_pages_favorites where book=1 group by pageId order by sum(book) desc limit 0,10");
			//---cache result
			$x = 0;
			while($arr && $x < 6) {
				$pageId = array_shift($arr);
				if(fRules::get($user->userVO->userId,$pageId)) {
					$row = $db->getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
					$tpl->setCurrentBlock('mostfavouritepage');
					$tpl->setVariable('MOSTFAVOURITEURL','?k='.$row[0]);
					$tpl->setVariable('MOSTFAVOURITETITLE',fSystem::textins($row[3],array('plainText'=>1)));
					$tpl->setVariable('MOSTFAVOURITETEXT',$row[2].' ['.$TYPEID[$row[1]].']');
					$tpl->parseCurrentBlock();
					$x++;
				}
			}
			$cache->setData($tpl->get('mostfavouritepage'));
		} else {
			$tpl->setVariable('MOSTFAVOURITECACHED',$tmptext);
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
	}
	
}