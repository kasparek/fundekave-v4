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


	function __construct() {
		parent::__construct('sys_pages_items as i','i.itemId');
		$this->fetchmode = 1;
	}

	static function isTypeValid($type) {
		$types = array('forum','galery','blog','event');
		return in_array($type, $types);
	}

	function initDetail($itemId) {
		$itemCheck = $this->getRow("select itemIdTop from sys_pages_items where itemId='".$itemId."'");
		if($itemCheck[0] > 0) {
			$this->itemIdInside = $itemId;
			$itemId = $itemCheck[0];
		}
		if($itemId > 0 && $this->showComments) {
			//---add discussion
			FForum::process($itemId);
		}
		$this->addWhere("i.itemId='".$itemId."'");
		if(!FRules::getCurrent(2)) {
			$this->addWhere('i.public = 1');
		}
		return $itemId;
	}

	function initList($typeId='forum', $byPermissions = false, $strictType=false) {
		$this->queryReset();
		if(!empty($typeId)) $this->typeId = $typeId;
		$doPagesJoin = true;
		//---check permissions for given user
		if($byPermissions!==false) {
			$this->byPermissions = $byPermissions;
		}
		//---strict type
		if(!empty($typeId) && $strictType==true) {
			$this->addWhere("i.typeId='".$typeId."'");
		}
		//---set select
		$this->setSelect( ItemVO::getTypeColumns( $typeId ));
		//---check for public
		if(!FRules::getCurrent( 2 )) {
			$this->addWhere('i.public = 1');
		}

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
						if(FRules::get($this->byPermissions,$row[2],1)) {
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
			var_dump($this->fetchmode);
			print_r($arr);
			die();
			//---map items
			foreach($arr as $row) {
				$itemVO = new ItemVO();
				$itemVO->map( $row );
				if($this->thumbInSysRes) $itemVO->thumbInSysRes = true;
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
		//---render item
		if($itemVO = $this->pop()) {
			$this->fItemsRenderer->render($itemVO);
			return true;
		}
	}

	function show() {
		return $this->fItemsRenderer->show();
	}


	/**
	 * chechk if item exists
	 */
	static function itemExists($itemId) {
		$q = "select count(1) from sys_pages_items where itemId='".$itemId."'";
		return $this->getOne($q,$itemId.'exist','fitems','l');
	}
}