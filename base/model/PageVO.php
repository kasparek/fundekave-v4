<?php
class PageVO extends Fvob {

	public function getTable(){ return 'sys_pages'; }
	public function getPrimaryCol() { return 'pageId'; }
	public function getColumns() { return array('pageId' => 'pageId',
	'pageIdTop' => 'pageIdTop',
	'typeId' => 'typeId',
	'typeIdChild' => 'typeIdChild',
	'categoryId' => 'categoryId',
	'template' => 'template',
	'name' => 'name',
	'description' => 'description',
	'content' => 'content',
	'public' => 'public',
	'dateCreated' => 'dateCreated',
	'dateUpdated' => 'dateUpdated',
	'dateContent' => 'dateContent',
	'userIdOwner' => 'userIdOwner',
	'pageIco' => 'pageIco',
	'cnt' => 'cnt',
	'locked' => 'locked',
	'galeryDir' => 'galeryDir'
	); }

	protected $propertiesList = array('position','itemIdLast','forumSet','thumbCut','order','hideSearchbox','hideSidebar','sidebar','homesite','home');
	protected $propDefaults = array('forumSet'=>1,'home'=>'');

	//---db based
	public $pageId;
	public $pageIdTop;
	public $typeId;
	public $typeIdChild;
	public $categoryId;
	public $categoryVO;
	public $template;
	public $name;
	public $description;
	public $content;
	public $public = 1;
	public $userIdOwner;
	public $ownerUserVO;
	public $pageIco;
	public $locked = 0;
	public $galeryDir;
	public $cnt;
	public $dateContent;
	public $dateCreated;
	public $dateUpdated;

	//---dedicted
	//---based on logged user
	public $favorite; //is booked
	public $favoriteCnt; //readed items

	function __get($name) {
		switch($name) {
			case 'unreaded':
				if($this->favoriteCnt < 1) $this->favoriteCnt = $this->cnt;
				$unreaded = $this->cnt - $this->favoriteCnt;
				if($unreaded > 0) return $unreaded; else return 0;
				break;
		}
	}

	//---changed
	public $showHeading=true;
	public $htmlName;
	public $htmlTitle;
	public $htmlDescription;
	public $htmlKeywords;
	public $showSidebar = true;
	public $showMidbar = true;
	public $showTopBanner = true;
	
	public $tplVars = array();
	
	function __construct($primaryId=0, $autoLoad = false) {
		parent::__construct($primaryId,$autoLoad);
		$this->propLoadAtOnce = true;
	}

	/**
	 * type specific perpage / galery has in xml
	 * @return number
	 */
	function perPage($perPage=0,$typeId=false) {
		if($typeId===false) $typeId = $this->get('typeId');
		if($typeId=='galery') return $this->get('cnt');
		$cache = FCache::getInstance('s');
		$SperPage = &$cache->getPointer($this->pageId,'pp');
		if($perPage > 0) {
			if($perPage < FConf::get('perpage','min')) $perPage = FConf::get('perpage','min');
			if($perPage > FConf::get('perpage','max')) $perPage = FConf::get('perpage','max');
			//set perpage
			$SperPage = (int) $perPage;
		}
		//get from cache if is custom
		if(!empty($SperPage)) $perPage = $SperPage;
		if(empty($perPage)) $perPage = FConf::get('perpage',$this->pageId);
		$typeIdChild = $this->get('typeIdChild');
		if(empty($perPage) && !empty($typeIdChild)) $perPage = FConf::get('perpage',$typeIdChild);
		if(empty($perPage) && !empty($typeId)) $perPage = FConf::get('perpage',$typeId);
		if(empty($perPage)) $perPage = FConf::get('perpage','default');
		return $perPage;
	}

	function itemsOrder() {
		$orderBy = $this->prop('order');
		$typeId = $this->get('typeId');
		//---legacy
		if($orderBy==1 && $typeId=='galery') {
			$orderBy = 'dateStart desc';
		}
		if(empty($orderBy)) {
			//---get default
			switch($typeId) {
				case 'galery':
					$orderBy = 'enclosure';
					break;
				default:
					$orderBy = 'dateStart desc, itemId desc';
			}
		}
		return $orderBy;
	}

	/**
	 *update readed
	 **/
	function updateReaded($userId) {
		if(empty($userId)) return;
		$this->cnt = FDBTool::getOne("select cnt from sys_pages where pageId='".$this->pageId."'");
		if($this->cnt==0) return;
		$q = "insert into sys_pages_favorites
			values ('".$userId."','".$this->pageId."','".$this->cnt."','0')
			on duplicate key update cnt='".$this->cnt."'";
		FDBTool::query($q);
		$this->unreaded = 0;
	}
	
