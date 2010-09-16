<?php
class ItemVO extends Fvob {

	var $table = 'sys_pages_items';
	var $primaryCol = 'itemId';

	var $options = array();

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
	
	var $propertiesList = array('position','forumSet');
	public $propDefaults = array('reminder'=>0,'reminderEveryday'=>0,'forumSet'=>2);

	public function __get($name) {
		if(!$name) return;
		
		if(isset($this->{$name})) {
			return $this->{$name};
		}

		$type = $this->typeId;

		switch($name) {
			case 'dateStartIso':
			case 'dateEndIso':
			case 'dateCreatedIso':
				$format = DATE_ATOM;
				$key = str_replace('Iso','',$name);
				break;
			case 'dateStartLocal':
				$format = 'date';
				$key = str_replace('Local','',$name);
				break;
			case 'dateStartTime':
			case 'dateEndTime':
				$key = str_replace('Time','',$name);
				$format = 'timeshort';
				break;
			case 'dateEndLocal':
				$format = 'date';
				$key = str_replace('Local','',$name);
				break;
			case 'dateCreatedLocal':
				if($type=='forum') {
					$format = 'datetime';
				} else {
					$format = 'date';
				}
				$key = str_replace('Local','',$name);
				break;
		}
		
		
		if(!empty($format)) {
			$this->$name = $this->date($this->$key,$format);
			return $this->{$name};
		}
		
		return null;
	}

	var $itemId;
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

	private $dateStartIso;
	private $dateStartLocal;
	private $dateStartTime;

	private $dateEndIso;
	private $dateEndLocal;
	private $dateEndTime;

	private $dateCreatedIso;
	private $dateCreatedLocal;

	var $editable = false;
	var $unread = false;
	var $prepared = false;

	var $thumbInSysRes = false;
	var $thumbUrl;

	var $detailUrl;
	var $detailUrlToGalery;
	var $detailUrlToPopup;
	
	//---changed
	var $htmlName;

