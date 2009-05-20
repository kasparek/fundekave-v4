<?php
class UserVO extends FDBvo {
  var $tableDef = 'CREATE TABLE sys_users (
       userId MEDIUMINT unsigned NOT NULL PRIMARY KEY
     , skinId SMALLINT unsigned DEFAULT null
     , name VARCHAR(20) NOT NULL
     , password VARCHAR(32) NOT NULL
     , ipcheck BOOLEAN DEFAULT 1
     , dateCreated DATETIME NOT NULL
     , dateUpdated DATETIME default null
     , dateLastVisit DATETIME default null
     , email VARCHAR(100) DEFAULT null
     , icq VARCHAR(20) DEFAULT null
     , info TEXT
     , avatar VARCHAR(100) DEFAULT null
     , zbanner TINYINT unsigned DEFAULT 1
     , zavatar TINYINT unsigned DEFAULT 1
     , zforumico TINYINT unsigned DEFAULT 1
     , zgalerytype TINYINT unsigned DEFAULT 0
     , deleted TINYINT unsigned DEFAULT 0
     , hit INT unsigned DEFAULT 0
)  ;';
  
  var $userId;
  var $skinId;
  var $name;
  var $password;
  var $ipcheck = true;
  var $dateCreated;
  var $dateUpdated;
	var $dateLastVisit;
  var $email = '';
	var $icq = '';
	//---additional user information XML structure
	var $info = "<user><personal><www/><motto/><place/><food/><hobby/><about/><HomePageId/></personal><webcam /></user>";
	var $avatar = AVATAR_DEFAULT;
	var $zbanner = 1;
	var $zavatar = 1;
	var $zforumico = 1;
	var $zgalerytype = 0;
	var $deleted;
	var $hit;
	
	//---security
  var $idlogin = '';
	var $idloginInDb = '';
  
	var $ip = '';
	//---skin info
	var $skin = 0;
	var $skinName = '';
	var $skinDir = '';
	
	  function PageVO() {
  	
    	parent::__construct();
      
    }
    
    function load() {
      $this->setSelect( implode(',',$this->_cols) );
		$this->setWhere($this->primaryCol ."='".$recordId."'");
		$this->addJoinAuto('sys_skin','skinId','name');
    }
}