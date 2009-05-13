<?php
require('../fQueryTool.class.php');
class PageVO extends fQueryTool {
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


  function PageVO() {
    include('../../pear/SQL/Parser.php');
    $parser = new SQL_Parser($this->tableDef,'MySQL');

    var_dump( $parser->parse() );  
  }
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
  //---changed
  var $htmlTitle;
  var $htmlDescription;
  var $htmlKeywords;
  
}

$a = new PageVO();