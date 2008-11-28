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
        $this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($user->idkontrol)?(',(p.cnt-f.cnt) as newMess'):('')));
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
        		  foreach ($arrForums[$category[0]] as $forum) {
            		$tpl->setCurrentBlock('all');
            		if($user->zaudico) {
            		  $tpl->touchBlock('showicons');
            		   if(!empty($forum[3])) {
                		 $tpl->setVariable("ALLICOKAM", $forum[0]);
                		 $tpl->setVariable("ALLFORUMICO", WEB_REL_PAGE_AVATAR.$forum[3]);
                		 $tpl->setVariable("ALLFORUMICOALT", substr($forum[2],0,8));
                		 $tpl->setVariable("ALLFORUMICONAME", $forum[2]);
            		   }
            		}
            		$tpl->setVariable("ALLFORUMNAME", $forum[2]);
            		$tpl->setVariable("ALLKAM", $forum[0]);
            		if($user->idkontrol) if($forum[4]>0) $tpl->setVariable("ALLNEWCNT", $forum[4]);
            		$tpl->parseCurrentBlock();
            	}
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
	function checkType() {
	    if(!in_array($this->type,$this->availableTypeArr)) $this->type = $this->availableTypeArr[0];
	}
	function printBookedList($xajax=false) {
	    global $user;
	    
	    $this->checkType();
	    
	    //---template init
        $tpl = new fTemplateIT('forums.booked.tpl.html');
        /*FIXME: moce delete somewhere else
        if(isset($_GET["del"])){
        	$delAuditId = $_GET["del"];
        	$tpl->setVariable('DELFORUMNAME',$this->db->getOne("SELECT name FROM sys_pages WHERE pageId='".$delAuditId."'"));
        	$tpl->setVariable('DELFORMACTION',$user->getUri());
        	$tpl->setVariable('DELFORUMID',$delAuditId);
        }
        if(isset($_POST["delnow"]) && fRules::get($user->gid,($_POST["del"]),2)){
        	$delAuditId = $_POST["del"];
        	$this->db->query("update sys_pages set locked='3' where pageId='".$delAuditId."'");
        	fError::addError("Klub odstranen");
        	fHTTP::redirect($user->getUri());
        }
        */
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
        
        $this->setSelect('p.pageId,p.name,p.pageIco,p.cnt,(p.cnt-f.cnt) as newMess');
        $this->addJoin('left join sys_pages_favorites as f on f.userId=p.userIdOwner');
        $this->setWhere('p.userIdOwner="'.$userId.'" and p.pageId=f.pageId and p.locked<3');
        $this->setOrder('newMess desc,p.name');
        $this->setGroup('p.pageId');
        $arraudit = $this->getContent();
        
        if(count($arraudit)>0){
          if($user->zaudico) $tpl->touchBlock("showiconsowner");
        	foreach ($arraudit as $forum) {
        		$tpl->setCurrentBlock("owner");
        		if($user->zaudico) {
        		    if(!empty($forum[2])) {
          			 $tpl->setVariable("OWNERICOKAM", $forum[0]);
          			 $tpl->setVariable("OWNERFORUMICO", WEB_REL_PAGE_AVATAR.$forum[2]);
          			 $tpl->setVariable("OWNERFORUMICOALT", substr($forum[1],0,8));
          			 $tpl->setVariable("OWNERFORUMICONAME", $forum[1]);
        		    }
        		}
        		$tpl->setVariable("OWNERFORUMNAME", $forum[1]);
        		$tpl->setVariable("OWNERKAM", $forum[0]);
        		$tpl->setVariable("OWNERNEWCLASS", ($forum[4]>0)?('i_banner'):(''));
        		$tpl->setVariable("OWNERNEWCNT", $forum[4]);
        		$tpl->setVariable("OWNERCNT", $forum[3]);
        		//$tpl->setVariable("OWNERDELLINK", $user->getUri('nav=del&del='.$forum[0]));
        		$tpl->parseCurrentBlock();
        	}
        }
        
        //vypis oblibenych
        $this->queryReset();
        $this->setSelect('p.pageId,p.name,p.userIdOwner,p.pageIco,p.cnt,(p.cnt-f.cnt) as newMess');
        $this->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId="'.$userId.'"');
        $this->setWhere('f.book="1" and p.userIdOwner!="'.$userId.'" and p.locked<2');
        $this->setOrder('newMess desc,p.name');
        $this->setGroup('p.pageId');
        $arraudit = $this->getContent();
        
        if(count($arraudit)>0){ 
          if($user->zaudico) $tpl->touchBlock("showiconsbooked");
           foreach ($arraudit as $forum) {
               $tpl->setCurrentBlock("booked");
        		if($user->zaudico) {
        		    if(!empty($forum[3])) {
          			 $tpl->setVariable("BOOKEDICOKAM", $forum[0]);
          			 $tpl->setVariable("BOOKEDFORUMICO", WEB_REL_PAGE_AVATAR.$forum[3]);
          			 $tpl->setVariable("BOOKEDFORUMICOALT", substr($forum[1],0,8));
          			 $tpl->setVariable("BOOKEDFORUMICONAME", $forum[1]);
        		    }
        		}
        		$tpl->setVariable("BOOKEDFORUMNAME", $forum[1]);
        		$tpl->setVariable("BOOKEDKAM", $forum[0]);
        		$tpl->setVariable("BOOKEDNEWCLASS", ($forum[5]>0)?('i_banner'):(''));
        		$tpl->setVariable("BOOKEDNEWCNT", $forum[5]);
        		$tpl->setVariable("BOOKEDCNT", $forum[4]);
        		$tpl->parseCurrentBlock();
          }
        }
        
        //vypis novych
        if($friendsBook==false) {
            $this->queryReset();
            $this->setSelect('p.pageId,p.name,p.userIdOwner,p.pageIco,p.cnt,(p.cnt-f.cnt)');
            $this->addJoin('left join sys_pages_favorites as f on f.userId="'.$userId.'"');
            $this->setWhere('f.pageId=p.pageId and f.book="0" and f.userId!=p.userIdOwner and p.userIdOwner!="'.$userId.'" and p.locked < 2');
            $this->setOrder('p.dateCreated desc');
            $this->setGroup('p.pageId');
            $this->setLimit(0,6);
            $arraudit = $this->getContent();
            
            if(count($arraudit)>0) {
              if($user->zaudico) $tpl->touchBlock("showiconsnew");
               foreach ($arraudit as $forum) {
            	  $tpl->setCurrentBlock("new");
            		if($user->zaudico) {
            		    if(!empty($forum[3])) {
              			 $tpl->setVariable("NEWICOKAM", $forum[0]);
              			 $tpl->setVariable("NEWFORUMICO", WEB_REL_PAGE_AVATAR.$forum[3]);
              			 $tpl->setVariable("NEWFORUMICOALT", substr($forum[1],0,8));
              			 $tpl->setVariable("NEWFORUMICONAME", $forum[1]);
            		    }
            		}
            		$tpl->setVariable("NEWFORUMNAME", $forum[1]);
            		$tpl->setVariable("NEWKAM", $forum[0]);
            		$tpl->setVariable("NEWNEWCLASS", ($forum[5]>0)?('i_banner'):(''));
            		$tpl->setVariable("NEWNEWCNT", $forum[5]);
            		$tpl->setVariable("NEWCNT", $forum[4]);
            		$tpl->parseCurrentBlock();
            }
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
}