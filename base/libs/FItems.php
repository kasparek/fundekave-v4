<?php
class FItems extends FDBTool {

	private $typeStrict;

	//---list of ItemVOs
	public $data;

	//---renderer
	public $fItemsRenderer;

	//---using user permissions
	public $userIdForPageAccess = false;
	private $access = true;

	//---items removed because no access
	public $itemsRemoved = 0;

	//---options
	public $thumbInSysRes = false;

	/**
	 *  userId=false - no restrictions applied, userId=0 - only public
	 **/
	function __construct($typeId='',$userId=false,$itemRenderer=null) {
		parent::__construct('sys_pages_items','itemId');
		$this->VO = 'ItemVO';
		$this->fetchmode = 1;

		$itemVO = new ItemVO();
		$this->columns = $itemVO->getColumns();
		//---set select
		foreach($this->columns as $k=>$v) {
			if(strpos($v,' as ')===false) $v .= ' as '.$k;
			$columnsAsed[]=$v;
		}
		$this->setSelect( $columnsAsed );
		if($itemRenderer) $this->fItemsRenderer = $itemRenderer;
		
		$this->setup($typeId,$userId);
	}
	
	function setup($typeId='',$userId=false) {
		$this->typeStrict = $typeId;
		$this->userIdForPageAccess = $userId;
		//validate input types
		if(!empty($typeId)) {
			if(!is_array($typeId)) {
				if(strpos($typeId,',')!==false) {
					$typeId = explode(','); 
				} else {
					$typeId=array($typeId);
				}
			}
			$tValid = array();
			foreach($typeId as $t) {
				if(FItems::isTypeValid($t)) {
					$tValid[]=$t;
				}
			}
			$typeId=implode("','",$tValid);
		}
		
		if($this->userIdForPageAccess!==false) {
			$this->autojoinSet(true);
			$typeWhere='';
			if(!empty($typeId)) {
				if(strpos($typeId,',')===false) {
					$typeWhere=" ".$this->table.".typeId='".$typeId."' and ";
				} else {
					$typeWhere=" ".$this->table.".typeId in ('".$typeId."') and ";
				}
			} else {
				$typeWhere=" ".$this->table.".typeId!='request' and ";
			}
			
			if($userId>0) {
				$pageIdQuery = " (select p.pageId from sys_pages as p left join sys_users_perm as up on up.userId='".$userId."' and up.pageId=p.pageId 
				where ((p.public>0 or up.rules>0) and p.locked<2)"
				.(SITE_STRICT && (empty($userId) || $typeId=='top' || $typeId=='blog') ? " and p.pageIdTop='".SITE_STRICT."' " : " ")
				.")";
			} else {
				$pageIdQuery = " (select p.pageId from sys_pages as p where p.public=1 and p.locked<2"
				.(SITE_STRICT && (empty($userId) || $typeId=='top' || $typeId=='blog') ? " and p.pageIdTop='".SITE_STRICT."' " : " ")
				.")";
			}
			
			/*
			$queryBase = "select {SELECT} from ".$this->table." as ".$this->table
						." join sys_pages as p on p.pageId=".$this->table.".pageId "
						.($userId>0?"left join sys_users_perm as up on up.userId='".$userId."' and up.pageId=".$this->table.".pageId ":' ')
			."{JOIN} where ("
			.$typeWhere
			.(SITE_STRICT && (empty($userId) || $typeId=='top' || $typeId=='blog') ? "p.pageIdTop='".SITE_STRICT."' and " : " ")
			.($userId>0?
				"(".$this->table.".public>0 or (".$this->table.".public=0 and ".$this->table.".userId='".$userId."')) and (p.public>0 or up.rules>0) and p.locked<2 "
				:"p.locked<2 and p.public=1 and ".$this->table.".public = 1 ")
			.') and ({WHERE}) {GROUP} {ORDER} {LIMIT}';
			*/
			
			$queryBase = "select {SELECT} from ".$this->table." as ".$this->table
			."{JOIN} where ("
			.$typeWhere
			.$this->table.".pageId in ".$pageIdQuery . " and "
			.($userId>0?
				"(".$this->table.".public>0 or (".$this->table.".public=0 and ".$this->table.".userId='".$userId."')) "
				:" ".$this->table.".public = 1 ")
			.') and ({WHERE}) {GROUP} {ORDER} {LIMIT}';
			
			
			$this->setTemplate($queryBase);
		}
	}

	function __destruct() {
	 unset($this->fItemsRenderer);
	}

	static function isTypeValid($type) {
		$types = array('forum','galery','blog','event','request');
		return in_array($type, $types);
	}

	function setPage($pageId) {
		$this->addWhere("sys_pages_items.pageId='".$pageId."'");
		if($this->userIdForPageAccess !== false) {
			if(FRules::get($this->userIdForPageAccess,$pageId)===false) {
				//have no access for this page so no items
				$this->access = false;
				return false;
			} else {
				$this->userIdForPageAccess = false;
			}
		}
		return true;
	}

	function hasReactions($value=false) {
		if($value===false) {
			$this->addWhere("(sys_pages_items.itemIdTop is null or sys_pages_items.itemIdTop=0)");
		}
	}

	function total() {
		return count($this->data);
	}

