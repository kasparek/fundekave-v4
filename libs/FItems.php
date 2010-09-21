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
			if(!empty($props))
			foreach($this->data as $itemVO) {
				$i = count($props)-1;
				$invalidate = false;
				while(count($props)>0 && $i>=0) {
					if($props[$i][0]==$itemVO->itemId) {
						$prop = array_pop($props);
						$itemVO->properties[$prop[1]] = $prop[2];
						$invalidate = true;
					}
					$i--;
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
			}
		}

		if($this->debug==1) print_r($this->data);
		return $this->data;
	}
	/*
	 function pop() {
		if($this->data) return array_shift($this->data);
		}
		*/
	function parse() {
		if(!$this->fItemsRenderer) $this->fItemsRenderer = new FItemsRenderer();
		//---render item
		if($itemVO = array_shift($this->data)) {
			$this->fItemsRenderer->render( $itemVO );
			FProfiler::profile('FItems::parse--ITEM PARSED');
		}
	}

	function show() {
		return $this->fItemsRenderer->show();
	}

	function render($from=0, $perPage=0) {
		if(empty($this->data)) $this->getList($from, $perPage);
		//---items parsing
		if(!empty($this->data)) {
			while ($this->data) {
				$this->parse();
			}
			$ret = $this->show();
			return $ret;
		} else {
			return false;
		}
	}

	//---aktualizace oblibenych / prectenych prispevku
	/*.......aktualizace FAV KLUBU............*/
	static function aFavAll($usrId,$typeArr='forum') {
		if(!is_array($typeArr)) $typeArr = array($typeArr);

		if(!empty($usrId)){
			foreach($typeArr as $typeId) {
				//file cache until somebody create new page
				$q = "SELECT f.pageId FROM sys_pages_favorites as f join sys_pages as p on p.pageId=f.pageId WHERE p.typeId='".$typeId."' and f.userId = '".$usrId."'";
				$klo=FDBTool::getCol($q,'user-'.$usrId.'-type-'.$typeId.'-1','aFavAll','f',0);
				$q = "SELECT pageId FROM sys_pages where typeId = '".$typeId."'";
				$kls=FDBTool::getCol($q,'user-'.$usrId.'-type-'.$typeId.'-2','aFavAll','f',0);
				if(!isset($klo[0])) $res=$kls;
				else $res = array_diff($kls,$klo);
				if(!empty($res)) {
					$cache = FCache::getInstance('f');
					$cache->invalidateGroup('aFavAll');
					foreach($res as $r) {
						FDBTool::query('insert into sys_pages_favorites (userId,pageId,cnt) values ("'.$usrId.'","'.$r.'","0")');
					}
				}
			}
		}
	}

	static function aFav($pageId,$userId) {
		if($userId > 0){
			$dot = "insert delayed into sys_pages_favorites
			values ('".$userId."','".$pageId."',(select cnt from sys_pages where pageId='".$pageId."'),'0')
			on duplicate key update cnt=(select cnt from sys_pages where pageId='".$pageId."')";
			FDBTool::query($dot);
		}
	}


	/**
	 * chechk if item exists
	 */
	static function itemExists($itemId) {
		$q = "select count(1) from sys_pages_items where itemId='".$itemId."'";
		return FDBTool::getOne($q,$itemId.'exist','fitems','l');
	}
}