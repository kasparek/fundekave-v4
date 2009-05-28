<?php
class FPocket extends FDBTool {
    private $userId;
    private $template = 'sidebar.user.pocket.tpl.html';
    function __construct($userId) {
        $this->userId = $userId;
        parent::__construct('sys_users_pocket as p','p.pocketId');
    }
    function __destruct() {
        
    }
    static function getLink($itemId,$page=false) {
        $conf = FConf::getInstance();
        if($conf["pocket"]["enabled"] == 1)
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
            $pageId = $this->getOne("select pageId from sys_pages_items where itemId='".$itemId."'");
        } else {
            $testDot = "select count(1) from sys_users_pocket where userId='".$this->userId."' and pageId='".$itemId."' and itemId is null";
            $pageId = $itemId;
            $itemId = 0;
        }
        $isInPocket = $this->getOne($testDot);
        if($isInPocket == 0 && !empty($pageId)) {
            $dot = "INSERT INTO sys_users_pocket (userId,pageId,itemId,description,dateCreated)
                VALUES ('".$this->userId."', '".$pageId."', ".(($itemId>0)?("'".$itemId."'"):('null')).", '".$description."', NOW());";
            return $this->query($dot);
        }
    }
    function removeItem($pocketId) {
        //TODO:---delete cache
        return $this->query("delete from sys_users_pocket where pocketId='".$pocketId."'");
    }
    function clear() {
        //TODO:---delete cache
        return $this->query("delete from sys_users_pocket where userId='".$this->userId."'");
    }
    function checkPocketItem($pocketId,$userId) {
        return $this->getOne("select count(1) from sys_users_pocket where pocketId='".$pocketId."' and userId='".$userId."'");
    }
    function action($action,$pocketId) {
        if($this->checkPocketItem($pocketId,$this->userId)) {
            if($action=='r') $this->removeItem($pocketId);
        }
    }
    function show($xajax=false) {
        $conf = FConf::getInstance();
        if($conf->a["pocket"]["enabled"] == 1) {
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
                    $tpl->setVariable('POCKETLINK',(empty($item[3]))?('?k='.$item[2]):('?i='.$item[3]));
                    $tpl->setVariable('POCKETITEMID',(empty($item[3]))?('pocket'.$item[2]):('pocket'.$item[3]));
                    $nameOnPocket = $item[5];
                    $pocketType = 'page';
                    if(!empty($item[3])) {
                        //---item - typeId - index 6
                        //--- typeId-blog-name=addon [7], event-name=addon [7], galery-name=enclosure [10], forum-name=text [8]
                        $tpl->setVariable('ITEMLINK','?i='.$item[3]);
                        $pocketType = $typeId = $item[6];
                        if($typeId=='forum') $index = 8;
                        elseif ($typeId=='galery')  $index = 10;
                        else $index = 7;
                        $nameOnPocket = $item[$index];
                    }
                    $tpl->setVariable('POCKETTITLE',fSystem::textins($item[5].((isset($index))?(' '.$item[$index]):('')),array('plainText'=>1)));
                    $tpl->setVariable('POCKETNAME',fSystem::textins($nameOnPocket,array('plainText'=>1,'lengthLimit'=>20,'lengthLimitAddOnEnd'=>' ...')));
                    $tpl->setVariable('POCKETTYPE',$pocketType);
                    $tpl->parseCurrentBlock();
    
          $user = FUser::getInstance();
                    $tpl->setCurrentBlock('pocketitemtooltip');
                    $tpl->setVariable('USE',$user->getUri('p=u&pi='.((empty($item[3]))?($item[2]):($item[3]))));
                    $tpl->setVariable('REMOVE',$user->getUri('p=r&pi='.$item[0]));
                    $tpl->setVariable('PAGENAMECOMPLETE',$item[5]);
                    $tpl->setVariable('PAGELINKTOOLTIP','?k='.$item[2]);
                    if(!empty($item[3])) {
    
                        $tpl->setVariable('ITEMIDTOOLTIP','pocket'.$item[3]);
                        $fItems = new FItems();
                        $fItems->showTag = false;
                        $fItems->showPocketAdd = false;
                        $fItems->showComments = false;
                        $fItems->showTooltip = false;
                        $fItems->showRating = false;
                        $fItems->showHentryClass = false;
                        $fItems->showFooter = false;
                        $fItems->showHeading = true;
                        //$fItems->initData($item[6],$user->gid,true);
                        $fItems->initData('',$user->userVO->userId);
                        $fItems->initDetail($item[3]);
                        $fItems->getData();
                        $fItems->parse();
                        $parsed = $fItems->show();
                        $tpl->setVariable('ITEMASTOOLTIP',$parsed);
    
                    } else {
                        $tpl->setVariable('ITEMIDTOOLTIP','pocket'.$item[2]);
                        $tpl->setVariable('PAGEDESC',$item[4]);
                    }
                    $tpl->parseCurrentBlock();
    
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
}