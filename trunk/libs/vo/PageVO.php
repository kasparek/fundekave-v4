<?php
/**
 *
 * TODO:
 * migrate xml pageparams to properties
 * build properties list
 * put ItemVO prop handling to Fvob
 * check columns in project 
 * project - newMess -> this->unreaded 
 * //TODO: migrate pageParams home from forum, blog
	//TODO: migrate pageParams orderitems from galery    
 *
 */   
class PageVO extends Fvob {
	
	var $table = 'sys_pages';
	var $primaryCol = 'pageId';

	var $columns = array('pageId' => 'pageId',
	'pageIdTop' => 'pageIdTop',
	'typeId' => 'typeId',
	'typeIdChild' => 'typeIdChild',
	'categoryId' => 'categoryId',
	'menuSecondaryGroup' => 'menuSecondaryGroup',
	'template' => 'template',
	'name' => 'name',
	'nameshort' => 'nameshort',
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
	'authorContent' => 'authorContent',
	'galeryDir' => 'galeryDir'
	);
	
	var $propertiesList = array('position','itemIdLast','forumSet','thumbCut','order');
	public $propDefaults = array('forumSet'=>1,'home'=>'');
	
	var $defaults = array(
    'forum'=>array('template'=>'forum.view.php'),
    'blog'=>array('categoryId'=>'318','template'=>'forum.view.php'),
    'galery'=>array('template'=>'galery.detail.php'),
    'culture'=>array('template'=>'culture.view.tpl.html'));

	//---db based
	var $pageId;
	var $pageIdTop;
	var $typeId;
	var $typeIdChild;
	var $categoryId;
	var $categoryVO;
	var $menuSecondaryGroup;
	var $template;
	var $name;
	var $nameshort;
	var $description;
	var $content;
	var $public = 1;
	var $userIdOwner;
	var $ownerUserVO;
	var $pageIco;
	var $locked = 0;
	var $authorContent;
	var $galeryDir;
	var $cnt;
	var $dateContent;
	var $dateCreated;
	var $dateUpdated;

	//---dedicted
	//---based on logged user
	var $favorite; //is booked
	var $favoriteCnt; //readed items
		
	function __get($name) {
		switch($name) {
			case 'unreaded':
				$unreaded = $this->cnt - $this->favoriteCnt;
				if($unreaded > 0) return $unreaded; else return 0;
			break;
		}
	}          

	//---changed
	var $showHeading=true;
	var $htmlName;
	var $htmlTitle;
	var $htmlDescription;
	var $htmlKeywords;
	var $showSidebar = true;

	//---watcher
	var $saveOnlyChanged = false;
	var $changed = false;
	var $loaded = false;
	var $xmlChanged = false;
	
	static function get( $pageId, $autoLoad = false ) {
		$user = FUser::getInstance();
		if($user->pageVO) {
			if($user->pageVO->pageId == $pageId) {
				
				$pageVO = $user->pageVO;
				 
			}
		}
		$pageVO = new PageVO($pageId, $autoLoad);
		return $pageVO;
	}

	function PageVO($pageId=0, $autoLoad = false) {
		$this->pageId = $pageId;
		if($autoLoad == true) {
			$this->load();
		}
	}

	function load() {
		if(!empty($this->pageId)) {
			$vo = new FDBvo( $this );
			return $this->loaded = $vo->load();
		}
	}

	function save() {
	 $vo = new FDBvo( $this );
		if(!empty($this->pageId)) {
			$this->dateUpdated = 'now()';
			$vo->notQuote('dateUpdated');
			$vo->addIgnore('dateCreated');
			$vo->forceInsert = false;
		} else {
			$this->pageId = FPages::newPageId();
			$vo->forceInsert = true;
			$this->dateCreated = 'now()';
			$vo->notQuote('dateCreated');
			$vo->addIgnore('dateUpdated');
		}
		$this->pageId = $vo->save();
		$this->xmlChanged = false;
		$vo->vo = false;
		$vo = false;
		return $this->pageId;
	}

