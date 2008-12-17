<?php
class fLeftPanel extends fQueryTool {
  private $pageId;
  private $pageType;
  private $pageTypeChild;
  private $userId;
  
  private $panels; //---array of sorted visible panels for given page and user if logged in
  
  function __construct($pageId,$userId='0',$pageType='top',$pageTypeChild='') {
    global $db;
    parent::__construct('sys_leftpanel_functions as f','f.functionName',$db);
    $this->pageId = $pageId;
    $this->pageType = $pageType;
    $this->pageTypeChild = $pageTypeChild;
    $this->userId = $userId;
  }
  /**
   * do the actions and save to db
   *
   * @param String(5) $pageId - page to change settings
   * @param String(*) $functionName - function to change
   * @param String(1) $action - u-move up,d-move down,m-toggle minimize
   * @param int() $userId - if 0 chage setting for a page
   */
  static function processAction($pageId,$functionName,$action,$userId=0) {
      global $db;
      
  }
  function load() {
    $this->setSelect("f.functionName,f.name,f.public,f.userId,f.content,fd.leftpanelGroup,fp.pageId,fu.userId");
    $this->addSelect("fd.ord,fd.visible,fd.minimized");
    $this->addSelect("fp.ord,fp.visible,fp.minimized");
    
    $this->addJoin("left join sys_leftpanel_defaults as fd on fd.functionName = f.functionName and (fd.leftpanelGroup='default' or fd.leftpanelGroup='".$this->pageType."' or fd.leftpanelGroup='".$this->pageTypeChild."')");
    $this->addJoin("left join sys_leftpanel_pages as fp on fp.functionName = f.functionName and fp.pageId='".$this->pageId."'");
    if($this->userId > 0) {
      $this->addJoin("left join sys_leftpanel_users as fu on fu.functionName = f.functionName and fu.pageId='".$this->pageId."' and fu.userId='".$this->userId."'");
      $this->addSelect("fu.ord,fu.minimized");
    } else {
      $this->addWhere('f.public=1');
      $fQuery->replaceSelect('fu.userId','0');
    }
    $arrTmp = $this->getContent();
    //---group
    foreach($arrTmp as $row) {
      $setByUser = false;
      $setByPage = false;
      $setByDefault = false;
      
      if(!isset($arrGrouped[$row[0]])) { 
      
        $newRow['functionName'] = $row[0];
        $newRow['name'] = $row[1];
        $newRow['public'] = $row[2];
        $newRow['userIdOwner'] = $row[3];
        $newRow['content'] = $row[4];
        
      } else {
      
        $setByUser = (!empty($newRow['userId'])) ? true : false;
        $setByPage = (!empty($newRow['pageId'])) ? true : false;
        $setByDefault = (!empty($newRow['group'])) ? true : false;
        
      }
      
      if(empty($newRow['group']) && !empty($row[5])) $newRow['group'] = $row[5];
      if(empty($newRow['pageId']) && !empty($row[6])) $newRow['pageId'] = $row[6];
      if(empty($newRow['userId']) && !empty($row[7])) $newRow['userId'] = $row[7];
      
      if(!empty($newRow['group']) && $setByPage===false && $setByUser===false) {
        //---defaults
        $newRow['ord'] = $row[8];
        $newRow['visible'] = $row[9];
        $newRow['minimized'] = $row[10];
      }
      
      if(!empty($newRow['pageId']) && $setByUser===false) {
        //---bigger priority
        $newRow['ord'] = $row[11];
        $newRow['visible'] = $row[12];
        $newRow['minimized'] = $row[13];
      }
      
      //---biggest priority
      if(!empty($newRow['userId'])) {
        $newRow['ord'] = $row[14];
        $newRow['minimized'] = $row[15];
      }
      
      $arrGrouped[$row[0]] = $newRow;
    }
    $arrTmp = array();
    
    //---sort
    foreach($arrGrouped as $row) {
      $arrSorted[$row['ord']] = $row['functionName'];
    }
    ksort($arrSorted);
    //---get panels sorted
    foreach($arrSorted as $functionName) {
      if($arrGrouped[$functionName]['visible']==1) {
        $arrFinal[] = $arrGrouped[$functionName];
      }
    }
    $arrSorted = array();
    $arrGrouped = array();
    
    //---sorted visible panels for given page and user if logged in
    $this->panels = $arrFinal;
  }
  
  function show() {
      if(!empty($this->panels)) {
        	foreach ($this->panels as $panel) {
        		$fnc = $panel['functionName'];
        		$letext = '';
        		if($panel['minimized']!=1) {
            		if(empty($panel['content'])) {
            		  $letext = fLeftPanelPlugins::$fnc();
            		} else {
            		   $letext = $panel['content'];
            		}
        		}
                if(!empty($letext) || $panel['minimized']==1) {
                    global $TOPTPL;
                  $TOPTPL->setCurrentBlock('sidebar-block');
                  //---set buttons - move up, move down, minimize/maximize
                  global $user;
                  $TOPTPL->setVariable('MOVEUP',$user->getUri('b='.$fnc.'&a=u'));
                  $TOPTPL->setVariable('MOVEDOWN',$user->getUri('b='.$fnc.'&a=d'));
                  $TOPTPL->setVariable('MINIMIZE',$user->getUri('b='.$fnc.'&a=m'));
                  
                  if(!empty($panel['name']))$TOPTPL->setVariable('SIDEBARHEAD',$panel['name']);
                  $TOPTPL->setVariable('SIDEBARDATA',$letext);
                  $TOPTPL->parseCurrentBlock();
                }
        	}
        }
  }
  
  
}
