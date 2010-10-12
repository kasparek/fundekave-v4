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
	var $template;
	var $name;
	var $description;
	var $content;
	var $public = 1;
	var $userIdOwner;
	var $ownerUserVO;
	var $pageIco;
	var $locked = 0;
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
				if($this->favoriteCnt < 1) $this->favoriteCnt = $this->cnt;
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

	static function factory( $pageId, $autoLoad = false ) {
		$user = FUser::getInstance();
		if($user->pageVO) {
			if($user->pageVO->pageId == $pageId) {

				$pageVO = $user->pageVO;
					
			}
		}
		$pageVO = new PageVO($pageId, $autoLoad);
		return $pageVO;
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
		$typeId = $this->get('typeId');
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
					$orderBy = 'if(dateStart,dateStart,dateCreated) desc, itemId desc';
			}
		}
		return $orderBy;
	}

	/**
	 *update readed
	 **/
	function updateReaded($userId) {
		if(empty($userId)) return;
		if($this->cnt==0) return;
		$q = "insert delayed into sys_pages_favorites
			values ('".$userId."','".$this->pageId."',(select cnt from sys_pages where pageId='".$this->pageId."'),'0')
			on duplicate key update cnt=(select cnt from sys_pages where pageId='".$this->pageId."')";
		FDBTool::query($q);
		$this->unreaded = 0;
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
					$this->itemVO = new ItemVO($fotoId,true);
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