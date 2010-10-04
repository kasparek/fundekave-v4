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
	private $byPermissions = false;
	private $access = true;

	//---items removed because no access
	public $itemsRemoved = 0;

	//---options
	public $thumbInSysRes = false;


	function __construct($typeId='',$byPermissions=false,$itemRenderer=null) {
		parent::__construct('sys_pages_items','itemId');
		$this->VO = 'ItemVO';
		$this->fetchmode = 1;
		if($typeId!='') $this->typeId = $typeId;

		$itemVO = new ItemVO();
		$this->columns = $itemVO->getColumns();

		$this->initList($this->typeId,$byPermissions);

		if($itemRenderer) $this->fItemsRenderer = $itemRenderer;
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
		if($this->byPermissions !== false) {
			if(FRules::get($this->byPermissions,$pageId)===false) {
				//have no access for this page so no items
				$this->access = false;
				return false;
			} else {
				$this->byPermissions = false;
			}
		}
		return true;
	}

	function hasReactions($value=false) {
		if($value===false) {
			$this->addWhere("(itemIdTop is null or itemIdTop=0)");
		}
	}

	function initList($typeId='', $byPermissions = false) {

		$this->queryReset();
		if($typeId!='') {
			if(FItems::isTypeValid($typeId)) {
				$this->typeId = $typeId;
				$this->addWhere("typeId='".$typeId."'");
			}
		}
		$doPagesJoin = true;

		//---check permissions for given user
		if($byPermissions===-1) {
			$this->addWhere('sys_pages_items.public = 1');
		} else if($byPermissions!==false) {
			$this->byPermissions = $byPermissions;
		}

		if($byPermissions !== -1 && !FRules::getCurrent( 2 )) { //---check for public
			$this->addWhere('sys_pages_items.public > 0');
		}
			
		//---set select
		foreach($this->columns as $k=>$v) {
			if(strpos($v,' as ')===false) $v .= ' as '.$k;
			$columnsAsed[]=$v;
		}
		$this->setSelect( $columnsAsed );

	}

	function total() {
		return count($this->data);
	}

	function getList($from=0, $count=0) {
		$this->data = array();

		if($this->access===false) $this->data;

		if($this->byPermissions === false) {
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
						if(FRules::get($this->byPermissions,$row->pageId,1)) {
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
					$i = count($props)-1;
					while(count($props)>0 && $i>=0) {
						if($props[$i][0]==$itemVO->itemId) {
							$prop = array_pop($props);
							$itemVO->properties[$prop[1]] = $prop[2];
							$invalidate = true;
						}
						$i--;
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

		if($this->debug==1) print_r($this->data);
		FProfiler::write('FItems::getList--DATA LOADED');
		return $this->data;
	}

	function parse() {
		if(!$this->fItemsRenderer) $this->fItemsRenderer = new FItemsRenderer();
		//---render item
		if($itemVO = array_shift($this->data)) {
			$itemVO->render($this->fItemsRenderer,false);
			FProfiler::write('FItems::parse--ITEM PARSED');
		}
	}

	function show() {
		return $this->fItemsRenderer->show();
	}

	function render($from=0, $perPage=0) {
		if(empty($this->data)) $this->getList($from, $perPage);
		if(empty($this->data)) return false;
		//---items parsing
		while ($this->data) {
			$this->parse();
		}
		$ret = $this->show();
		return $ret;
	}

	/**
	 * set unreded items to cache
	 * */
	static function cacheUnreadedList() {
		$user = FUser::getInstance();
		if($user->idkontrol==false) return 0;
		if(empty($user->itemVO)) $unreadedNum = $user->pageVO->unreaded;
		else $unreadedNum = $user->itemVO->unreaded;
		if(empty($unreadedNum)) return 0;
		//TODO: optimize this for blog (not published items)
		$arr = FDBTool::getAll("select itemId,public from sys_pages_items
		where and pageId='".$user->pageVO->pageId."'".(($user->itemVO->itemId>0)?(" and itemIdTop='".$user->itemVO->itemId."'"):(" and (itemIdTop is null or itemIdTop==0)"))." order by itemId desc limit 0,".$unreadedNum);
		if(!empty($arr)) {
			$cache = FCache::getInstance( 's' );
			$unreadedList = &$cache->getPointer('unreadedItems');
			if(empty($unreadedList)) $unreadedList = array();
			//add to unreaded list
			foreach($arr as $row) if($row[1]==1) if(!in_array($row[0],$unreadedList)) $arrTmp[] = $row[0];
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