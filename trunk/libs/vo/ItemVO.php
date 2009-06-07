<?php
class ItemVO extends FDBvo {

	var $options = array();
	
	static $colsDefault = array('itemId' => 'itemId',
	'itemIdTop' => 'itemIdTop',
	'itemIdBottom' => 'itemIdBottom',
	'typeId' => 'typeId',
	'pageId' => 'pageId',
	'pageIdBottom' => 'pageIdBottom',
	'categoryId' => 'categoryId',
	'userId' => 'userId',
	'name' => 'name',
	'dateStart' => 'dateStart',
	'dateEnd' => 'dateEnd',
	'dateCreated' => 'dateCreated',
	'text' => 'text',
	'textLong' => 'textLong',
	'enclosure' => 'enclosure',
	'addon' => 'addon',
	'filesize' => 'filesize',
	'hit' => 'hit',
	'cnt' => 'cnt',
	'tag_weight' => 'tag_weight',
	'location' => 'location',
	'public' => 'public'
	);

	//TODO: galery - 'galeryDir'=>'p.galeryDir','pageParams'=>'p.pageParams','pageDateUpdated'=>'p.dateUpdated','pageName'=>'p.name'
	static $colsType = array(
		'galery'=>array('dateCreatedLocal'=>"date_format(dateCreated ,'{#datetime_local#}')"
			,'dateCreatedIso'=>"date_format(dateCreated ,'{#datetime_iso#}')"),
		'blog'=>array('dateCreatedLocal'=>"date_format(dateCreated ,'{#date_local#}')"
			,'dateCreatedIso'=>"date_format(dateCreated ,'{#date_iso#}')"),
		'forum'=>array('dateCreatedLocal'=>"date_format(dateCreated ,'{#datetime_local#}')"
			,'dateCreatedIso'=>"date_format(dateCreated ,'{#datetime_iso#}')",),
		'event'=>array('dateStartLocal'=>"date_format(dateStart ,'{#date_local#}')"
			,'dateStartIso'=>"date_format(dateStart ,'{#date_iso#}')"
			,'dateEndLocal'=>"date_format(dateEnd ,'{#date_local#}')"
			,'dateEndIso'=>"date_format(dateEnd ,'{#date_iso#}')"
			,'dateStartTime'=>"date_format(dateStart ,'{#time_short#}')"
			,'dateEndTime'=>"date_format(dateEnd ,'{#time_short#}')"
			,'dateCreatedLocal'=>"date_format(dateCreated ,'{#date_local#}')"
			,'dateCreatedIso'=>"date_format(dateCreated ,'{#date_iso#}')"));

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

		//---comments on blog/forum
		var $cnt;
		var $cntReaded;

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
		var $thumbWidth;
		var $thumbHeight;
		
		var $detailUrl;
		var $detailWidth;
		var $detailHeight;
		var $detailUrlToGalery;
		var $detailUrlToPopup;

		function ItemVO($itemId=0, $autoLoad = false, $options=array()) {
			$this->table = 'sys_pages_items';
			$this->primaryCol = 'itemId';
			if(isset($options['type'])) $this->typeId = $options['type'];
			$this->options = $options;
			parent::__construct();
			$this->cacheResults = 0; //--- for items we cache localy
			$this->itemId = $itemId;
			if($autoLoad == true) {
				$this->load();
			}
		}

		static function getTypeColumns($typeId,$getKeysArray=false) {
			$arrSelect = array_merge(ItemVO::$colsDefault, ($typeId)?(ItemVO::$colsType[$typeId]):(array()));
			if($getKeysArray) return $arrSelect;
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
			
			if(empty($this->typeId)) {
				$q = "select typeId from ".$this->table." where ".$this->primaryCol. "='".$this->{$this->primaryCol}."'";
				$this->typeId = FDBTool::getOne($q, $this->{$this->primaryCol}, 'fitType', 'l');
			}
			$this->columns = ItemVO::getTypeColumns($this->typeId,true);
			
			//---try load from cache cache
			$cache = FCache::getInstance('l');
			if(($itemVO = $cache->getData($this->itemId, 'fit')) === false) {
				parent::load();
				$this->prepare();
			} else {
				$this->reload($itemVO);
			}
		}

		function reload($itemVO) {
			foreach ($itemVO as $key => $val) {
				$this->{$key} = $val;
			}
		}

		function map($arr) {
			parent::map($arr);
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

		/**
		 * returns parsed html
		 *
		 */
		function render($itemRenderer=null) {
			if(!$itemRenderer) {
				$itemRenderer = new FItemsRenderer();
				if(!empty($this->options)) {
					foreach($this->options as $k=>$v) {
						$itemRenderer->$k = $v;
					}
				}
			}
			$itemRenderer->render( $this );
			return $itemRenderer->show();
		}

		//---special properties
		static function getProperty($itemId,$propertyName,$default=false) {
			$q = "select value from sys_pages_items_properties where itemId='".$itemId."' and name='".$propertyName."'";
			$value = FDBTool::getOne($q,$itemId.'-'.$propertyName.'-prop','fitems','l');
			if($value === false || $value === null) $value = $default;
			return $value;
		}
		
		static function setProperty($itemId,$propertyName,$propertyValue) {
			FDBTool::query("insert into sys_pages_items_properties (itemId,name,value) values ('".$itemId."','".$propertyName."','".$propertyValue."') on duplicate key update value='".$propertyValue."'");
			$cache = FCache::getInstance('l');
			$cache->invalidateData($itemId.'-'.$propertyName.'-prop','fitems');
		}

		function getNumUnreadComments( $userId ) {
			$q =' select cnt from sys_pages_items_readed_reactions where itemId="'.$this->itemId.'" and userId="'.$userId.'"';
			$this->cntReaded = FDBTool::getOne($q,$this->itemId.'-'.$userId.'-readed','fitems','l');
			if(!empty($this->cnt)) $this->cnt = 0;
			if(empty($this->cntReaded)) $this->cntReaded = $this->cnt;
			$unreaded = $this->cnt - $this->cntReaded;
			return $unreaded;
		}

		//TODO: refactor - getitem,saveitem - check where used
		function getItem($itemId) {
			$this->setWhere("itemId='".$itemId."'");
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