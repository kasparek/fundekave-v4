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
						$letext = $panel['content'];
						try {
							$letext = FLeftPanelPlugins::$fnc();
						}
						catch (Exception $e) {

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


	//---edit
	function getAvailablePanels() {
		$user = FUser::getInstance();

		$this->load(true);
		//---1. not used, 2.user has access to use (system panels only sa,other by pageid)
		$this->setSelect('f.functionName,f.name,f.pageId');
		$this->setOrder('f.name');
		$arr = $this->getContent();
		foreach ($arr as $row) {
			if(!in_array($row[0],$this->panelsUsed)) {
				//not used panel
				if(!empty($row[2])) {
					//check for pageid
					if(FRules::get($user->userVO->userId,$row[2],2)) {
						$arrAvailable[] = $row;
					}
				} else {
					//must have sa access
					if(FRules::get($user->userVO->userId,'sadmi',1)) {
						$arrAvailable[] = $row;
					}
				}
			}
		}
		if(!empty($arrAvailable)) return $arrAvailable;
	}
	function panelInsert($type,$functionName,$sequence,$visible=1) {
		 
		if($type=='page') {
			$dot = "insert into sys_leftpanel_pages (pageId,functionName,ord,visible) values ('".$this->pageId."','".$functionName."',".$sequence.",".$visible.")";
		}
		if(!empty($dot)) {
			return $this->query( $dot );
		}
	}
	function panelUpdate($type,$functionName,$arr) {

		//---get current settings
		foreach ($this->panels as $panel) {
			if($panel['functionName']==$functionName) {
				$currentPanel = $panel;
				break;
			}
		}
		if($type=='page') {
			$update = false;
			//---check sequence
			if(isset($arr['sequence'])) {
				$sequence = (int) $arr['sequence'];
				if($sequence != $panel['ord']) {
					$currentPanel['ord'] = $sequence;
					$update = true;
				}
			}
			if(isset($arr['visible'])) {
				if($arr['visible']==0) {
					$currentPanel['visible'] = 0;
					$update = true;
				}
			}
			if(isset($arr['delete'])) {
				if($arr['delete']==0) {
					$currentPanel['delete'] = 'page';
					$update = true;
				}
			}

			if($update===true) {
				if($currentPanel['origin']=='system') {
					//---insert
					if(!isset($currentPanel['visible'])) $currentPanel['visible'] = 1;
					$this->panelInsert('page',$functionName,$currentPanel['ord'],$currentPanel['visible']);
				} else {
					//---update
					if(isset($currentPanel['delete'])) {
						$this->query("delete from sys_leftpanel_pages where pageId='".$this->pageId."' and functionName='".$functionName."'");
					} else {
						$this->query("update sys_leftpanel_pages set sequence=".$currentPanel['ord'].",visible=".$currentPanel['visible']." where pageId='".$this->pageId."' and functionName='".$functionName."'");
					}
				}
			}

		}
	}
	function process($postArr) {
		$this->load(true);
		foreach ($postArr as $k=>$panel) {
			if($k=='new') {
				if(!empty($panel['name'])) {
					//---add new panel
					$this->panelInsert('page',$panel['name'],$panel['sequence']);
				}
			} else {
				//---update
				$this->panelUpdate('page',$k,$panel);
			}
		}

	}
	function showEdit() {

		$tpl = new FTemplateIT('leftpanel.page.set.tpl.html');

		$this->load(true);


		foreach ($this->panels as $panel) {
			$tpl->setCurrentBlock('panelrow');
			$tpl->setVariable('NAMESYS',$panel['functionName']);
			$tpl->setVariable('NAME',$panel['name']);
			$tpl->setVariable('LEFTID',$panel['functionName']);
			$tpl->setVariable('SEQUENCE',$panel['ord']);
			if($panel['origin']!='system') {
				$tpl->setVariable('DELETEID',$panel['functionName']);
				$tpl->touchBlock('visible'.($panel['visible']*1));
			} else {
				$tpl->setVariable('VISIBLEID',$panel['functionName']);
				if($panel['visible']==0) $tpl->touchBlock('invisible');
			}
			$tpl->parseCurrentBlock();
		}

		$arr = $this->getAvailablePanels();
		if(!empty($arr)) {
			$tpl->setVariable('AVAILABLEPANELS',FSystem::getOptions($arr,'',true,''));
		}

		return $tpl->get();
	}

}