	function ItemVO($itemId = null, $autoLoad = false, $options=array()) {
		$this->properties = array();
		if(isset($options['type'])) $this->typeId = $options['type'];
		$this->options = $options;
		$this->itemId = $itemId;
		if($autoLoad == true) {
			$this->load();
		}
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

	function load() {
		//---try load from cache cache
		$itemVO = $this->memGet();
		if($itemVO === false) {
			$vo = new FDBvo( $this );
			$vo->load();
			if($this->itemId > 0) {
				$this->prepare();
			}
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
		$this->memStore();
	}
	
	function save() {
		$vo = new FDBvo( $this );
		$vo->resetIgnore();
		$creating = false;
			
		if($this->itemId > 0) {
			//---update
			if($this->saveOnlyChanged===false) {
				$vo->addIgnore('dateCreated');
			}
		} else {
			//---insert
			if(empty($this->dateCreated)) {
				$this->dateCreated = 'now()';
				$vo->notQuote('dateCreated');
			}
			if($this->itemIdTop > 0) {
					
				$itemTop = new ItemVO( $this->itemIdTop );
				$itemTop->saveOnlyChanged = true;
				$itemTop->set('cnt',FDBTool::getOne("select count(1) from sys_pages_items where itemIdTop='".$this->itemIdTop."'")+1);
				$itemTop->save();
					
				FPages::cntSet( $this->pageId, 0 );
			} else {
				FPages::cntSet( $this->pageId, 1 );
			}
			
			//TODO: resolve what is this for
			$cache = FCache::getInstance('f');
			$cache->invalidateData($this->pageId.'-page', 'fitGrp');
			
			$creating = true;
		}

		$itemId = $vo->save();
		//---update stats
		ItemVO::statPage($this->pageId, FUser::logon(), false);
		//---update in cache
		$this->memFlush();
			
		if( empty($this->itemIdTop) ) {
			$this->updateItemIdLast();
		}

		page_PagesList::invalidate();
		return $itemId;
	}

	function updateItemIdLast() {
		//---update last item id on page
		$pageVO = new PageVO($this->pageId);
		$q = "select itemId from sys_pages_items where public=1 and (itemIdTop is null or itemIdTop=0) and pageId='".$this->pageId."' order by dateCreated desc";
		$itemIdLast = FDBTool::getOne($q);
		$pageVO->prop( 'itemIdLast', $itemIdLast);
	}

	function delete() {
		$itemId = $this->itemId;

		$vo = new FDBvo( $this );
		$vo->delete(null);
		$vo->vo = false;
		$vo = false;

		if($this->itemIdTop > 0) {

			$itemTop = new ItemVO( $this->itemIdTop );
			$itemTop->saveOnlyChanged = true;
			$itemTop->set('cnt',FDBTool::getOne("select count(1) from sys_pages_items where itemIdTop='".$this->itemIdTop."'"));
			$itemTop->save();

			FDBTool::query("update sys_pages_items_readed_reactions set cnt=cnt-1 where itemId='".$this->itemIdTop."'");
			FDBTool::query("delete from sys_pages_items_readed_reactions where cnt < 0");

		} else {
			FPages::cntSet($this->pageId, -1);

			FDBTool::query("update sys_pages_favorites set cnt=cnt-1 where pageId='".$this->pageId."'");
			FDBTool::query("update sys_pages_favorites as pf set pf.cnt=(select p.cnt from sys_pages as p where p.pageId=pf.pageId) where pf.cnt < 0 or pf.cnt > (select p.cnt from sys_pages as p where p.pageId=pf.pageId)");
		}
		//---delete in other tables
		FDBTool::query("delete from sys_users_pocket where itemId='".$itemId."'");
		FDBTool::query("delete from sys_pages_items_readed_reactions where itemId='".$itemId."'");
		FDBTool::query("delete from sys_pages_items_hit where itemId='".$itemId."'");
		FDBTool::query("delete from sys_pages_items_tag where itemId='".$itemId."'");
		//---statistics
		ItemVO::statPage($this->pageId, FUser::logon());
			
		//---last item
		$this->updateItemIdLast();
			
		page_PagesList::invalidate();
		
		$this->memFlush();
		
	}

	function prepare() {
		switch ($this->typeId) {
			case 'galery':
				FGalery::prepare( $this );
//				echo 'ItemVO::prepare';
//				var_dump($this);
//				die();
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
		$this->prepared = true;
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
			FDBTool::query("update low_priority sys_pages_items set hit=hit+1 where itemId=".$this->itemId);

			//---write
			$filename = FConf::get('settings','logs_path').'item-counter/'.$this->itemId.'.log';
			$data = 'userId='.FUser::logon().';time='.Date('U')."\n";
			$h = fopen($filename, 'a');
			fwrite($h, $data);
			fclose($h);

			$this->hit++;
		}
	}

	function getPageItemsId() {
		//TODO: store in memory cache / only local prop
		$cache = FCache::getInstance('f');
		if(($arr = $cache->getData($this->pageId.'-page', 'fitGrp')) === false) {
			$pageVO = new PageVO($this->pageId,true);
			$q = "select itemId from sys_pages_items where (itemIdTop is null or itemIdTop=0) and pageId='".$this->pageId."' order by ".$pageVO->itemsOrder();
			$arr = FDBTool::getCol($q,$this->pageId.'-page', 'fitGrp');
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

	function getNext($onlyId=false, $consecutively = true) {
		$itemId = $this->getSideItemId(1,$consecutively);
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

	function getPrev($onlyId=false, $consecutively = true) {
		$itemId = $this->getSideItemId(-1,$consecutively);
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
		if(!isset($keyIndexes[$this->itemId])) return false;
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

	function getNumUnreadComments( $userId ) {
		$ret = 0;
		if($userId > 0) {
			$q =' select cnt from sys_pages_items_readed_reactions where itemId="'.$this->itemId.'" and userId="'.$userId.'"';
			$this->cntReaded = (int) FDBTool::getOne($q,$this->itemId.'-'.$userId.'-readed','fitems','l');
			$numComments = (int) $this->cnt;
			if($this->cntReaded < 1) $this->cntReaded = $numComments;
			$ret = $numComments - $this->cntReaded;
		}
		return $ret;
	}

	//---support functions
	/**
	 * items for page statistics
	 *
	 * @param string $pageId
	 * @param int $userId
	 * @param Boolean $count - if true num is refreshed by database
	 */
	static function statPage($pageId, $userId, $count = true){
		$user = FUser::getInstance();
		$user->pageStat(true,$count,$pageId,$userId);
	}

}