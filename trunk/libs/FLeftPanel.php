<?php
class FLeftPanel extends FDBTool {
	private $pageId;
	private $pageType;
	private $userId;

	public $panels; //---array of sorted visible panels for given page and user if logged in
	public $panelsUsed; //---list of used panel names

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

		$cache = FCache::getInstance( 'f' );
		if( false === ($arrSidebar = $cache->getData($this->pageId.'-'.($this->userId * 1),'sidebar_set')) ) {

			$this->setSelect("f.functionName,f.name,f.public,f.userId,f.pageId,f.content,f.options,fd.leftpanelGroup,'','',fd.ord,fd.visible");
			$this->addJoin("join sys_leftpanel_defaults as fd on fd.functionName = f.functionName and (fd.leftpanelGroup in ('default','".$this->pageType."'))");
			if(empty($this->userId) && $allForPage === false) $this->addWhere('f.public=1');
			$arrTmp = $this->getContent();
			
			$this->queryReset();
			$this->setSelect("f.functionName,f.name,f.public,f.userId,f.pageId,f.content,f.options,'',fp.pageId,'',fp.ord,fp.visible");
			$this->addJoin("join sys_leftpanel_pages as fp on fp.functionName = f.functionName and fp.pageId='".$this->pageId."'");
			if(empty($this->userId) && $allForPage === false) $this->addWhere('f.public=1');
			$arr2 = $this->getContent();
			if(!empty($arr2)) $arrTmp = array_merge($arrTmp,$arr2);
			
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
					$newRow['options'] = $row[6];

				} else {
					$newRow = $arrGrouped[$row[0]];

					$setByUser = (!empty($newRow['userId'])) ? true : false;
					$setByPage = (!empty($newRow['pageId'])) ? true : false;
					$setByDefault = (!empty($newRow['group'])) ? true : false;

				}

				if(empty($newRow['group']) && !empty($row[7])) $newRow['group'] = $row[6];
				if(empty($newRow['pageId']) && !empty($row[8])) $newRow['pageId'] = $row[7];
				if(empty($newRow['userId']) && !empty($row[9])) $newRow['userId'] = $row[8];

				if(!empty($newRow['group']) && $setByPage===false && $setByUser===false) {
					//---defaults
					$newRow['ord'] = $row[10];
					$newRow['visible'] = $row[11];
					$newRow['origin'] = 'system';
				}

				if(!empty($newRow['pageId']) && $setByUser===false) {
					//---bigger priority
					$newRow['ord'] = $row[10];
					$newRow['visible'] = $row[11];
					$newRow['origin'] = 'page';
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
			$arrSidebar = array('arrFinal'=>$arrFinal,'arrUsed'=>$arrUsed);
			$cache->setData( $arrSidebar );
		}

		//---sorted visible panels for given page and user if logged in
		$this->panels = $arrSidebar['arrFinal'];
		$this->panelsUsed = $arrSidebar['arrUsed'];
	}

	static function getParam( $key, $options ) {
		$ret = false;
		if(strpos($options,$key)!==false) {
			$arr = explode(';',$options);
			if(!empty($arr)) {
				foreach($arr as $v) {
					$row = explode('=',$v);
					if(count($row)>1) {
						if($row[0]==$key) {
							$ret = $row[1];
						}
					}
				}
			}
		}
		return $ret;
	}

	function show() {
		if(!empty($this->panels)) {
			foreach ($this->panels as $panel) {
				if($panel['visible']==1) {
					$fnc = $panel['functionName'];
					$letext = false;
					

						if(!empty($fnc)) {
								
							$showBlock = true;
							$cacheId = 'lp';
							$cacheGrp = $fnc;

							if(strpos($panel['options'],'nocache')===false) {
									
								//---member/non-member dependant block
								if($panel['public']==0 || strpos($panel['options'],'member')!==false) {
									$cacheId .= '-'.(($this->userId>0)?('1'):('0')).'-member';
								}
									
								//---pageId dependant block
								if(strpos($panel['options'],'page')!==false) {
									if(!isset($user)) $user = FUser::getInstance();
									if($user->pageAccess===false) {
										//---do not display block
										$showBlock = false;
									}
									$cacheId .= '-'.($user->pageId).'-page';
								}
									
								//---userId dependant block
								if(strpos($panel['options'],'user')!==false) {
									$cacheId .= '-'.($this->userId*1).'-user';
								}
									
								if(($lifeTime=FLeftPanel::getParam( 'lifeTime' ,$panel['options'] ))!==false) {
									$cache = FCache::getInstance('f', (int) $lifeTime);
								} else {
									$cache = FCache::getInstance('f');
								}
								
								//---try cache
								if($showBlock===true) {
									$letext=$cache->getData($cacheId,$cacheGrp);
								}
							}
									
							if($showBlock === true) {
								if($letext === false) {
									include('FLeftPanel/'.$fnc.'.php');
									$letext = call_user_func(array($fnc, 'show'));
									if(isset($cache)) {
										$cache->setData($letext,$cacheId,$cacheGrp);
										unset($cache);
									}
								}
							}
							
						} else {
							$letext = $panel['content'];
						}

					if(!empty($letext)) {
						$TOPTPL = FBuildPage::getInstance();
						if(!empty($panel['name']))$TOPTPL->setVariable('SIDEBARHEAD',$panel['name']);
						$TOPTPL->setVariable('SIDEBARBLOCKID',$fnc);
						$TOPTPL->setVariable('SIDEBARDATA',$letext);
						$TOPTPL->parse('sidebar-block');
					}
				}
			}
		}
	}



}
