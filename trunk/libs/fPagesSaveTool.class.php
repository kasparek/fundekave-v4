<?php
class fPagesSaveTool extends fSqlSaveTool {
    var $pagesTable = 'sys_pages'; 
    var $pagesPrimaryCol = 'pageId';
    var $type;
    var $defaults = array(
    'forum'=>array(
      'typeId'=>'forum',
      'categoryId'=>'null',
      'menuSecondaryGroup'=>'null',
      'leftpanelGroup'=>9,
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
      'leftpanelGroup'=>9,
      'template'=>'forum.view.php',
      'nameshort'=>'BLOG',
      'public'=>1,
      'locked'=>0,
      'pageParams' => "<blog><home/></blog>"
      ),
    'galery'=>array(
      'typeId'=>'galery',
      'categoryId'=>'null',
      'menuSecondaryGroup'=>8,
      'leftpanelGroup'=>8,
      'template'=>'galery.detail.php',
      'nameshort'=>'GALERIE',
      'public'=>1,
      'locked'=>0,
      'pageParams' => "<galery><enhancedsettings><orderitems>0</orderitems><width/><height/><widthpx/><heightpx/><thumbnailstyle/><fotoforum>1</fotoforum></enhancedsettings></galery>"
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
}
?>