<?php
class fPages extends fQueryTool {
	var $type = ''; //type of pages for listing - galery,forum,blog...
	var $userId = 0; //when it is 0 it work like not logged
	var $permission = 1; //---read access / 2 - edit/admin access
	var $sa = false; //---superadmin - selet true to list all pages
	var $pagesTableName = 'sys_pages';
	var $pagesFavoriteTableName = 'sys_pages_favorites';
	var $pagesPermissionTableName = 'sys_users_perm';
	var $pagesPrimaryCol = 'pageId';
	var $availableTypeArr = array('forum','blog','galery');
	
	function __construct($type,$userId,$db,$permission=1) {
		$this->db = &$db;
		$this->type = $type;
		$this->userId = $userId;
		$this->permission = $permission;
		
		parent::__construct();
		$this->primaryCol = $this->pagesPrimaryCol;
		
        $this->getListPages();
	}
	static function newPageId($delka=5) {
		Global $db;
		$moznosti='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$lo="";
		$lotmp="";
		while($lo==""){
			while(strlen($lotmp) < $delka) $lotmp .= $moznosti[mt_rand(0,strlen($moznosti)-1)];
			if($db->getOne("SELECT count(1) FROM sys_pages WHERE pageId LIKE '".$lotmp."'")==0) { $lo=$lotmp; break; }
			else $lotmp = '';
		}
		return($lo);
	}
	static function page_exist($col,$val) {
        global $db;
        return $db->getOne("SELECT count(1) FROM sys_pages WHERE ".$col." = '".$val."'");
    }
    static function pageOwner($pageId) {
        global $db;
        return $db->getOne("SELECT userIdOwner FROM sys_pages WHERE pageId= '".$pageId."'");
    }
    static function pageAttribute($pageId,$attribute='name') {
        global $db;
        return $db->getOne("SELECT ".$attribute." FROM sys_pages WHERE pageId= '".$pageId."'");
    }
	function getListPages() {
		if($this->permission == 1) {
		    if($this->sa === true) {
		        $queryBase = "select {SELECT} from ".$this->pagesTableName." as p 
				{JOIN} 
				where 1";
		    } else if($this->userId == 0) {
				$queryBase = "select {SELECT} from ".$this->pagesTableName." as p 
				{JOIN} 
				where p.public=1";
			} else {
				$queryBase = "select {SELECT} from ".$this->pagesTableName." as p 
				left join ".$this->pagesPermissionTableName." as up on p.".$this->pagesPrimaryCol."=up.".$this->pagesPrimaryCol." and up.userId='".$this->userId."' 
				{JOIN} 
				where (((p.public in (0,3) and up.rules > 1) 
				or p.userIdOwner='".$this->userId."' 
				or p.public in (1,2)) and (up.userId is null or up.rules!=0))";
			}
		} else {
			$queryBase = "select {SELECT} from ".$this->pagesTableName." as p 
				left join ".$this->pagesPermissionTableName." as up on p.".$this->pagesPrimaryCol."=up.".$this->pagesPrimaryCol." and up.userId='".$this->userId."' 
				{JOIN} 
				where ( p.userIdOwner='".$this->userId."' 
				or (up.userId is not null and up.rules=".$this->permission.") )";
		}
		if(!empty($this->type)) {
			if(!is_array($this->type)) {
				$queryBase.=" and p.typeId='".$this->type."'";
			} else {
				$queryBase.=" and p.typeId in ('".implode("','",$this->type)."')";
			}
		}
		$queryBase .= '  and ({WHERE}) {GROUP} {ORDER} {LIMIT}';

		$this->setTemplate($queryBase);
	}
	