	function setDefaults() {
		if(isset($this->defaults[$this->typeId])) {
			foreach($this->defaults[$this->typeId] as $k=>$v) {
				$this->{$k} = $v;
			}
		}
	}

	/**
	 * type specific perpage / galery has in xml
	 * @return number
	 */
	function perPage($perPage=0) {
		if($this->typeId=='galery') return $this->cnt;
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
		if(empty($perPage) && !empty($this->typeIdChild)) $perPage = FConf::get('perpage',$this->typeIdChild);
		if(empty($perPage) && !empty($this->typeId)) $perPage = FConf::get('perpage',$this->typeId);
		if(empty($perPage)) $perPage = FConf::get('perpage','default');
		return $perPage;
	}

	function itemsOrder() {
		$orderBy = $this->prop('order');
		//---legacy
		if($orderBy==1 && $this->typeId=='galery') {
			$orderBy = 'dateStart desc';
		}
		if(empty($orderBy)) {
			//---get default
			switch($this->typeId) {
				case 'galery':
					$orderBy = 'enclosure';
					break;
				default:
					$orderBy = 'dateCreated desc';
			}
		}
		return $orderBy;
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
		$galdir = $this->conf['sourceServerBase'] . $this->pageVO->galeryDir.'/';
		$ffile = new FFile(FConf::get("galery","ftpServer"),FConf::get("galery","ftpUser"),FConf::get("galery","ftpPass"));
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
				$this->itemVO->typeId = $this->typeId;
				$this->itemVO->enclosure = $file;
				/*
				 $itemVO->dateCreated = 'now()';
				 //---try exif
				 $exif = @exif_read_data( $galdir.$file );
				 if(!empty($exif)) {
					$itemVO->dateCreated = date("Y-m-d",$exif['FileDateTime']);
					if(!empty($exif['DateTimeOriginal'])) {
					//TODO: find a way to fix all exif formats
					//$itemVO->dateCreated = date("Y-m-d",$exif['DateTimeOriginal']);
					}
					}
					*/
				$this->itemVO->filesize = filesize($galdir.$file);
				$this->itemVO->text = '';
				$this->itemVO->hit = 0;
				$this->itemVO->dateStart = $this->pageVO->dateContent;
				$this->itemVO->save();
				$gCountFotoNew++;
				$items['new'][] = $this->itemVO->itemId;
				$change = true;
			}
		}

		//--- check if filesize changed so update thumb
		foreach ($arrFotoDetail as $k=>$v) {
			if(file_exists($galdir.$v)) {
				$newFilesize = filesize($galdir.$v);
				$oldFilesize = $arrFotoSize[$v];
				if($newFilesize != $oldFilesize) {
					//---delete thumb, update filesize
					$fotoId = $arrNames[$v];
					$this->itemVO = new ItemVO($fotoId,true,array('type'=>'ignore'));
					$this->itemVO->filesize = $newFilesize;
					$this->itemVO->save();
					$this->itemVO->flush();
					$change = true;
					$items['updated'][] = $fotoId;
				}
			}
		}

		//---invalidate all cache places
		if($change == true) {
			//TODO:send notification to observer
			//FCommand::run('itemChanged');
			//$cache = FCache::getInstance('f');
			//$cache->invalidateGroup('calendarlefthand');
		}

		//---update foto count on page
		$totalFoto = $gCountFotoNew + $gCountFoto;
		FDBTool::query("update sys_pages set cnt='".$totalFoto."',dateUpdated = now() where pageId='".$this->pageId."'");

		$items['total'] = $totalFoto;
		FError::write_log('PageVO::refreshImgToDb COMPLETE '.$this->pageId.' inserted:'.(isset($items['new']) ? count($items['new']) : 0).' updated:'.( isset($items['updated']) ? count($items['updated']) : 0).' removed: '.$removed);
		return $items;
	}
	
}