	var $typeLimit;
	var $typeLimitCount;
	function setTypeLimit($typeId,$num) {
		$this->typeLimit[$typeId] = $num;
	}
		
	function getList($from=0, $count=0) {
		$this->data = array();

		if($this->access===false) $this->data;

		$arr = $this->getContent($from, $count);
		
/*		
		if($this->userIdForPageAccess === false) {
			$arr = $this->getContent($from, $count);
		} else {
			$itemsCount = 0;
			$page = 0;
			$arr = array();
			$prevItems = 0;
			$typeLimitCount = array();
			
			$this->cacheResults='f';
			
			while(count($arr) < $count || $count==0) {
				$arrTmp = $this->getContent($page*($count*10), $count*10);
				$page++;
				if(empty($arrTmp)) break; //---no records
				else {
					$this->itemsRemoved = 0;
					foreach($arrTmp as $row) {
						if(!isset($typeLimitCount[$row->pageId])) {
							$typeLimitCount[$row->pageId]=0;
						}
						$includeItem = true;
						//if(FRules::get($this->userIdForPageAccess,$row->pageId)) $includeItem = true;
						
						if($includeItem) {
							//check limits for types
							if(isset($this->typeLimit[$row->typeId]) && empty($row->itemIdTop)) {
								if($typeLimitCount[$row->pageId] > $this->typeLimit[$row->typeId]) {
									$includeItem = false; 
								} else {
									$typeLimitCount[$row->pageId]++;
								}
							}
						}
						
						if($includeItem) {
							if($prevItems >= $from) {
								$arr[] = $row;
								$itemsCount++;
								if($itemsCount == $count && $count!=0) break;
							} else {
								$prevItems++;
							}
						} else {
							//not permission for post
							$this->itemsRemoved++;
						}
					}
				}
				//---we have got all in once
				if($count == 0) break;
			}
		}
*/
		if(!empty($arr)) {

			$this->data = $arr;

			foreach($this->data as $itemVO) {
				$itemVO->thumbInSysRes = $this->thumbInSysRes;
				$itemIdList[] = $itemVO->itemId;
			}

			$q = "select itemId,name,value from sys_pages_items_properties where itemId in (".implode(',',$itemIdList).")";
			$props = FDBTool::getAll($q);
				
			foreach($this->data as $k=>$itemVO) {
				$invalidate = false;
				if(!empty($props)) {
					while($prop = array_pop($props)) {
						if($prop[0] == $itemVO->itemId) {
							$itemVO->properties->{$prop[1]} = $prop[2];
							$invalidate = true;
						} else {
							$propsRest[]=$prop;
						}
					}
					if(!empty($propsRest)) {
						$props=$propsRest;
						$propsRest=array();
					}
				}
				$propList = $itemVO->getPropertiesList();
				
				foreach($propList as $prop) {
					if(!isset($itemVO->properties->$prop)) {
						$itemVO->properties->$prop = false;
						$invalidate = true;
					}
				}
				$itemVO->prepare();
				$this->data[$k] = $itemVO;
			}
		}
		FProfiler::write('FItems::getList--DATA LOADED');
		return $this->data;
	}

	function parse($itemVO) {
		if(!$this->fItemsRenderer) $this->fItemsRenderer = new FItemsRenderer();
		//---render item
		$itemVO->render($this->fItemsRenderer,false);
		FProfiler::write('FItems::parse--ITEM PARSED');
	}

	function show() {
		return $this->fItemsRenderer->show();
	}

	function render($from=0, $perPage=0) {
		if(empty($this->data)) $this->getList($from, $perPage);
		if(empty($this->data)) return false;
		//---items parsing
		while ($itemVO = array_shift($this->data)) {
			$this->parse($itemVO);
		}
		$ret = $this->show();
		return $ret;
	}

	/**
	 * set unreaded items to cache
	 * */
	static function cacheUnreadedList() {
		$unreadedCnt = 0;
		$user = FUser::getInstance();
		if($user->idkontrol==false) return 0;
		if(empty($user->itemVO)) {
			$unreadedNum = $user->pageVO->unreaded;
		} else $unreadedNum = $user->itemVO->unreaded;
		if(empty($unreadedNum)) return 0;
		$itemId = 0;
		if($user->itemVO) $itemId = $user->itemVO->itemId;
		$q = "select itemId,public from sys_pages_items where pageId='".$user->pageVO->pageId."'".(($itemId>0)?(" and itemIdTop='".$itemId."'"):(" and (itemIdTop is null or itemIdTop=0)"))." order by itemId desc limit 0,".$unreadedNum;
		$arr = FDBTool::getAll($q);
		if(empty($arr)) return 0;
		$cache = FCache::getInstance( 's' );
		$unreadedList = &$cache->getPointer('unreadedItems');
		if(empty($unreadedList)) $unreadedList = array();
		//add to unreaded list
		foreach($arr as $row) if($row[1]==1) {
			$unreadedCnt++;
			if(!in_array($row[0],$unreadedList)) $unreadedList[] = $row[0];
		}
		return $unreadedCnt;
	}


	/**
	 * check if item exists
	 */
	static function itemExists($itemId) {
		$q = "select count(1) from sys_pages_items where itemId='".$itemId."'";
		return FDBTool::getOne($q,$itemId.'exist','fitems','l');
	}
}