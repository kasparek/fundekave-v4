<?php
class FItems extends FDBTool {

	const TYPE_DEFAULT = 'forum';

	//---current type
	private $typeId;

	//---list of ItemVOs
	public $data;

	//---renderer
	public $fItemsRenderer;

	//---using user permissions
	private $userIdForPageAccess = false;
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

		if($typeId!='') {
			if(FItems::isTypeValid($typeId)) {
				$this->typeId = $typeId;
				$this->addWhere("typeId='".$typeId."'");
			}
		}

		//---check permissions for given user
		if($userId===0) {
			$this->addJoinAuto('sys_pages','pageId',array(),'join');
			$this->addWhere('sys_pages_items.public=1 and sys_pages.public = 1 and sys_pages.locked<2');
		} elseif($userId > 0) {
			if(!FRules::getCurrent( 2 )) {
				//TODO:public=3 - jen pro pratele - solve performance issues
				//add sys_pages_items.public = 3 and sys_pages_items.userId in (friendsList)
				//---only public item for non-admins
				$this->addWhere('(sys_pages_items.public = 1 or sys_pages_items.public = 2)');
			}
			$this->userIdForPageAccess = $userId;
		}

		if($itemRenderer) $this->fItemsRenderer = $itemRenderer;

		//EXPERIMENTAL
		$byPagePerm=false;
		if($byPagePerm) {
			if($this->permission == 1) {
				if($this->sa === true) {
					$queryBase = "select {SELECT} from ".$this->table." as ".$this->table
					." {JOIN} where 1 ";
				} else if($this->userId == 0) {
					$queryBase = "select {SELECT} from ".$this->table." as ".$this->table
					." {JOIN} where ".$this->table.".public=1 and ".$this->table.".locked<2";
				} else {
					$queryBase = "select {SELECT} from ".$this->table." as ".$this->table
					." left join ".$this->pagesPermissionTableName." as up on "
					.$this->table.".".$this->primaryCol."=up.".$this->primaryCol
					." and up.userId='".$this->userId."' {JOIN} "
					."where (((".$this->table.".public in (0,3) and up.rules >= 1) "
					."or ".$this->table.".userIdOwner='".$this->userId."' "
					."or ".$this->table.".public in (1,2)) and (up.userId is null or up.rules!=0))";
				}
			} else {
				$queryBase = "select {SELECT} from ".$this->table." as ".$this->table
				." left join ".$this->pagesPermissionTableName." as up on "
				.$this->table.".".$this->primaryCol."=up.".$this->primaryCol
				." and up.userId='".$this->userId."' {JOIN} "
				."where ( ".$this->table.".userIdOwner='".$this->userId."' "
				."or (up.userId is not null and up.rules=".$this->permission.") )";
			}

			if(!empty($this->type)) {
				if(!is_array($this->type)) {
					$queryBase.=" and ".$this->table.".typeId='".$this->type."'";
				} else {
					$queryBase.=" and ".$this->table.".typeId in ('".implode("','",$this->type)."')";
				}
			}
			$queryBase .= ' and ({WHERE}) {GROUP} {ORDER} {LIMIT}';
			$this->setTemplate($queryBase);
		}
	}

	function __destruct() {
	 unset($this->fItemsRenderer);
	}

	static function isTypeValid($type) {
		$types = array('forum','galery','blog','event');
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

	function getList($from=0, $count=0) {
		$this->data = array();

		if($this->access===false) $this->data;

		if($this->userIdForPageAccess === false) {
			$arr = $this->getContent($from, $count);
		} else {
			$itemsCount = 0;
			$page = 0;
			$arr = array();

			while(count($arr) < $count || $count==0) {
				$arrTmp = $this->getContent($from + ($page*$count), $count);
				$page++;
				if(empty($arrTmp)) break; //---no records
				else {
					$this->itemsRemoved = 0;
					foreach($arrTmp as $row) {
						//---check premissions
						if(FRules::get($this->userIdForPageAccess,$row->pageId,1)) {
							$arr[] = $row;
							$itemsCount++;
							if($itemsCount == $count && $count!=0) break;
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

		if(!empty($arr)) {

			$this->data = $arr;

			foreach($this->data as $itemVO) {
				$itemVO->thumbInSysRes = $this->thumbInSysRes;
				$itemVO->prepare();
				$itemIdList[] = $itemVO->itemId;
			}

			$q = "select itemId,name,value from sys_pages_items_properties where itemId in (".implode(',',$itemIdList).")";
			$props = FDBTool::getAll($q);
				
			foreach($this->data as $k=>$itemVO) {
				$invalidate = false;
				if(!empty($props)) {
					while($prop = array_pop($props)) {
						if($prop[0] == $itemVO->itemId) {
							$itemVO->properties[$prop[1]] = $prop[2];
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
					if(!isset($itemVO->properties[$prop])) {
						$itemVO->properties[$prop] = false;
						$invalidate = true;
					}
				}
				if($invalidate===true) {
					$itemVO->memStore();
				}
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
	 * chechk if item exists
	 */
	static function itemExists($itemId) {
		$q = "select count(1) from sys_pages_items where itemId='".$itemId."'";
		return FDBTool::getOne($q,$itemId.'exist','fitems','l');
	}
}