	function inludeGaleries() {
		$typeId = $this->get('typeId');
		if($typeId != 'top' && $typeId!='blog') return false;
		if($this->getProperty('galeryincluded')==1) return true;
		return false;
	}

	/**
	 * refresh data for galery in db by files in folder
	 * @param $pageId
	 * @return void
	 */
	function refreshImages() {
		$galeryConf = FConf::get('galery');
		FError::write_log('PageVO::refreshImgToDb '.$this->pageId);

		$gCountFoto = 0;
		$gCountFotoNew = 0;

		$fItems = new FItems('galery',false);
		$fItems->setWhere('pageId="'.$this->pageId.'"');
		$fItems->addWhere('(itemIdTop is null or itemIdTop=0)');
		$itemList = $fItems->getList();
		$totalItems = count($itemList);

		$arrFotoDetail = array();
		$arrFotoSize = array();
		$arrNames = array();
		if(!empty($itemList)) {
			foreach ($itemList as $itemVO) {
				$arrFotoDetail[$itemVO->itemId] = $itemVO->enclosure;
				$arrFotoSize[$itemVO->enclosure] = $itemVO->filesize;
				$arrNames[$itemVO->enclosure] = $itemVO->itemId;
			}
		}

		//---search folder
		$gCountFoto = count($arrFotoDetail);
		$arrFiles = array();
		$galdir = FConf::get('galery','sourceServerBase') . $this->galeryDir;
		$ffile = new FFile(FConf::get("galery","ftpServer"));
		$arrFiles = $ffile->fileList($galdir,"png|jpg|jpeg|gif");
		$change = false;

		$arrNotInDB = array_diff($arrFiles,$arrFotoDetail);
		$arrItemIdsNotOnFtp = array_keys(array_diff($arrFotoDetail,$arrFiles));

		//---remove foto no longer in folder
		$removed=0;
		if(!empty($arrItemIdsNotOnFtp)) {
			foreach ($arrItemIdsNotOnFtp as $itemId) {
				$itemVO = new ItemVO($itemId,true);
				$itemVO->delete();
				$change = true;
				$removed++;
			}
		}

		$items = array();

		//---insert new foto to db
		if(!empty($arrNotInDB)) {
			foreach ($arrNotInDB as $file) {
				$this->itemVO = new ItemVO();
				$this->itemVO->pageId = $this->pageId;
				$this->itemVO->pageIdTop = $this->pageIdTop;
				$this->itemVO->typeId = $this->typeId;
				$this->itemVO->enclosure = $file;
				$this->itemVO->filesize = $ffile->filesize($galdir.($galdir{strlen($galdir)-1}!='/'?'/':'').$file);
				$this->itemVO->text = '';
				$this->itemVO->hit = 0;
				$this->itemVO->dateStart = $this->get('dateContent');
				$this->itemVO->save();
				$gCountFotoNew++;
				$items['new'][] = $this->itemVO->itemId;
				$change = true;
			}
		}

		//--- check if filesize changed so update thumb
		foreach ($arrFotoDetail as $k=>$v) {
			if(file_exists($galdir.($galdir{strlen($galdir)-1}!='/'?'/':'').$v)) {
				$newFilesize = $ffile->filesize($galdir.($galdir{strlen($galdir)-1}!='/'?'/':'').$v);
				$oldFilesize = $arrFotoSize[$v];
				if($newFilesize != $oldFilesize) {
					//---delete thumb, update filesize
					$fotoId = $arrNames[$v];
					$this->itemVO = new ItemVO($fotoId,true);
					$this->itemVO->filesize = $newFilesize;
					$this->itemVO->save();
					$this->itemVO->flush();
					FCommand::run(ITEM_UPDATED,$this->itemVO);
					$change = true;
					$items['updated'][] = $fotoId;
				}
			}
		}

		//---invalidate all cache places
		if($change == true) {
			FCommand::run(PAGE_UPDATED,$this);
		}

		//---update foto count on page
		$totalFoto = $gCountFotoNew + $gCountFoto;
		FDBTool::query("update sys_pages set cnt='".$totalFoto."',dateUpdated = now() where pageId='".$this->pageId."'");

		$items['total'] = $totalFoto;
		FError::write_log('PageVO::refreshImgToDb COMPLETE '.$this->pageId.' inserted:'.(isset($items['new']) ? count($items['new']) : 0).' updated:'.( isset($items['updated']) ? count($items['updated']) : 0).' removed: '.$removed);
		return $items;
	}

}