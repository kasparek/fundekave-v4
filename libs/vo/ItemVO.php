<?php
class ItemVO {
	var $tableDef = 'CREATE TABLE sys_pages_items (
       itemId mediumint unsigned NOT NULL
     , itemIdTop mediumint unsigned default NULL
     , itemIdBottom mediumint unsigned default NULL
     , typeId VARCHAR(10) DEFAULT null
     , pageId varchar(5) NOT NULL
     , pageIdBottom varchar(5) default NULL
     , categoryId SMALLINT unsigned DEFAULT null
     , userId MEDIUMINT unsigned default null
     , name VARCHAR(15) not null
     , dateStart DATETIME default NULL
     , dateEnd DATETIME default NULL
     , dateCreated DATETIME NOT NULL
     , text TEXT
     , textLong TEXT DEFAULT NULL
     , enclosure VARCHAR(255) default null
     , addon VARCHAR(100) default null
     , filesize mediumint unsigned default null
     , hit mediumint unsigned default 0
     , cnt smallint unsigned not null default 0
     , tag_weight mediumint unsigned default 0
     , location VARCHAR(100) default null
     , public TINYINT unsigned NOT NULL DEFAULT 1
     , PRIMARY KEY (itemId)
)  ;
	';

	var $itemId = 0;
	var $itemIdTop;
	var $itemIdBottom;
	var $typeId;
	var $pageId;
	var $pageIdBottom;
	var $categoryId;
	var $userId;
	var $name;
	var $dateStart;
	var $dateEnd;
	var $dateCreated;
	var $text;
	var $textLong;
	var $enclosure;
	var $addon;
	var $filesize;
	var $hit;
	var $cnt;
	var $tag_weight;
	var $location;
	var $public;
	
	var $dateStartIso;
	var $dateStartLocal;
	var $timeStart;
	
	var $dateEndIso;
	var $dateEndLocal;
	var $timeEnd;
	
	var $dateCreatedIso;
	var $dateCreatedLocal;
	var $timeCreated;

	var $thumbInSysRes = false;
	var $thumbUrl;
	var $detailUrl;
	var $detailWidth;
	var $detailHeight;
	var $detailUrlToGalery;
	var $detailUrlToPopup;

	function ItemVO($itemId=0, $autoLoad = false) {
		parent::__construct();
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