	static function deletePage($pageId) {
	    global $db;
	    $db->query("delete from sys_pages_relations where pageId='".$pageId."' or pageIdRelative='".$pageId."'");
	    $db->query("delete from sys_pages_favorites where pageId='".$pageId."'");
	    $db->query("delete from sys_pages_counter where pageId='".$pageId."'");
	    $db->query("delete from sys_users_perm where pageId='".$pageId."'");
	    $db->query("delete from sys_pages where pageId='".$pageId."'");
	    
	    $db->query("delete from sys_leftpanel_pages where pageId='".$pageId."'");
	    $db->query("delete from sys_leftpanel_users where pageId='".$pageId."'");
	    $db->query("delete from sys_users_pocket where pageId='".$pageId."'");
	    $db->query("delete from sys_pages_properties where pageId='".$pageId."'");
	    $db->query("delete from sys_pages_items where pageId='".$pageId."'");
	    $db->query("delete from sys_menu where pageId='".$pageId."'");
	    $db->query("delete from sys_menu_secondary where pageId='".$pageId."'");
	    $db->query("delete from sys_users_perm_cache where pageId='".$pageId."'");
	    
	    /*
	    TODO: clean polls
	    */
	}

	static function cntSet($pageId,$increment=true,$refresh=false) {
	    global $db;
	    if($refresh==true) {
	        return $db->query('update sys_pages set cnt = (select count(1) from sys_pages_items where pageId="'.$pageId.'" and itemIdTop is null) where pageId="'.$pageId.'"');
	    } else {
	        return $db->query('update sys_pages set cnt = cnt '.(($increment==true)?('+'):('-')).' 1 where pageId="'.$pageId.'"');
	    }
	}
	function category($categoryId) {
	    global $user;
        if(empty($this->type)) $this->type = $db->getOne('select typeId from sys_pages_category where categoryId="'.$categoryId.'"');
        $this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($user->idkontrol)?(',(p.cnt-f.cnt) as newMess'):(',0')));
        $this->addWhere('p.locked<2');
        if ($user->idkontrol) {
          $this->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$user->gid.'"');
        }
        $this->addWhere('p.categoryId='.$categoryId);
        $this->setOrder('p.name');
	}
	function printCategoryList($categoryId=0,$xajax=false) {
	    global $user;
	    
	    $this->type = $user->currentPage['typeIdChild'];
        if(!empty($user->currentPageParam) || $categoryId>0) {
          if($categoryId==0) $categoryId = $user->currentPageParam * 1;
          if($categoryId>0) {
            $this->category($categoryId);
            if($arr = $this->getContent()) {
              $arrForums[$categoryId] = $arr;
            }
          }
        }
        
        //---template init
        $tpl = new fTemplateIT('forums.all.tpl.html');
        $arrCategory = $this->db->getAll("select categoryId,name from sys_pages_category where typeId='".$this->type."' order by ord,name");
        if(count($arrCategory)>0) {
            foreach ($arrCategory as $category) {
        		//vypis jednotlivych klubu	
        		if(!empty($arrForums[$category[0]])) {
        		  
        		    $tpl->setVariable("CATEGORYPAGELINKLIST",fPages::printPagelinkList($arrForums[$category[0]]));
            	
        		}
            $tpl->setCurrentBlock('category');
            $tpl->setVariable('CATEGORYLINK','?k='.$user->currentPageId.$category[0]);
            $tpl->setVariable('CATEGORYID',$category[0]);
            $tpl->setVariable('CATEGORYNAME',$category[1]);
            $tpl->parseCurrentBlock();
            }
        }
        if($xajax==true) {
            return $tpl->get('category');
        }
        else return $tpl->get();
	}
	
	static function printPagelinkList($arrLinks=array()) {
	    global $user;
        //---template init
        $tpl = new fTemplateIT('item.pagelink.tpl.html');
		//vypis jednotlivych klubu	
		if(!empty($arrLinks)) {
		  $tpl->touchBlock('showicons');
		  foreach ($arrLinks as $forum) {
    		$tpl->setCurrentBlock('item');
    		if($user->zaudico) {
    		   if(!empty($forum[3])) {
        		 $tpl->setVariable("AVATARURL", WEB_REL_PAGE_AVATAR.$forum[3]);
        		 $tpl->setVariable("AVATARNAME", $forum[2]);
        		 $tpl->setVariable("AVATARALT", $forum[2]);
    		   }
    		}
    		$tpl->setVariable("PAGENAME", $forum[2]);
    		$tpl->setVariable("PAGEID", $forum[0]);
    		if($user->idkontrol) {
    		    if($forum[4]>0) $tpl->setVariable("PAGEPOSTSNEW", $forum[4]);
    		    else $tpl->setVariable("PAGEPOSTSNEW", '&nbsp;');
    		}
    		$tpl->parseCurrentBlock();
    	   }
		}
        return $tpl->get();
	}
	
	function checkType() {
	    if(!in_array($this->type,$this->availableTypeArr)) $this->type = $this->availableTypeArr[0];
	}
	function printBookedList($xajax=false) {
	    global $user;
	    
	    $this->checkType();
	    
	    //---template init
        $tpl = new fTemplateIT('forums.booked.tpl.html');
        
        //---srovnani klubu
        fForum::clearUnreadedMess();
        fForum::afavAll($user->gid,$this->type);
          
        //vypis vlastnich
        $friendsBook = false;
        if(!empty($user->whoIs)){
            if($user->pritel($user->whoIs)) {
            	$userId = $user->whoIs;
            	$tpl->setVariable('AVATAR',$user->showAvatar($userId,array('showName'=>1)));
            	$friendsBook = true;
            }
        } else $userId=$user->gid;
        
        $this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess');
        $this->addJoin('left join sys_pages_favorites as f on f.userId=p.userIdOwner');
        $this->setWhere('p.userIdOwner="'.$userId.'" and p.pageId=f.pageId and p.locked<3');
        $this->setOrder('newMess desc,p.name');
        $this->setGroup('p.pageId');
        $arraudit = $this->getContent();
        
        if(count($arraudit)>0){
            
            $tpl->setVariable('PAGELINKSOWN',$this->printPagelinkList($arraudit));
          
        }
        
        //vypis oblibenych
        $this->queryReset();
        $this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess');
        $this->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId="'.$userId.'"');
        $this->setWhere('f.book="1" and p.userIdOwner!="'.$userId.'" and p.locked<2');
        $this->setOrder('newMess desc,p.name');
        $this->setGroup('p.pageId');
        $arraudit = $this->getContent();
        
        if(count($arraudit)>0){ 
            
            $tpl->setVariable('PAGELINKSBOOKED',$this->printPagelinkList($arraudit));
            
        }
        
        //vypis novych
        if($friendsBook==false) {
            $this->queryReset();
            $this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess');
            $this->addJoin('left join sys_pages_favorites as f on f.userId="'.$userId.'"');
            $this->setWhere('f.pageId=p.pageId and f.book="0" and f.userId!=p.userIdOwner and p.userIdOwner!="'.$userId.'" and p.locked < 2');
            $this->setOrder('p.dateCreated desc');
            $this->setGroup('p.pageId');
            $this->setLimit(0,6);
            $arraudit = $this->getContent();
            
            if(count($arraudit)>0) {
                
                $tpl->setVariable('PAGELINKSNEW',$this->printPagelinkList($arraudit));
                
            }
        }
        
        if($xajax==true) {
            $tpl->parse('bookedcontent');
            return $tpl->get('bookedcontent');
        } else return $tpl->get();
	    
	}
	static function getCategory($categoryId) {
	  global $user,$db;
	  $arr = &$user->arrCachePerLoad['categories'];
	  if(!isset($arr[$categoryId])) {
	    $arr[$categoryId] = $row = $db->getRow("select categoryId,typeId,name,ord,public from sys_pages_category where categoryId='".$categoryId."'");
	  } else $row = $arr[$categoryId];
	  return $row;
	}
	
	//---properties
    static function getProperty($pageId,$propertyName,$default=null) {
            global $db;
         $arr = $db->getAll("select value from sys_pages_properties where pageId='".$pageId."' and name='".$propertyName."'");
         if(empty($arr)) {
             ///get default
             $value = $default;
         } else {
             $value = $arr[0][0];
         }
         return $value;
    }
    static function setProperty($pageId,$propertyName,$propertyValue) {
        global $db;
        return $db->query("insert into sys_pages_properties (pageId,name,value) values ('".$pageId."','".$propertyName."','".$propertyValue."') on duplicate key update value='".$propertyValue."'");
    }
}