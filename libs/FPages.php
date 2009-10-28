<?php
class FPages extends FDBTool {
	var $type = ''; //type of pages for listing - galery,forum,blog...
	var $userId = 0; //when it is 0 it work like not logged
	var $permission = 1; //---read access / 2 - edit/admin access
	var $sa = false; //---superadmin - selet true to list all pages
	var $pagesTableName = 'sys_pages';
	var $pagesPermissionTableName = 'sys_users_perm';
	var $pagesPrimaryCol = 'pageId';
	var $availableTypeArr = array('forum','blog','galery');

	function __construct($type,$userId,$permission=1) {

		$this->type = $type;
		$this->userId = $userId * 1;
		$this->permission = $permission;

		parent::__construct();
		$this->primaryCol = $this->pagesPrimaryCol;

		$this->getListPages();
	}
	
	static function setBooked($pageId,$userId,$book) {
		$this->query("update sys_pages_favorites set book='".$book."' where pageId='".$pageId."' AND userId='" . $userId."'");
	}
	
	static function newPageId($delka=5) {
		$moznosti='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$lo="";
		$lotmp="";
		while($lo==""){
			while(strlen($lotmp) < $delka) $lotmp .= $moznosti[mt_rand(0,strlen($moznosti)-1)];
			if(FDBTool::getOne("SELECT count(1) FROM sys_pages WHERE pageId LIKE '".$lotmp."'")==0) { $lo=$lotmp; break; }
			else $lotmp = '';
		}
		return($lo);
	}
	
	static function page_exist($col,$val) {
		return FDBTool::getOne("SELECT count(1) FROM sys_pages WHERE ".$col." = '".$val."'");
	}
	
	static function pageOwner($pageId) {
		$q = "SELECT userIdOwner FROM sys_pages WHERE pageId= '".$pageId."'";
		return FDBTool::getOne($q,$pageId,'pageOwn','s');
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

		$pageVO = new PageVO($pageId,true);
		//---galery - delete photos
		if($pageVO->typeId=='galery') {
			//---delete photo
			$dir = $pageVO->galeryDir;
			$galery = new FGalery();
			$arrd = FDBTool::getCol("select itemId from sys_pages_items where pageId='".$pageId."' and itemIdTop is null");
			foreach ($arrd as $df) $galery->removeFoto($df);
			if(!empty($dir)) {
				FFile::rm_recursive( ROOT . WEB_REL_GALERY . $dir );
				$cachePath = $galery->getThumbCachePath();
				FFile::rm_recursive($cachePath);
				$systemCachePath = $galery->getThumbCachePath( WEB_REL_CACHE_GALERY_SYSTEM );
				FFile::rm_recursive($systemCachePath);
			}
		}

		//TODO: ---event - delete flyer

		//TODO: ---delete avatar


		FDBTool::query("delete from sys_pages_relations where pageId='".$pageId."' or pageIdRelative='".$pageId."'");
		FDBTool::query("delete from sys_pages_favorites where pageId='".$pageId."'");
		FDBTool::query("delete from sys_pages_counter where pageId='".$pageId."'");
		FDBTool::query("delete from sys_users_perm where pageId='".$pageId."'");
		FDBTool::query("delete from sys_pages where pageId='".$pageId."'");
		 
		FDBTool::query("delete from sys_leftpanel_pages where pageId='".$pageId."'");
		FDBTool::query("delete from sys_leftpanel_users where pageId='".$pageId."'");
		FDBTool::query("delete from sys_users_pocket where pageId='".$pageId."'");
		FDBTool::query("delete from sys_pages_properties where pageId='".$pageId."'");
		FDBTool::query("delete from sys_pages_items where pageId='".$pageId."'");
		FDBTool::query("delete from sys_menu where pageId='".$pageId."'");
		FDBTool::query("delete from sys_menu_secondary where pageId='".$pageId."'");
		 
		$arrPoll = FDBTool::getCol("select pollId from sys_poll where pageId='".$pageId."'");
		while($arrPoll) {
			$pollId = array_shift($arrPoll);
			FDBTool::query("delete from sys_poll_answers_users where pollId='".$pollId."'");
			FDBTool::query("delete from sys_poll_answers where pollId='".$pollId."'");
			FDBTool::query("delete from sys_poll where pollId='".$pollId."'");
		}
	}

