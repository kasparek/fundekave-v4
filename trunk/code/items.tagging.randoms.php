<?php

if(isset($_POST['save'])) {
    if(!empty($_POST['item'])) {
        foreach ($_POST['item'] as $k=>$v) {
        	$v = trim($v);
        	if(!empty($v) && $k>0) {
        	    $arrV = explode(",",$v);
        	    $arrTestedTags = array();
        	    foreach ($arrV as $tag) {
        	    	$tag = fSystem::textins($tag,0,0);
        	    	if(!empty($tag)) $arrTestedTags[] = $tag;
        	    }
        	    if(!empty($arrTestedTags)) { $v = implode(",",$arrTestedTags);
        	       if(fItems::itemExists($k)) $itemTags[$k] = $v;
        	    }
        	}
        }
        if(!empty($itemTags)) {
            //---tags save
            foreach ($itemTags as $k=>$v) {
            	fItems::tag($k,$user->gid,0,$v);
            }
            fHTTP::redirect($user->getUri());
        }
    }
}

$fItems = new fItems();

$fItems->showPageLabel = true;
$fItems->showTag = false;
$fItems->showRating = false;

$fItems->initData('galery',false,true);

$fItems->cacheResults = false;

$fItems->addJoin('left join sys_pages_items_tag as it on it.itemId=i.itemId');
//$fItems->addSelect('(select count(1) from sys_pages_items_tag as att where att.tag is not null and att.itemId=i.itemId) as tags');
$fItems->addSelect('count(it.itemId) as tags');
$fItems->setGroup('i.itemId');
$fItems->setOrder('tags,rand()');

$tpl = new fTemplateIT('items.tagging.tpl.html');

$fItems->setLimit(0,15);

//$fItems->debug = 1;

$fItems->getData();

if(!empty($fItems->arrData)) {
	while ($fItems->arrData) {
        $arr = $fItems->parse(); 
        $tpl->setCurrentBlock('result');
        $tpl->setVariable('ITEMID',$arr['itemId']);
        $tpl->setVariable("ITEM",$fItems->show());
        $tpl->parseCurrentBlock();
	}
}

$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
?>