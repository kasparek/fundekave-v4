<?php
class FLeftPanel extends FDBTool {
	private $pageId;
	private $pageType;
	private $userId;

	private $panels; //---array of sorted visible panels for given page and user if logged in
	private $panelsUsed; //---list of used panel names

	function __construct($pageId, $userId='0', $pageType='top') {
		parent::__construct('sys_leftpanel_functions as f','f.functionName');
		$this->pageId = $pageId;
		$this->pageType = $pageType;
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


	}
	function load($allForPage=false) {
		$this->panels = array();
		$this->panelsUsed = array();

		$cache = FCache::getInstance( 's' );
		if( false === ($arrSidebar = $cache->getData($this->pageId.'-page-'.($this->userId*1).'-user','sidebarSet')) ) {

			$this->setSelect("f.functionName,f.name,f.public,f.userId,f.pageId,f.content,fd.leftpanelGroup,'','',fd.ord,fd.visible");
			$this->addJoin("join sys_leftpanel_defaults as fd on fd.functionName = f.functionName and (fd.leftpanelGroup='default' or fd.leftpanelGroup='".$this->pageType."')");
			if(empty($this->userId) && $allForPage === false) $this->addWhere('f.public=1');
			$arrTmp = $this->getContent();
			$this->queryReset();

			$this->setSelect("f.functionName,f.name,f.public,f.userId,f.pageId,f.content,'',fp.pageId,'',fp.ord,fp.visible");
			$this->addJoin("join sys_leftpanel_pages as fp on fp.functionName = f.functionName and fp.pageId='".$this->pageId."'");
			if(empty($this->userId) && $allForPage === false) $this->addWhere('f.public=1');
			$arr2 = $this->getContent();
			if(!empty($arr2)) foreach ($arr2 as $row) $arrTmp[] = $row;
			$this->queryReset();

			if($this->userId > 0) {
				$this->setSelect("f.functionName,f.name,f.public,f.userId,f.pageId,f.content,'','',fu.userId,fu.ord,1,fu.minimized");
				$this->addJoin("join sys_leftpanel_users as fu on fu.functionName = f.functionName and fu.pageId='".$this->pageId."' and fu.userId='".$this->userId."'");
				if(empty($this->userId) && $allForPage === false) $this->addWhere('f.public=1');
				$arr2 = $this->getContent();
				if(!empty($arr2)) foreach ($arr2 as $row) $arrTmp[] = $row;
				$this->queryReset();
			}

			

		$arrGrouped = array();
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
				$newRow['pageIdOrigin'] = $row[4];
				$newRow['content'] = $row[5];

			} else {
				$newRow = $arrGrouped[$row[0]];

				$setByUser = (!empty($newRow['userId'])) ? true : false;
				$setByPage = (!empty($newRow['pageId'])) ? true : false;
				$setByDefault = (!empty($newRow['group'])) ? true : false;

			}

			if(empty($newRow['group']) && !empty($row[6])) $newRow['group'] = $row[6];
			if(empty($newRow['pageId']) && !empty($row[7])) $newRow['pageId'] = $row[7];
			if(empty($newRow['userId']) && !empty($row[8])) $newRow['userId'] = $row[8];

			if(!empty($newRow['group']) && $setByPage===false && $setByUser===false) {
				//---defaults
				$newRow['ord'] = $row[9];
				$newRow['visible'] = $row[10];
				$newRow['origin'] = 'system';
			}

			if(!empty($newRow['pageId']) && $setByUser===false) {
				//---bigger priority
				$newRow['ord'] = $row[9];
				$newRow['visible'] = $row[10];
				$newRow['origin'] = 'page';
			}

			//---biggest priority
			if(!empty($newRow['userId'])) {
				$newRow['ord'] = $row[9];
				$newRow['minimized'] = $row[11];
				$newRow['origin'] = 'user';
			}
			 
			$arrGrouped[$row[0]] = $newRow;

		}

		//---sort
		foreach($arrGrouped as $k=>$row) {
			$arrSorted[$k] = $row['ord'];
		}
		asort($arrSorted,SORT_NUMERIC);
		$arrFunctions = array_keys($arrSorted);
		//---get panels sorted

		foreach($arrFunctions as $functionName) {

			$arrFinal[] = $arrGrouped[$functionName];
			$arrUsed[] = $functionName;

		}
		$arrSidebar=array('arrFinal'=>$arrFinal,'arrUsed'=>$arrUsed);
		$cache->setData( $arrSidebar );
		}

		//---sorted visible panels for given page and user if logged in
		$this->panels = $arrSidebar['arrFinal'];
		$this->panelsUsed = $arrSidebar['arrUsed'];
	}

	function show() {
		if(!empty($this->panels)) {
			foreach ($this->panels as $panel) {
				if($panel['visible']==1) {
					$fnc = $panel['functionName'];
					$letext = '';
					if(empty($panel['minimized'])) {
						if(!empty($fnc)) {
							$cache = FCache::getInstance('f');
							if(false===($letext=$cache->getData($fnc,'lp'))) {
								include('FLeftPanel/'.$fnc.'.php');
								$letext = call_user_func(array($fnc, 'show'));
								$cache->setData($letext); 
							}
						} else {
							$letext = $panel['content'];	
						}
						
					}
					if(!empty($letext) || !empty($panel['minimized'])) {
						$TOPTPL = FBuildPage::getInstance();
						$TOPTPL->setCurrentBlock('sidebar-block');
						//---if login block
						if($fnc == 'rh_login') {
							$TOPTPL->touchBlock('sidebar-block-login');
						}
						//---set buttons - move up, move down, minimize/maximize
						/*
						 * TODO: minimize/maximize script - ajax
						 $TOPTPL->setVariable('MOVEUP',$user->getUri('b='.$fnc.'&amp;a=u'));
						 $TOPTPL->setVariable('MOVEDOWN',$user->getUri('b='.$fnc.'&amp;a=d'));
						 $TOPTPL->setVariable('MINIMIZE',$user->getUri('b='.$fnc.'&amp;a=m'));
						 */
						if(!empty($panel['name']))$TOPTPL->setVariable('SIDEBARHEAD',$panel['name']);
						$TOPTPL->setVariable('SIDEBARBLOCKID',$fnc);
						$TOPTPL->setVariable('SIDEBARDATA',$letext);
						$TOPTPL->parseCurrentBlock();
					}
				}
			}
		}
	}



}
