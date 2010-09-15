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
		$cache = FCache::getInstance('s');
		$SperPage = &$cache->getPointer($this->pageId,'pp');
		if($perPage > 0) {
			if($perPage > FConf::get('perpage','min')) {
				//set perpage
				$SperPage = (int) $perPage;
			}
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
	
}