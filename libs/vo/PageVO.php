<?php
class PageVO extends FVO {

	var $cacheResults = 's';
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
	'galeryDir' => 'galeryDir',
	'pageParams' => 'pageParams'
	);

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
	var $saveOnlyChanged = false;
	var $changed = false;
	var $xmlChanged = false;


	function PageVO($pageId=0, $autoLoad = false) {
	 $this->pageId = $pageId;
		if($autoLoad == true) {
			$this->load();
		}
	}

	function load() {
		$vo = new FDBvo( $this );
		$vo->load();
		$vo->vo = false;
		$vo = false;
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
		if($perPage>0) {
			if($perPage > FConf::get('perpage','min')) {
				//set perpage
				$cache = FCache::getInstance('s');
				$perPage = $cache->setData((int) $perPage,$this->pageId,'pp');
			}
		}
		//get from cache if is custom
		$cache = FCache::getInstance('s');
		$perPage = $cache->getData($this->pageId,'pp');
		if(empty($perPage))$perPage = (String) $this->getPageParam('enhancedsettings/perpage');
		if(empty($perPage)) $perPage = FConf::get('perpage',$this->pageId);
		if(empty($perPage)) $perPage = FConf::get('perpage',((!empty($typeId))?($typeId):($this->typeId)));
		if(empty($perPage)) $perPage = FConf::get('perpage','default');
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