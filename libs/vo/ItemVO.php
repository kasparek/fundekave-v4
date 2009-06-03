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
	
	static $colsDefault = array('itemId' => 'i.itemId',
	'itemIdTop' => 'i.itemIdTop',
	'itemIdBottom' => 'i.itemIdBottom',
	'typeId' => 'i.typeId',
	'pageId' => 'i.pageId',
	'pageIdBottom' => 'i.pageIdBottom',
	'categoryId' => 'i.categoryId',
	'userId' => 'i.userId',
	'name' => 'i.name',
	'dateStart' => 'i.dateStart',
	'dateEnd' => 'i.dateEnd',
	'dateCreated' => 'i.dateCreated',
	'text' => 'i.text',
	'textLong' => 'i.textLong',
	'enclosure' => 'i.enclosure',
	'addon' => 'i.addon',
	'filesize' => 'i.filesize',
	'hit' => 'i.hit',
	'cnt' => 'i.cnt',
	'tag_weight' => 'i.tag_weight',
	'location' => 'i.location',
	'public' => 'i.public'
	);

	//TODO: galery - 'galeryDir'=>'p.galeryDir','pageParams'=>'p.pageParams','pageDateUpdated'=>'p.dateUpdated','pageName'=>'p.name'
	static $colsType = array(
		'galery'=>array('dateCreatedLocal'=>"date_format(i.dateCreated ,'{#datetime_local#}')"
			,'dateCreatedIso'=>"date_format(i.dateCreated ,'{#datetime_iso#}')"),
		'blog'=>array('dateCreatedLocal'=>"date_format(i.dateCreated ,'{#date_local#}')"
			,'dateCreatedIso'=>"date_format(i.dateCreated ,'{#date_iso#}')"),
		'forum'=>array('dateCreatedLocal'=>"date_format(i.dateCreated ,'{#datetime_local#}')"
			,'dateCreatedIso'=>"date_format(i.dateCreated ,'{#datetime_iso#}')",),
		'event'=>array('dateStartLocal'=>"date_format(i.dateStart ,'{#date_local#}')"
			,'dateStartIso'=>"date_format(i.dateStart ,'{#date_iso#}')"
			,'dateEndLocal'=>"date_format(i.dateEnd ,'{#date_local#}')"
			,'dateEndIso'=>"date_format(i.dateEnd ,'{#date_iso#}')"
			,'dateStartTime'=>"date_format(i.dateStart ,'{#time_short#}')"
			,'dateEndTime'=>"date_format(i.dateEnd ,'{#time_short#}')"
			,'dateCreatedLocal'=>"date_format(i.dateCreated ,'{#date_local#}')"
			,'dateCreatedIso'=>"date_format(i.dateCreated ,'{#date_iso#}')"));

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

	var $editable = false;
	var $unread = false;

	var $thumbInSysRes = false;
	var $thumbUrl;
	var $detailUrl;
	var $detailWidth;
	var $detailHeight;
	var $detailUrlToGalery;
	var $detailUrlToPopup;

	function ItemVO($itemId=0, $autoLoad = false) {
		parent::__construct();
		$this->cacheResults = 0; //--- for items we cache localy
		$this->itemId = $itemId;
		if($autoLoad == true) {
			$this->load();
		}
	}
	
	static function getTypeColumns($typeId,$getKeysArray=false) {
		$arrSelect = array_merge(ItemVO::$colsDefault,ItemVO::$colsType[$typeId]);
		if($getKeysArray) return array_keys($arrSelect);
		else return implode(",",$arrSelect);
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
	
	function load() {
		//---try load from cache cache
		$cache = FCache::getInstance('l');
		if(($itemVO = $cache->getData($this->itemId, 'fit')) === false) {
			parent::load();
			$this->prepare();
		} else {
			$this = $itemVO;
		}
	}
	
	function map() {
		parent::map();
		$this->prepare();
		//---save in cache
		$cache = FCache::getInstance('l');
		$cache->setData( $this, $this->itemId, 'fit');
	}
	
	function save() {
		parent::save();
		//---update in cache
		$cache = FCache::getInstance('l');
		$cache->setData( $this, $this->itemId, 'fit');
	}

	function prepare() {
		switch ($this->typeId) {
			case 'galery':
				FGalery::prepare(&$this);
				break;
			case 'forum':
				$this->unread = FForum::isUnreadedMess($this->itemId);
				break;
		}
		if(($userId = FUser::logon()) > 0) {
			if($userId == $this->userId || FRules::get($userId,$this->pageId,2)) {
				$this->editable = true;
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