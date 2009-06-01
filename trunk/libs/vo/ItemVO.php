<?php
class ItemVO {
	var $itemId = 0;
	var $typeId;
	var $pageId;

	var $text;
	var $addon;
	var $enclosure;
	var $dateStart;
	var $dateEnd;
	var $dateCreated;
	var $hit;

	var $thumbInSysRes = false;
	var $thumbUrl;
	var $detailUrl;
	var $detailWidth;
	var $detailHeight;
	var $detailUrlToGalery;
	var $detailUrlToPopup;

	function ItemVO($itemId=0, $autoLoad = false) {
		$this->itemId = $itemId;
		if($autoLoad == true) {
			$this->load();
		}
	}

	function checkItem() {
		if($this->itemId > 0) {
			$item = FDBTool::getRow("select typeId,pageId from sys_pages_items where itemId='".$this->itemId."'");
			if(FRules::get(FUser::logon(),$item[1])) {
				$this->typeId = $item[0];
				$this->pageId = $item[1];
			} else {
				$this->itemId = 0;
			}
		}
	}
	//---special properties
	static function getProperty($itemId,$propertyName,$default=false) {
		$q = "select value from sys_pages_items_properties where itemId='".$itemId."' and name='".$propertyName."'";
		$value = FDBTool::getOne($q,$itemId.'-'.$propertyName.'-prop','fitems','l');
		if($value === false) $value = $default;
		return $value;
	}
	static function setProperty($itemId,$propertyName,$propertyValue) {
		FDBTool::query("insert into sys_pages_items_properties (itemId,name,value) values ('".$itemId."','".$propertyName."','".$propertyValue."') on duplicate key update value='".$propertyValue."'");
		$cache = FCache::getInstance('l');
		$cache->invalidateData($itemId.'-'.$propertyName.'-prop','fitems');
	}
	
	//TODO: refactor - getitem,saveitem - check where used
			function getItem($itemId) {
            	$this->setWhere("i.itemId='".$itemId."'");
            	$arr = $this->getContent();
            	if(!empty($arr)) return $arr[0];
            }
            function saveItem($arrData) {
            	$sItem = new fSqlSaveTool('sys_pages_items','itemId');
            	return $sItem->Save($arrData,array('dateCreated'));
            }
	
	
	//---delete
	static function deleteItem($itemId) {
		FDBTool::query("delete from sys_pages_items where itemId='".$itemId."'");
		FDBTool::query("delete from sys_users_pocket where itemId='".$itemId."'");
		FDBTool::query("delete from sys_pages_items_readed_reactions where itemId='".$itemId."'");
		FDBTool::query("delete from sys_pages_items_hit where itemId='".$itemId."'");
		FDBTool::query("delete from sys_pages_items_tag where itemId='".$itemId."'");
	}
}