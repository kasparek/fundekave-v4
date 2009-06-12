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
	var $public;
	var $userIdOwner;
	var $ownerUserVO;
	var $pageIco;
	var $locked;
	var $authorContent;
	var $galeryDir;
	var $pageParams;
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
	
	function PageVO($pageId=0, $autoLoad = false) {
		parent::__construct();
		$this->pageId = $pageId;
		if($autoLoad == true) {
			$this->load();
		}
	}
	
	/**
	 * type specific perpage / galery has in xml
	 * @return number
	 */
	function perPage() {
		$perPage = (String) $this->getPageParam('enhancedsettings/perpage');
		if(empty($perPage)) $perPage = FConf::get('perpage',$this->typeId);
		return $perPage;
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
}