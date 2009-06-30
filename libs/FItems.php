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
	//---items removed because no access
	public $itemsRemoved = 0;

	//---options
	public $thumbInSysRes = false;


	function __construct($typeId='',$byPremissions=false,$itemRenderer=null) {
		parent::__construct('sys_pages_items','itemId');
		$this->fetchmode = 1;
		if($typeId!='') $this->initList($typeId,$byPremissions);
		$this->columns = ItemVO::getTypeColumns($this->typeId);
		if($itemRenderer) $this->fItemsRenderer = $itemRenderer;
	}

	static function isTypeValid($type) {
		$types = array('forum','galery','blog','event');
		return in_array($type, $types);
	}

	function initList($typeId='forum', $byPermissions = false) {
		$this->queryReset();
		if(FItems::isTypeValid($typeId)) {
			$this->typeId = $typeId;
			$this->addWhere("typeId='".$typeId."'");
		}
		$doPagesJoin = true;
		//---check permissions for given user
		if($byPermissions!==false) {
			$this->byPermissions = $byPermissions;
		}
		//---set select
		$this->setSelect( ItemVO::getTypeColumns( $typeId ));
		//---check for public
		if(!FRules::getCurrent( 2 )) {
			$this->addWhere('public = 1');
		}

	}
	
	function total() {
		return count($this->data);
	}
	
	function getList($from=0, $count=0) {
		$this->arrData = array();
		$itemTypeId = $this->typeId;

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
						if(FRules::get($this->byPermissions,$row['pageId'],1)) {
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
			//---map items
			foreach($arr as $row) {
				$itemVO = new ItemVO();
				$itemVO->thumbInSysRes = $this->thumbInSysRes;
				$itemVO->map( $row );
				$this->data[] = $itemVO;
			}
		}

		if($this->debug==1) print_r($this->data);
		return $this->data;
	}

	function pop() {
		if($this->data) return array_shift($this->data);
	}

	function parse() {
		if(!$this->fItemsRenderer) $this->fItemsRenderer = new FItemsRenderer();
		//---render item
		if($itemVO = $this->pop()) {
			$this->fItemsRenderer->render( $itemVO );
			FSystem::profile('FItems::parse--ITEM PARSED');
			return true;
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
			return $this->show();
		} else {
			return false;
		}
	}
	
	//---aktualizace oblibenych / prectenych prispevku
	/*.......aktualizace FAV KLUBU............*/
	static function aFavAll($usrId,$typeId='forum') {
		if(!empty($usrId)){
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
	
	static function aFav($pageId,$userId,$cnt,$booked=0) {
		if(!empty($userId)){
			$dot = "insert into sys_pages_favorites values ('".$userId."','".$pageId."','".$cnt."','".$booked."') on duplicate key update cnt='".$cnt."'";
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