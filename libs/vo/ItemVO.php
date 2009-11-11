<?php
class ItemVO extends Fvob {

	var $table = 'sys_pages_items';
	var $primaryCol = 'itemId';

	var $options = array();

	var $typed = false;
	var $columns = array('itemId' => 'itemId',
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

	static $colsType = array(
		'galery'=>array('dateCreatedLocal'=>"date_format(dateCreated ,'{#date_local#}')"
		,'dateCreatedIso'=>"date_format(dateCreated ,'{#date_iso#}')"),
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

		var $itemId = null;
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
		var $dateStartTime;

		var $dateEndIso;
		var $dateEndLocal;
		var $dateEndTime;

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

		function ItemVO($itemId = null, $autoLoad = false, $options=array()) {
			if(isset($options['type'])) $this->typeId = $options['type'];
			$this->options = $options;
			$this->itemId = $itemId;
			if($autoLoad == true) {
				$this->load();
			}
		}

		function getTypeColumns( $typeId='' ) {
			if($typeId=='') $typeId = $this->typeId;
			$ret = $this->columns;
			if(!empty($typeId)) {
				if(isset(ItemVO::$colsType[$typeId])) {
					$ret = $this->columns = array_merge($this->columns, ($typeId)?(ItemVO::$colsType[$typeId]):(array()));
					$this->typed=true;
				}
			}
			return $ret;
		}

		function checkItem() {
			if($this->itemId > 0) {
				$itemArr = FDBTool::getRow("select typeId,pageId from sys_pages_items where itemId='".$this->itemId."'");
				if(!empty($itemArr)) {
					$this->typeId = $itemArr[0];
					$this->pageId = $itemArr[1];
				} else {
					$this->itemId = 0;
				}
			}
		}

		function load($typed=true) {
			
			if($typed===true) {
				if(empty($this->typeId)) {
					$q = "select typeId from ".$this->table." where ".$this->primaryCol. "='".$this->{$this->primaryCol}."'";
					$this->typeId = FDBTool::getOne($q, $this->{$this->primaryCol}, 'fitType', 'l');
				}
				$this->columns = $this->getTypeColumns();
			}

			//---try load from cache cache
			$cache = FCache::getInstance('l');
			if(($itemVO = $cache->getData($this->itemId.'-'.(($this->typed==true)?('typed'):('nottyped')), 'fit')) === false) {
				$vo = new FDBvo( $this );
				$vo->load();
				$vo->vo = false;
				$vo = false;
				$this->prepare();
			} else {
				$this->reload($itemVO);
			}
			if($this->itemId > 0) return true;
		}

		function reload($itemVO) {
			foreach ($itemVO as $key => $val) {
				$this->{$key} = $val;
			}
		}

		function map($arr) {
			if(!empty($arr)) {
				foreach($arr as $k=>$v) {
					$this->{$k} = $v;
				}
			}
			$this->prepare();
			//---save in cache
			$cache = FCache::getInstance('l');
			$cache->setData( $this, $this->itemId.'-'.(($this->typed===true)?('typed'):('nottyped')), 'fit');
		}

		function save() {
			$vo = new FDBvo( $this );
			$vo->resetIgnore();
			if($this->itemId > 0) {
				//---update
				$vo->addIgnore('dateCreated');
			} else {
				//---insert
				if(empty($this->dateCreated)) {
					$this->dateCreated = 'now()';
					$vo->notQuote('dateCreated');
				}
				if($this->itemIdTop > 0) {
					ItemVO::incrementReactionCount( $this->itemIdTop );
				} else {
					FPages::cntSet( $this->pageId );
				}
				$cache = FCache::getInstance('f');
				$cache->invalidateData($this->pageId.'-page', 'fitGrp');
			}
				
			$itemId = $vo->save();
			//---update stats
			ItemVO::statPage($this->pageId, FUser::logon(), false);
			//---update in cache
			$cache = FCache::getInstance('l');
			//$cache->setData( $this, $this->itemId, 'fit'); - doesnot work for custom data as dateStart
			$cache->invalidateData($this->itemId, 'fit');
			return $itemId;
		}

		function delete() {
			$itemId = $this->itemId;
				
			$vo = new FDBvo( $this );
			$vo->delete();
			$vo->vo = false;
			$vo = false;
				
			if($this->itemIdTop > 0) {
				ItemVO::incrementReactionCount( $itemVO->itemIdTop, false );
			} else {
				FPages::cntSet($this->pageId, false);
			}
			//---delete in other tables
			FDBTool::query("delete from sys_users_pocket where itemId='".$itemId."'");
			FDBTool::query("delete from sys_pages_items_readed_reactions where itemId='".$itemId."'");
			FDBTool::query("delete from sys_pages_items_hit where itemId='".$itemId."'");
			FDBTool::query("delete from sys_pages_items_tag where itemId='".$itemId."'");
			//---statistics
			ItemVO::statPage($this->pageId, FUser::logon());
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

		//---support
		/**
		 * statistics for foto - item
		 * @return void
		 */
		function hit() {
			if(!empty($this->itemId)){
				FDBTool::query("update sys_pages_items set hit=hit+1 where itemId=".$this->itemId);
				FDBTool::query("insert into sys_pages_items_hit (itemId,userId,dateCreated) values (".$this->itemId.",".FUser::logon().",now())");
				$this->hit++;
			}
		}

		function getPageItemsId() {
			$cache = FCache::getInstance('f');
			if(($arr = $cache->getData($this->pageId.'-page', 'fitGrp')) === false) {
				$pageVO = new PageVO($this->pageId,true);
				$q = "select itemId from sys_pages_items where itemIdTop is null and pageId='".$this->pageId."' order by ".$pageVO->itemsOrder();
				$arr = FDBTool::getCol($q);
				$cache->setData($arr);
			}
			return $arr;
		}

		function onPageNum() {
			$arrItemId = $this->getPageItemsId();
			$pageVO = new PageVO($this->pageId,true);
			$arr = array_chunk($arrItemId, $pageVO->perPage());
			$pid = 0;
			foreach ($arr as $k=>$arrpage) {
				if(in_array($this->itemId,$arrpage)) {
					$pid = $k + 1;
					break;
				}
			}
			return $pid;
		}
		
		function getTotal() {
			return count($this->getPageItemsId());
		}
		
		function getPos() {
			$arr = $this->getPageItemsId();
			$arr = array_flip($arr);
			return $arr[$this->itemId]; 
		}

		function getNext($onlyId=false) {
			$itemId = $this->getSideItemId(1,true);
			if($itemId > 0) {
				if($onlyId === true) {
					return $itemId;
				} else {
					$itemVO = new ItemVO($itemId, false);
					$itemVO->typeId = $this->typeId;
					return $itemVO;
				} 
			}
			return false;
		}

		function getPrev($onlyId=false) {
			$itemId = $this->getSideItemId(-1,true);
			if($itemId > 0) {
				if($onlyId === true) {
					return $itemId;
				} else {
					$itemVO = new ItemVO($itemId, false);
					$itemVO->typeId = $this->typeId;
					return $itemVO;
				}
			}
			return false;
		}

		function getSideItemId($side=-1, $consecutively = false) {
			$keys = $this->getPageItemsId();; //--- when key is value
			$keyIndexes = array_flip($keys);
			$return = array();
			//--- previous
			if($side == -1) {
				if (isset($keys[$keyIndexes[$this->itemId]-1])) {
					return $keys[$keyIndexes[$this->itemId]-1];
				} else {
					if($consecutively) return $keys[sizeof($keys)-1]; else return  0; //--- if not previous return last
				}
			} else {
				//--- next
				if (isset($keys[$keyIndexes[$this->itemId]+1])) {
					return $keys[$keyIndexes[$this->itemId]+1];
				} else {
					if($consecutively) return $keys[0]; else return 0; //--- if not next return first
				}
			}
		}
		
		public $propDefaults = array('reminder'=>0,'reminderEveryday'=>0);  
		function prop($propertyName,$value=null) {
			if($value!==null) ItemVO::setProperty($this->itemId,$propertyName,$value);
			$default='';
			if(isset($this->propDefaults[$propertyName])) $default = $this->propDefaults[$propertyName];
			return ItemVO::getProperty($this->itemId,$propertyName,$default);
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

		//---support functions
		/**
		 * items for page statistics
		 *
		 * @param string $pageId
		 * @param int $userId
		 * @param Boolean $count - if true num is refreshed by database
		 */
		static function statPage($pageId, $userId, $count=true){
			if($count) $str = FDBTool::getOne("select count(1) from sys_pages_items where pageId='".$pageId."' AND userId='". (int) $userId."'");
			else $str="ins+1";
			FDBTool::query("update sys_pages_counter set ins=".$str." WHERE pageId='".$pageId."'and dateStamp=now() AND userId='". (int) $userId."'");
		}

		/**
		 * stats for item reactions
		 *
		 * @param int $itemId
		 * @param Boolean $increment
		 * @return void
		 */
		static function incrementReactionCount($itemId, $increment=true) {
			$dot = "update sys_pages_items set cnt=cnt".(($increment===true)?('+'):('-'))."1 where itemId='".$itemId."'";
			return FDBTool::query($dot);
		}
}