<?php
class PageVO extends FDBvo {
	var $tableDef = 'CREATE TABLE sys_pages (
     pageId varchar(5) NOT NULL PRIMARY KEY
     , pageIdTop varchar(5) DEFAULT NULL
     , typeId VARCHAR(10) DEFAULT null
     , typeIdChild VARCHAR(10) DEFAULT null
     , categoryId SMALLINT unsigned DEFAULT null
     , menuSecondaryGroup VARCHAR(10) DEFAULT null
     , template VARCHAR(50) DEFAULT null
     , name VARCHAR(100) NOT NULL
     , nameshort VARCHAR(20) NOT NULL
     , description TEXT
     , content TEXT
     , public tinyint unsigned NOT NULL DEFAULT 1
     , dateCreated DATETIME not null
     , dateUpdated DATETIME default null
     , dateContent DATETIME DEFAULT null
     , userIdOwner MEDIUMINT unsigned NOT NULL
     , pageIco VARCHAR(30)
     , cnt MEDIUMINT DEFAULT 0
     , locked TINYINT DEFAULT 0
     , authorContent VARCHAR(100)
     , galeryDir VARCHAR(100) DEFAULT null
     , pageParams text
)  ;';

	var $defaults = array(
    'forum'=>array('template'=>'page_ForumView','pageParams' => "<Page><home/></Page>"),
    'blog'=>array('categoryId'=>'318','template'=>'page_ForumView','pageParams' => "<Page><home/></Page>"),
    'galery'=>array('template'=>'page_GaleryDetail','pageParams' => "<Page><enhancedsettings><orderitems>0</orderitems><perpage>9</perpage><widthpx>170</widthpx><heightpx>170</heightpx><thumbnailstyle>2</thumbnailstyle><fotoforum>0</fotoforum></enhancedsettings></Page>"),
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
	var $pageParams = "<Page></Page>";
	var $cnt;
	var $dateContent;
	var $dateCreated;
	var $dateUpdated;

	//---dedicted
	//---based on logged user
	var $favorite;
	var $favoriteCnt;

	//---changed
	var $htmlTitle;
	var $htmlDescription;
	var $htmlKeywords;
	
	//---watcher
	var $xmlChanged = false;

	function PageVO($pageId=0, $autoLoad = false) {
		parent::__construct();
		$this->pageId = $pageId;
		if($autoLoad == true) {
			$this->load();
		}
	}
	
	function save() {
		if(!empty($this->pageId)) {
			$this->dateUpdated = 'now()';
			$this->notQuote('dateUpdated');
			$this->addIgnore('dateCreated');
			$this->forceInsert = false;
		} else {
			$this->pageId = FPages::newPageId();
			$this->forceInsert = true;
			$this->dateCreated = 'now()';
			$this->notQuote('dateCreated');
			$this->addIgnore('dateUpdated');
		}
		$pageId = parent::save();
		$this->xmlChanged = false;
		return $pageId;
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
	function perPage($typeId='') {
		$perPage = (String) $this->getPageParam('enhancedsettings/perpage');
		if(empty($perPage)) $perPage = FConf::get('perpage',((!empty($typeId))?($typeId):($this->typeId)));
		return $perPage;
	}
	
	function itemsOrder() {
		$orderBy = $this->getPageParam('enhancedsettings/orderitems');
		//---legacy
		if($orderBy==1 && $this->typeId=='galery') {
			$orderBy = 'dateCreated desc';
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

	function getPageParam($paramName) {
		if(!empty($this->pageParams)) {
			$xml = new SimpleXMLElement($this->pageParams);
			$result = $xml->xpath($paramName);
			if(isset($result[0])) {
				$ret = (String) $result[0];
				return 	$ret;
			}
		}
		return false;
	}

	function setXML($branch,$node,$value=false) {
		$xml = new SimpleXMLElement($this->pageParams);
		if($value === false) {
			if((String) $xml->$branch != $node) $this->xmlChanged = true;
			$xml->$branch = $node;
		} else {
			if((String) $xml->$branch->$node != $value) $this->xmlChanged = true;
			$xml->$branch->$node = $value;
		}
		$this->pageParams = $xml->asXML();
	}
}