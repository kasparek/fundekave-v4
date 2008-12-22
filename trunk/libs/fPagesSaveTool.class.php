<?php
class fPagesSaveTool extends fSqlSaveTool {
    var $pagesTable = 'sys_pages'; 
    var $pagesPrimaryCol = 'pageId';
    var $type;
    
    var $xmlProperties;
    
    var $defaults = array(
    'forum'=>array(
      'typeId'=>'forum',
      'categoryId'=>'null',
      'menuSecondaryGroup'=>'null',
      'template'=>'forum.view.php',
      'nameshort'=>'KLUB',
      'public'=>1,
      'locked'=>0,
      'pageParams' => "<forum><home/></forum>"
      ),
    'blog'=>array(
      'typeId'=>'blog',
      'categoryId'=>'318',
      'menuSecondaryGroup'=>'null',
      'template'=>'forum.view.php',
      'nameshort'=>'BLOG',
      'public'=>1,
      'locked'=>0,
      'pageParams' => "<blog><home/></blog>"
      ),
    'galery'=>array(
      'typeId'=>'galery',
      'categoryId'=>'null',
      'menuSecondaryGroup'=>'null',
      'template'=>'galery.detail.php',
      'nameshort'=>'GALERIE',
      'public'=>1,
      'locked'=>0,
      'pageParams' => "<galery><enhancedsettings><orderitems>0</orderitems><perpage>9</perpage><widthpx>170</widthpx><heightpx>170</heightpx><thumbnailstyle>2</thumbnailstyle><fotoforum>0</fotoforum></enhancedsettings></galery>"
    ),
    'culture'=>array(
      'typeId'=>'culture',
      'categoryId'=>'null',
      'menuSecondaryGroup'=>'null',
      'leftpanelGroup'=>2,
      'template'=>'culture.view.tpl.html',
      'nameshort'=>'KULTURA',
      'public'=>1,
      'locked'=>0,
      'pageParams' => "<culture/>"
      ),
    );
    function __construct($type='') {
        $this->type = $type;
        parent::__construct($this->pagesTable,$this->pagesPrimaryCol);
    }
    function getDef($var) {
      if(isset($this->defaults[$this->type][$var])) return $this->defaults[$this->type][$var];
    }
    function getType($pageId) {
       global $db;
       return $this->type = $db->getOne('select typeId from sys_pages where pageId="'.$pageId.'"');
    }
    function savePage($cols,$notQuoted=array()) {
        if(empty($this->type)) {
            fError::addError('fPageSaveTool: type not set');
            return false;
        }
        
        $forceInsert=false;
        if(!isset($cols[$this->primaryCol])) {
            //---insert
            $forceInsert=true;
            $cols[$this->primaryCol] = fPages::newPageId();
            if(!isset($cols['dateCreated'])) {
                $cols['dateCreated']='now()';
                if(!in_array('dateCreated',$notQuoted)) $notQuoted[]='dateCreated';
            }
            foreach ($this->defaults[$this->type] as $k=>$v) {
                if(!isset($cols[$k])) $cols[$k] = $v;
            }
        } else {
            //---update
            if(!isset($cols['dateUpdated'])) {
                $cols['dateUpdated']='now()';
                if(!in_array('dateUpdated',$notQuoted)) $notQuoted[]='dateUpdated';
            }
        }
        if(!in_array('categoryId',$notQuoted)) $notQuoted[]='categoryId';
        if(!in_array('menuSecondaryGroup',$notQuoted)) $notQuoted[]='menuSecondaryGroup';

        return $this->save($cols,$notQuoted,$forceInsert);
    }
    
    function setXmlPropertiesDefaults() {
      $this->xmlProperties = $this->defaults[$this->type]['pageParams'];
    }
    
    function getXMLVal($branch,$node=false,$default='') {
	    $xml = new SimpleXMLElement($this->xmlProperties);
	    if(isset($xml->$branch)) {
	       if($node===false) {
          return $xml->$branch;
         } else {
  	       if(isset($xml->$branch->$node)) {
  	           return $xml->$branch->$node;
  	       }
	       }
	    }
	    return $default;
	}
	function setXMLVal($branch,$node,$value=false) {
	    $xml = new SimpleXMLElement($this->xmlProperties);
	    if($value===false) {
	     $xml->$branch = $node;
	    } else {
        $xml->$branch->$node = $value;
	    }
      $this->xmlProperties = $xml->asXML();
	}
}