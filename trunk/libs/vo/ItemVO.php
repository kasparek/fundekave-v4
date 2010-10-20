<?php
class ItemVO extends Fvob {

	var $table = 'sys_pages_items';
	var $primaryCol = 'itemId';

	var $options = array();

	var $columns = array('itemId' => 'itemId',
	'itemIdTop' => 'itemIdTop',
	'typeId' => 'typeId',
	'pageId' => 'pageId',
	'pageIdTop' => 'pageIdTop',
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
	public $propDefaults = array('reminder'=>0,'reminderEveryday'=>0,'forumSet'=>1);

	public function __get($name) {
		if(!$name) return;

		if(isset($this->{$name})) return $this->{$name};
		if(isset($this->{'_'.$name})) return $this->{'_'.$name};

		$type = $this->typeId;

		switch($name) {
			case 'pageVO':
				if(empty($this->pageId)) return null;
				if(!$this->_pageVO) $this->_pageVO = new PageVO($this->pageId);
				return $this->_pageVO;
				break;
			case 'dateStartIso':
			case 'dateEndIso':
			case 'dateCreatedIso':
				$format = DATE_ATOM;
				$key = str_replace('Iso','',$name);
				$name = '_'.$name;
				break;
			case 'dateStartLocal':
				$format = 'date';
				$key = str_replace('Local','',$name);
				$name = '_'.$name;
				break;
			case 'dateStartTime':
			case 'dateEndTime':
				$key = str_replace('Time','',$name);
				$format = 'timeshort';
				$name = '_'.$name;
				break;
			case 'dateEndLocal':
				$format = 'date';
				$key = str_replace('Local','',$name);
				$name = '_'.$name;
				break;
			case 'dateCreatedLocal':
				if($type=='forum') {
					$format = 'datetime';
				} else {
					$format = 'date';
				}
				$key = str_replace('Local','',$name);
				$name = '_'.$name;
				break;
			case 'unreaded':
				//number of unreaded reactions
				$user = FUser::getInstance();
				if($user->idkontrol==false) {
					$this->unreaded=0;
					return 0;
				}
				$numReaded = (int) FDBTool::getOne('select cnt from sys_pages_items_readed_reactions where itemId="'.$this->itemId.'" and userId="'.$user->userVO->userId.'"');
				if($numReaded < 1) $numReaded = $this->cnt;
				$this->unreaded = $this->cnt - $numReaded;
				//$this->unreaded = (int) FDBTool::getOne('select i.cnt-r.cnt from sys_pages_items as i join sys_pages_items_readed_reactions as r on i.itemId=r.itemId and r.userId="'.$user->userVO->userId.'" and i.itemId="'.$this->itemId.'"');
				if($this->unreaded < 0) $this->unreaded=0;
				$name = 'unreaded';
				break;
			case 'isUnreaded':
				$this->isUnreaded = false;
				$cache = FCache::getInstance( 's' );
				$unreadedList = &$cache->getPointer('unreadedItems');
				if(empty($unreadedList)) $unreadedList = array();
				if(in_array($this->itemId,$unreadedList)) $this->isUnreaded = true;
				$name = 'isUnreaded';
				break;
		}


		if(!empty($format)) {
			$this->{$name} = $this->date($this->$key,$format);
			
		}

		if(!empty($name)) {
			$this->memStore();
			return $this->{$name};
		}
		
		return null;
	}

	var $itemId;
	var $itemIdTop;
	var $typeId;
	var $pageId;
	var $_pageVO;
	var $pageIdTop;
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
	//$unreaded - get by getter

	var $tag_weight;
	var $location;
	var $public;

	private $_dateStartIso;
	private $_dateStartLocal;
	private $_dateStartTime;

	private $_dateEndIso;
	private $_dateEndLocal;
	private $_dateEndTime;

	private $dateCreatedIso;
	private $dateCreatedLocal;

	var $editable = false;
	var $prepared = false;

	var $thumbInSysRes = false;
	var $thumbUrl;
	var $detailUrl;

	//---changed
	var $htmlName;

	//private
	var $itemList;

	function load() {
		if($ret = parent::load()) {
			if(!$this->loadedCached) $this->prepare();
		}
		return $ret;
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
		
			
		if($this->itemId > 0) {
			//---update
			if($this->saveOnlyChanged===false) {
				$vo->addIgnore('dateCreated');
			}
			$vo->save();
		} else {
			//---insert
			if(empty($this->dateCreated)) {
				$this->dateCreated = 'now()';
				$vo->notQuote('dateCreated');
				if($this->typeId=='forum') {
					$this->dateStart = 'now()';
					$vo->notQuote('dateStart');
				}
			}
			$vo->save();
			
			if($this->itemIdTop > 0) {
				$itemTop = new ItemVO( $this->itemIdTop );
				$itemTop->saveOnlyChanged = true;
				$itemTop->set('cnt',FDBTool::getOne("select count(1) from sys_pages_items where itemIdTop='".$this->itemIdTop."'"));
				$itemTop->save();
				FPages::cntSet( $this->pageId, 0 );
			} else {
				FPages::cntSet( $this->pageId, 1 );
			}
				
			$this->itemList = null;
			$cache = FCache::getInstance('f');
			$cache->invalidateData($this->pageId, 'fitGrp');
		}
		//---update in cache
		$this->memFlush();
		if( empty($this->itemIdTop) && !empty($this->pageId) ) $this->updateItemIdLast();
		FCommand::run(ITEM_UPDATED,$this);
		return $this->itemId;
	}

	/**
	 * update last public item for page
	 * 
	 * @return void
	 */
	function updateItemIdLast() {
		//---update last item id on page
		$itemIdLast = FDBTool::getOne("select itemId from sys_pages_items where public=1 and (itemIdTop is null or itemIdTop=0) and pageId='".$this->pageId."' order by dateCreated desc limit 1");
		$this->pageVO->prop( 'itemIdLast', $itemIdLast);
		return $itemIdLast;
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
		$this->deleteImage();
		//---delete in other tables
		FDBTool::query("delete from sys_users_pocket where itemId='".$itemId."'");
		FDBTool::query("delete from sys_pages_items_readed_reactions where itemId='".$itemId."'");
		FDBTool::query("delete from sys_pages_items_hit where itemId='".$itemId."'");
		FDBTool::query("delete from sys_pages_items_tag where itemId='".$itemId."'");
			
		//---last item
		$this->updateItemIdLast();
		
		$this->memFlush();
		
		FCommand::run(ITEM_UPDATED,$this);
	}

	function prepare() {
		//galery item or any item with image enclosed
		if(!empty($this->enclosure)) {
			$confGalery = FConf::get('galery');
			$thumbCut = $confGalery['thumbCut'];
			if(isset($confGalery[$this->typeId.'_thumbCut'])) {
			  $thumbCut = $confGalery[$this->typeId.'_thumbCut'];
			}
			elseif($this->thumbInSysRes == false) {
				$thumbCut = $this->pageVO->getProperty('thumbCut',$thumbCut,true);
			}
			//thumbnail URL
			$this->thumbUrl = $this->getImageUrl(null,$thumbCut);
			//detail image URL
			//get optional sizes list
			$sideOptionList = explode(',',FConf::get('image_conf','sideOptions'));
			//get closest lower
			$user = FUser::getInstance();
			$maxWidth = $user->userVO->clientWidth;
			if(empty($maxWidth)) $maxWidth = FConf::get('image_conf','sideDefault');
			else {
				$maxWidth = $maxWidth - $confGalery['clientSpace'];
				//get closest valid width
				foreach ($sideOptionList as $fib) {
					if($maxWidth - $fib >= 0) {
						$diff[$fib] = (int) $maxWidth - $fib;
					}
				}
				$fibs = array_flip($diff);
				$maxWidth = $fibs[min($diff)];
			}
			$this->detailUrl = $this->getImageUrl(null,$maxWidth.'/prop');
		}

		//check if is editable
		if(($userId = FUser::logon()) > 0) {
			if($userId == $this->userId) {
				$this->editable = true;
			} else if(FRules::get($userId,$this->pageId,2)) {
				$this->editable = true;
			}
		}
		$this->prepared = true;
		$this->memStore();
	}

	/**
	 * returns parsed html
	 *
	 */
	function render($itemRenderer=null,$show=true) {
		if(!$itemRenderer) {
			$itemRenderer = new FItemsRenderer();
			if(!empty($this->options)) {
				foreach($this->options as $k=>$v) {
					$itemRenderer->setOption($k,$v);
				}
			}
		}
		$itemRenderer->render( $this );
		if($show) return $itemRenderer->show();
	}

	//---support
	/**
	 * statistics for foto - item
	 * @return void
	 */
	function hit() {
		if(!empty($this->itemId)){
			//due to locking update hit sometimes from _hit table
			//FDBTool::query("update low_priority sys_pages_items set hit=hit+1 where itemId=".$this->itemId);

			//---write
			FDBTool::query("insert into sys_pages_items_hit values ('".$this->itemId."','".(FUser::logon()*1)."',now())");

			$this->hit++;
		}
	}


	function getPageItemsId() {
		if(!empty($this->itemList)) return $this->itemList;
		$cache = FCache::getInstance('f');
		if(($arr = $cache->getData($this->pageId, 'fitGrp')) === false) {
			$q = "select itemId from sys_pages_items where (itemIdTop is null or itemIdTop=0) 
			and pageId='".$this->pageId."' order by ".$this->pageVO->itemsOrder().",itemId desc";
			$arr = FDBTool::getCol($q);
			$cache->setData($arr,$this->pageId, 'fitGrp');
			$this->itemList = $arr;
		}
		return $arr;
	}

	function onPageNum() {
		$arrItemId = $this->getPageItemsId();
		$arr = array_chunk($arrItemId, $this->pageVO->perPage());
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
		$keys = $this->getPageItemsId(); //--- when key is value
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


	/**
	 * get url of target
	 *
	 * @return string url
	 */
	function getImageUrl($root=null,$thumbCut=null) {
		$confGalery = FConf::get('galery');
		if($root===null) $root = $confGalery['targetUrlBase'];
		if($thumbCut===null) $sideSize = $confGalery['thumbCut'];
		return $root . $thumbCut .'/'. $this->pageVO->get('galeryDir') .'/'. $this->enclosure;
	}

	/**
	 * delete all cached images
	 *
	 */
	function flush( $resolution=0 ) {
		if(!is_array($resolution)) $resolution = array($resolution);
		foreach($resolution as $side) {
			$url = $this->getImageUrl(null,$side,'flush');
			//request url to do action
			file_get_contents( $url );
		}
	}

	/**
	 * delete image - enclosure
	 */
	function deleteImage() {
		if(empty($this->enclosure)) return;
		$this->flush();
		$confGalery = FConf::get('galery');
		$file = new FFile();
		if($file->is_file($confGalery['sourceServerBase'] . $this->pageVO->get('galeryDir') . '/' . $this->enclosure)) {
			$file->unlink($confGalery['sourceServerBase'] . $this->pageVO->get('galeryDir') . '/' . $this->enclosure);
		}
		$this->set('enclosure','');
	}

	/**
	 * update readed reactions
	 * */
	function updateReaded($userId) {
		if(empty($userId)) return;
		if($this->cnt==0) return;
		$this->unreaded = 0;
		FDBTool::query("insert delayed into sys_pages_items_readed_reactions (itemId,userId,cnt,dateCreated) values ('".$this->itemId."','".$userId."',(select cnt from sys_pages_items where itemId='".$this->itemId."'),now()) on duplicate key update cnt=(select cnt from sys_pages_items where itemId='".$this->itemId."')");
	}

}