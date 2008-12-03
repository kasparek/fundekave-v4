<?php
$tpl = new fTemplateIT('maina.tpl.html');
//--------------LAST-FORUM-POSTS
$data = $user->cacheGet('lastForumPost');
if(!$data) {
  $fItems = new fItems();
  $arr = $db->getCol("SELECT max(ItemId) as maxid FROM sys_pages_items where typeId='forum' group by pageId order by maxid desc limit 0,6");
  $strItemId = implode(',',$arr);
  $fItems->showPageLabel = true;
  $fItems->initData('forum',$user->gid,true);
  $fItems->addWhere('i.itemId in ('.$strItemId.')');
  $fItems->addOrder('i.dateCreated desc');
  //$fItems->setGroup('i.pageId');
  $fItems->getData(0,3);
  while($fItems->arrData) $fItems->parse();
  $data = $fItems->show();
  $user->cacheSave($data);
}
if(!empty($data)) $tpl->setVariable('LASTFORUMPOSTS',$data);
//---------------LAST-BLOG-POSTS
$data = false;
$firstPostSeparator = ';|||;';
$data = $user->cacheGet('lastBlogPost');
if(!$data) {
  //$arr = $db->getCol("SELECT max(ItemId) as maxid FROM sys_pages_items where typeId='blog' and itemIdTop is null group by pageId order by dateCreated desc limit 0,10");
  $arr = $db->getCol("SELECT itemId FROM sys_pages_items where typeId='blog' and itemIdTop is null order by dateCreated desc limit 0,10");
  $fItems = new fItems();
  $fItems->showPageLabel = true;
  $fItems->initData('blog',$user->gid,true);
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
  $user->cacheSave($data);
}
if(!empty($data)) {
  list($firstPostStr,$restPosts) = explode($firstPostSeparator,$data);  
  if(!empty($firstPostStr)) $tpl->setVariable('LASTBLOGPOST',$firstPostStr);
  if(!empty($restPosts)) $tpl->setVariable('LASTBLOGPOSTS',$restPosts);
}
//------LAST-CREATED-PAGES
if(!$tmptext = $user->cacheGet('userBasedMedium','lastCreated')) {
    $fPages = new fPages(array('blog','galery','forum'),$user->gid,&$db);
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
    $user->cacheSave($tpl->get('newpage'));
} else {
    $tpl->setVariable('NEWPAGECACHED',$tmptext);
}
//------MOST-VISITED-PAGES
if(!$tmptext = $user->cacheGet('userBasedMedium','mostVisited')) {
    $arr = $db->getCol("select pageId from sys_pages_counter where typeId in ('galery','forum','blog') group by pageId order by dateStamp desc, sum(hit) desc limit 0,10");
    //---cache result
    $x = 0;
    while($arr && $x < 6) {
        $x++;
        $pageId = array_shift($arr);
        if(fRules::get($user->gid,$pageId)) {
            $row = $db->getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
            $tpl->setCurrentBlock('mostvisitedpage');
            $tpl->setVariable('MOSTVISITEDEURL','?k='.$row[0]);
            $tpl->setVariable('MOSTVISITEDTITLE',fSystem::textins($row[3],array('plainText'=>1)));
            $tpl->setVariable('MOSTVISITEDTEXT',$row[2].' ['.$TYPEID[$row[1]].']');
            $tpl->parseCurrentBlock();
        }
    }
    $user->cacheSave($tpl->get('mostvisitedpage'));
} else {
    $tpl->setVariable('MOSTVISITEDECACHED',$tmptext);
}

//------MOST-ACTIVE-PAGES
if(!$tmptext = $user->cacheGet('userBasedMedium','mostActive')) {
    $arr = $db->getCol("select pageId from sys_pages_counter where typeId in ('galery','forum','blog') group by pageId order by dateStamp desc, sum(ins) desc limit 0,10");
    //---cache result
    $x = 0;
    while($arr && $x < 6) {
        $x++;
        $pageId = array_shift($arr);
        if(fRules::get($user->gid,$pageId)) {
            $row = $db->getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
            $tpl->setCurrentBlock('mostactivepage');
            $tpl->setVariable('MOSTACTIVEURL','?k='.$row[0]);
            $tpl->setVariable('MOSTACTIVETITLE',fSystem::textins($row[3],array('plainText'=>1)));
            $tpl->setVariable('MOSTACTIVETEXT',$row[2].' ['.$TYPEID[$row[1]].']');
            $tpl->parseCurrentBlock();
        }
    }
    $user->cacheSave($tpl->get('mostactivepage'));
} else {
    $tpl->setVariable('MOSTACTIVECACHED',$tmptext);
}

//------MOST-FAVOURITE-PAGES

if(!$tmptext = $user->cacheGet('userBasedMedium','mostFavourite')) {
    $arr = $db->getCol("select pageId from sys_pages_favorites where book=1 group by pageId order by sum(book) desc limit 0,10");
    //---cache result
    $x = 0;
    while($arr && $x < 6) {
        $x++;
        $pageId = array_shift($arr);
        if(fRules::get($user->gid,$pageId)) {
            $row = $db->getRow("select p.pageId,p.typeId,p.name,p.description from sys_pages as p where p.pageId='".$pageId."'");
            $tpl->setCurrentBlock('mostfavouritepage');
            $tpl->setVariable('MOSTFAVOURITEURL','?k='.$row[0]);
            $tpl->setVariable('MOSTFAVOURITETITLE',fSystem::textins($row[3],array('plainText'=>1)));
            $tpl->setVariable('MOSTFAVOURITETEXT',$row[2].' ['.$TYPEID[$row[1]].']');
            $tpl->parseCurrentBlock();
        }
    }
    $user->cacheSave($tpl->get('mostfavouritepage'));
} else {
    $tpl->setVariable('MOSTFAVOURITECACHED',$tmptext);
}


$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));