	/**
	 * update num items belongs to page
	 *
	 * @param string $pageId
	 * @param Boolean $increment - if false cnt is decremented
	 * @param Boolean $refresh - if true data are overriden with fresh query - slower 
	 * @return void
	 */
	static function cntSet($pageId, $increment=true, $refresh=false) {
		if($refresh==true) {
			return FDBTool::query('update sys_pages set dateUpdated=now(),cnt = (select count(1) from sys_pages_items where pageId="'.$pageId.'" and itemIdTop is null) where pageId="'.$pageId.'"');
		} else {
			return FDBTool::query('update sys_pages set dateUpdated=now(),cnt = cnt '.(($increment==true)?('+'):('-')).' 1 where pageId="'.$pageId.'"');
		}
	}
	
	/**
	 * sets FDBTool to load just pages by category
	 *
	 * @param int $categoryId
	 */
	function category($categoryId) {
		$userId = FUser::logon();
		if(empty($this->type)) $this->type = FDBTool::getOne('select typeId from sys_pages_category where categoryId="'.$categoryId.'"');
		$this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId)?(',(p.cnt-f.cnt) as newMess'):(',0')));
		$this->addWhere('p.locked < 2');
		if ($userId) {
			$this->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
		}
		$this->addWhere('p.categoryId='.$categoryId);
		$this->setOrder('p.name');
	}
	
	function getListByCategory($categoryId) {
		$user = FUser::getInstance();
		$this->type = $user->pageVO->typeIdChild;
		$this->category($categoryId);
		return $this->getContent();
	}
	/**
	 * print list of pages by category
	 *
	 * @param int $categoryId
	 * @param Boolean $xajax - if true no top div
	 * @return html string
	 */
	function printCategoryList($categoryId=0,$xajax=false) {
		$user = FUser::getInstance();
		$this->type = $user->pageVO->typeIdChild;
		if(!empty($user->pageParam) || $categoryId>0) {
			if($categoryId==0) $categoryId = $user->pageParam * 1;
			if($categoryId>0) {
				$this->category($categoryId);
				if($arr = $this->getContent()) {
					$arrForums[$categoryId] = $arr;
				}
			}
		}

		//---template init
		$tpl = new FTemplateIT('forums.all.tpl.html');
		$arrCategory = FDBTool::getAll("select categoryId,name from sys_pages_category where typeId='".$this->type."' order by ord,name");
		if(count($arrCategory)>0) {
			foreach ($arrCategory as $category) {
				//vypis jednotlivych klubu
				if(!empty($arrForums[$category[0]])) {
					//---add category name to title
					$user->pageVO->name =  $category[1] . ' - ' . $user->pageVO->name;
					$tpl->setVariable("CATEGORYPAGELINKLIST",FPages::printPagelinkList($arrForums[$category[0]]));
				}
				$tpl->setCurrentBlock('category');
				$tpl->setVariable('CATEGORYLINK',FSystem::getUri('','',$category[0]));
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

	/**
	 * leftpanel page links
	 *
	 * @param unknown_type $arrLinks
	 * @return HTML String
	 */
	static function printPagelinkList($arrLinks=array()) {
		$user = FUser::getInstance();
		//---template init
		$tpl = new FTemplateIT('item.pagelink.tpl.html');
		//vypis jednotlivych klubu
		if(!empty($arrLinks)) {
			$tpl->touchBlock('showicons');
			foreach ($arrLinks as $page) {
				$tpl->setCurrentBlock('item');
				if($user->userVO->zforumico) {
					if(!empty($page[3])) {
						$tpl->setVariable("AVATARURL", WEB_REL_PAGE_AVATAR.$page[3]);
						$tpl->setVariable("AVATARNAME", $page[2]);
						$tpl->setVariable("AVATARALT", $page[2]);
					}
				}
				$tpl->setVariable("PAGENAME", $page[2]);
				$tpl->setVariable("PAGEID", $page[0].'-'.FSystem::safetext($page[2]));
				if($user->idkontrol) {
					if($page[4]>0 && $page[4]<100000) $tpl->setVariable("PAGEPOSTSNEW", $page[4]);
					else $tpl->setVariable("PAGEPOSTSNEW", '&nbsp;');
				}
				
				//---show last item
				if(!empty($page[5])) {
					$item = new ItemVO($page[5],true,array('type'=>$page[6]));
					$tpl->setVariable("ITEM", $item->render());
				}
				$tpl->parseCurrentBlock();
			}
		}
		return $tpl->get();
	}
	
	/**
	 * list of own, booked and new pages
	 *
	 * @param Boolean $xajax - if true return html without top div
	 * @return hmtl String
	 */
	function printBookedList($xajax=false) {
		$user = FUser::getInstance();
		$bookOrder = $user->userVO->getXMLVal('settings','bookedorder') * 1;
		 
		if(!in_array($this->type,$this->availableTypeArr)) $this->type = $this->availableTypeArr[0];
		 
		//---template init
		$tpl = new FTemplateIT('forums.booked.tpl.html');

		//---srovnani klubu
		FForum::clearUnreadedMess();
		FItems::afavAll($user->userVO->userId,$this->type);

		//vypis vlastnich
		$friendsBook = false;
		if(!empty($user->whoIs)){
			if($user->userVO->isFriend($user->whoIs)) {
				$userId = $user->whoIs;
				$tpl->setVariable('AVATAR',FAvatar::showAvatar($userId,array('showName'=>1)));
				$friendsBook = true;
			}
		} else $userId=$user->userVO->userId;

		$this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess');
		$this->addJoin('left join sys_pages_favorites as f on f.userId=p.userIdOwner');
		$this->setWhere('p.userIdOwner="'.$userId.'" and p.pageId=f.pageId and p.locked<3');
		if($bookOrder==1) {
			$this->setOrder('p.name');
		} else {
			$this->setOrder('newMess desc,p.name');
		}

		$this->setGroup('p.pageId');
		$arraudit = $this->getContent();

		if(count($arraudit)>0){

			$tpl->setVariable('PAGELINKSOWN',$this->printPagelinkList($arraudit));

			$newSum=0;
			foreach($arraudit as $forum) {
				$newSum += $forum[4];
			}
			if($newSum>0) {
				$tpl->setVariable('OWNERNEW',$newSum);
			}
		}

		//vypis oblibenych
		$this->queryReset();
		$this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess');
		$this->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId="'.$userId.'"');
		$this->setWhere('f.book="1" and p.userIdOwner!="'.$userId.'" and p.locked<2');
		if($bookOrder==1) {
			$this->setOrder('p.name');
		} else {
			$this->setOrder('newMess desc,p.name');
		}

		$this->setGroup('p.pageId');
		$arraudit = $this->getContent();

		if(count($arraudit)>0){

			$tpl->setVariable('PAGELINKSBOOKED',$this->printPagelinkList($arraudit));

			$newSum=0;
			foreach($arraudit as $forum) {
				$newSum += $forum[4];
			}
			if($newSum>0) {
				$tpl->setVariable('BOOKEDNEW',$newSum);
			}
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
	
	/* AVATAR FOR PAGE */
	//---load from url
	static function avatarFromUrl($pageId, $avatarUrl) {
		$filename = 'pageAvatar-'.$pageId.'.jpg';
		if($file = @file_get_contents( $avatarUrl )) {
			file_put_contents(WEB_REL_PAGE_AVATAR.$filename,$file);
			$resizeParams = array('quality'=>80,'crop'=>1,'width'=>PAGE_AVATAR_WIDTH_PX,'height'=>PAGE_AVATAR_HEIGHT_PX);
			$iProc = new FImgProcess(WEB_REL_PAGE_AVATAR.$filename,WEB_REL_PAGE_AVATAR.$filename,$resizeParams);
		}
		return $filename;
	}

	//---load from upload
	function avatarUpload($pageId, $filesData) {
		$filesData['name'] = "pageAvatar-".$pageId.'.jpg';
		if($up = FSystem::upload($filesData, WEB_REL_PAGE_AVATAR, 40000)) {
			//---resize and crop if needed
			list($width,$height,$type) = getimagesize(WEB_REL_PAGE_AVATAR.$up['name']);
			if($width != PAGE_AVATAR_WIDTH_PX || $height!= PAGE_AVATAR_HEIGHT_PX) {
				//---RESIZE
				$resizeParams = array('quality'=>80,'crop'=>1,'width'=>PAGE_AVATAR_WIDTH_PX,'height'=>PAGE_AVATAR_HEIGHT_PX);
				$iProc = new FImgProcess(WEB_REL_PAGE_AVATAR.$filesData['name'],WEB_REL_PAGE_AVATAR.$up['name'],$resizeParams);
			}
			return $up['name'];
		}
		return '';
	}

	//---delete
	function avatarDelete( $pageId ) {
		$filename = WEB_REL_PAGE_AVATAR.'pageAvatar-'.$pageId.'.jpg';
		if(file_exists($filename)) {
			unlink($filename);
			return '';
		}
	}
	
	/* PAGE PROPERTIES */
	static function getProperty($pageId,$propertyName,$default=null) {
		$arr = FDBTool::getAll("select value from sys_pages_properties where pageId='".$pageId."' and name='".$propertyName."'");
		if(empty($arr)) {
			///get default
			$value = $default;
		} else {
			$value = $arr[0][0];
		}
		return $value;
	}
	static function setProperty($pageId,$propertyName,$propertyValue) {
		return FDBTool::query("insert into sys_pages_properties (pageId,name,value) values ('".$pageId."','".$propertyName."','".$propertyValue."') on duplicate key update value='".$propertyValue."'");
	}
}