<?php
class fPocket extends fQueryTool {
    private $userId;
    private $template = 'sidebar.user.pocket.tpl.html';
    function __construct($userId) {
        global $db;
        $this->userId = $userId;
        parent::__construct('sys_users_pocket as p','p.pocketId',$db);
    }
    function __destruct() {
        
    }
    static function getLink($itemId,$page=false) {
        return '<a href="?'.(($page)?('k='.$itemId.'&p=a'):('i='.$itemId.'&p=p')).'" class="pocketAdd">'.LABEL_POCKET_PUSH.'</a>';
    }
    function getItems() {
        
        $this->setSelect("p.pocketId,p.userId,p.pageId,p.itemId,p.description,pa.name,i.typeId,i.addon,i.text,i.name,i.enclosure");
        $this->addJoin("join sys_pages as pa on pa.pageId=p.pageId");
        $this->addJoin("left join sys_pages_items as i on i.itemId=p.itemId");
        $this->setWhere("p.userId='".$this->userId."'");
        $this->setOrder("p.dateCreated desc");
        return $this->getContent();
    }
    function saveItem($itemId,$page=false,$description='') {
        //TODO:---delete cache
        if(empty($page)) {
            $testDot = "select count(1) from sys_users_pocket where userId='".$this->userId."' and itemId='".$itemId."'";
            $pageId = $this->db->getOne("select pageId from sys_pages_items where itemId='".$itemId."'");
        } else {
            $testDot = "select count(1) from sys_users_pocket where userId='".$this->userId."' and pageId='".$itemId."' and itemId is null";
            $pageId = $itemId;
            $itemId = 0;
        }
        $isInPocket = $this->db->getOne($testDot);
        if($isInPocket == 0 && !empty($pageId)) {
            $dot = "INSERT INTO sys_users_pocket (userId,pageId,itemId,description,dateCreated)
                VALUES ('".$this->userId."', '".$pageId."', ".(($itemId>0)?("'".$itemId."'"):('null')).", '".$description."', NOW());";
            return $this->db->query($dot);
        }
    }
    function removeItem($pocketId) {
        //TODO:---delete cache
        return $this->db->query("delete from sys_users_pocket where pocketId='".$pocketId."'");
    }
    function clear() {
        //TODO:---delete cache
        return $this->db->query("delete from sys_users_pocket where userId='".$this->userId."'");
    }
    function checkPocketItem($pocketId,$userId) {
        return $this->db->getOne("select count(1) from sys_users_pocket where pocketId='".$pocketId."' and userId='".$userId."'");
    }
    function action($action,$pocketId) {
        global $user;
        if($this->checkPocketItem($pocketId,$user->gid)) {
            if($action=='r') $this->removeItem($pocketId);
        }
    }
    function show($xajax=false) {
      global $user;
        $nameLength = 10;
        $ret = '';
        //$this->debug = 1;
        $arr = $this->getItems();
        
        $tpl = new fTemplateIT($this->template);
        
        if(!empty($arr)) {
            //TODO:
            //---parse
            
            foreach ($arr as $item) {
            	$tpl->setCurrentBlock('pocketitem');
            	$tpl->setVariable('PAGELINK','?k='.$item[2]);
            	$substr_name = (strlen($item[5])>$nameLength)?(mb_substr($item[5],0,$nameLength).'...'):($item[5]);
            	$tpl->setVariable('PAGENAME',$substr_name);
            	$tpl->setVariable('PAGETITLE',$item[5]);
            	
            	if(!empty($item[3])) {
            	    //---item - typeId - index 6
            	    //--- typeId - blog - name=addon - ind 7
            	    //--- typeId - event - name=addon - ind 7
            	    //--- typeId - galery - name=enclosure - ind 10
            	    //--- typeId - forum - name=text - ind 8
            	   $tpl->setVariable('ITEMLINK','?i='.$item[3]);
            	   $typeId = $item[6];
            	   if($typeId=='forum') $index = 8;
            	   elseif ($typeId=='galery')  $index = 10;
            	   else $index = 7;
            	   $substr_name = (strlen($item[$index])>$nameLength)?(mb_substr(fSystem::textins($item[$index],0,0),0,$nameLength).'...'):($item[5]);
            	   $tpl->setVariable('ITEMNAME',$substr_name);
            	   $tpl->setVariable('ITEMTITLE',fSystem::textins($item[$index],0,0));
            	   $tpl->setVariable('ITEMID',$item[3]);
            	}
            	
            	$tpl->setVariable('USE',$user->getUri('p=u&pi='.((empty($item[3]))?($item[2]):($item[3]))));
            	$tpl->setVariable('REMOVE',$user->getUri('p=r&pi='.$item[0]));
            	
            	$tpl->parseCurrentBlock();
            	
            	if(!empty($item[3])) {
            	 $tpl->setCurrentBlock('pocketitemtooltip');
            	 $tpl->setVariable('ITEMIDTOOLTIP',$item[3]);
            	 $fItems = new fItems();
            	 $fItems->showTag = false;
            	 $fItems->showPocketAdd = false;
            	 $fItems->showComments = false;
            	 $fItems->showTooltip = false;
            	 $fItems->showRating = false;
            	 $fItems->showHentryClass = false;
            	 $fItems->showFooter = false;
            	 $fItems->showHeading = true;
            	 //$fItems->initData($item[6],$user->gid,true);
            	 $fItems->initData('',$user->gid);
            	 $fItems->initDetail($item[3]);
            	 $fItems->getData();
            	 $fItems->parse();
            	 $parsed = $fItems->show();
            	 $tpl->setVariable('ITEMASTOOLTIP',$parsed);
            	 $tpl->parseCurrentBlock();
            	}
            }
            
            //print_r($arr);
            //die();
            //---cache
            
        } else {
            $tpl->touchBlock('pocketempty');
        }
        
        if($xajax===true) {
            $tpl->parse('pocket');
            $ret = $tpl->get('pocket');
        } else {
            $ret = $tpl->get();
        }
        
        return $ret;
    }
}