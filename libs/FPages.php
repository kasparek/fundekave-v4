<?php
/**
 *
 * setup columns, follow FItems initlist
 * unify columns select
 * add f.cnt if registered   - newMess -> unreaded
 * check project total -> cnt
 * FCalendarPlugins - refactor, based on fitems  
 
		date_format(i.dateCreated ,'{#date_iso#}')
		date_format(i.dateCreated ,'{#date_local#}') "
		
		addjoin on propertie
		pplastitem.value as itemId
		
		date_format(dateContent,'{#date_local#}') as datumcz,
		date_format(dateContent,'{#date_iso#}') as diso
		
		
		
		   
 *
 */   
class FPages extends FDBTool {
	
	var $type = ''; //type of pages for listing - galery,forum,blog...
	var $userId = 0; //when it is 0 it work like not logged
	var $permission = 1; //---read access / 2 - edit/admin access
	var $sa = false; //---superadmin - selet true to list all pages
	
	var $pagesPermissionTableName = 'sys_users_perm';
	
	var $availableTypeArr = array('forum','blog','galery');

	function __construct($type,$userId,$permission=1) {

		$this->type = $type;
		$this->userId = (int) $userId * 1;
		$this->permission = $permission;

		parent::__construct('sys_pages','pageId');
		
		$this->VO = 'PageVO';
		$this->fetchmode = 1;
		$pageVO = new PageVO();
		
		$this->columns = $pageVO->getColumns();
		
		//---set select
		foreach($this->columns as $k=>$v) {
			if(strpos($v,' as ')===false) $v .= ' as '.$k;
			$columnsAsed[]=$v;
		}
		$this->setSelect( $columnsAsed );
		
		if(!empty($userId)) {
			$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			$fPages->addSelect('count(1) as favorite,f.cnt as favoriteCnt');
		}
		
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
				$queryBase = "select {SELECT} from ".$this->table." as p
				{JOIN} 
				where 1";
			} else if($this->userId == 0) {
				$queryBase = "select {SELECT} from ".$this->table." as p
				{JOIN} 
				where p.public=1 and p.locked<2";
			} else {
				$queryBase = "select {SELECT} from ".$this->table." as p
				left join ".$this->pagesPermissionTableName." as up on p.".$this->primaryCol."=up.".$this->primaryCol." and up.userId='".$this->userId."' 
				{JOIN} 
				where (((p.public in (0,3) and up.rules >= 1) 
				or p.userIdOwner='".$this->userId."' 
				or p.public in (1,2)) and (up.userId is null or up.rules!=0))";
			}
		} else {
			$queryBase = "select {SELECT} from ".$this->table." as p
				left join ".$this->pagesPermissionTableName." as up on p.".$this->primaryCol."=up.".$this->primaryCol." and up.userId='".$this->userId."' 
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
			$galery = new FGalery();
			$arrd = FDBTool::getCol("select itemId from sys_pages_items where pageId='".$pageId."' and itemIdTop is null");
			foreach ($arrd as $df) $galery->removeFoto($df);
			
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
	static function cntSet($pageId, $value = 1, $refresh=false) {
		$incStr = '';
		if($refresh===true) {
			$incStr = '(select count(1) from sys_pages_items where pageId="'.$pageId.'" and itemIdTop is null)';
		} else if($value > 0) {
			$incStr = 'cnt + '.$value;
		} else if($value < 0) {
			$incStr = 'cnt - '.abs($value);
		}
		if(!empty($incStr)) $incStr = ',cnt = '.$incStr;
		return FDBTool::query('update sys_pages set dateUpdated=now()'.$incStr.' where pageId="'.$pageId.'"');
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
		$tpl = FSystem::tpl('forums.all.tpl.html');
		$arrCategory = FDBTool::getAll("select categoryId,name from sys_pages_category where typeId='".$this->type."' order by ord,name");
		if(count($arrCategory)>0) {
			foreach ($arrCategory as $category) {
				//vypis jednotlivych klubu
				if(!empty($arrForums[$category[0]])) {
					//---add category name to title
					$user->pageVO->htmlName =  $category[1] . ' - ' . $user->pageVO->name;
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
	static function printPagelinkList($arrLinks=array(),$options=array()) {
		$user = FUser::getInstance();
		//---template init
		$tpl = FSystem::tpl('item.pagelink.tpl.html');
		//vypis jednotlivych klubu
		if(!empty($arrLinks)) {
			$tpl->touchBlock('showicons');
			foreach ($arrLinks as $page) {
				if($user->userVO->zforumico) {
						if(isset(FLang::$TYPEID[$page->typeId])) {
							if(!empty($page->pageIco)) {
								$tpl->setVariable("AVATARURL", URL_PAGE_AVATAR.$page->pageIco);
							} else if(!empty($page->typeId)) {
								$tpl->setVariable("AVATARURL", FConf::get('pageavatar',$page->typeId));
							}
							$tpl->setVariable("AVATARNAME", $page->name);
							$tpl->setVariable("AVATARALT", FLang::$TYPEID[$page->typeId]);
					  }
				}
				$tpl->setVariable("PAGENAME", $page->name);
				$tpl->setVariable("PAGEID", $page->pageId.'-'.FSystem::safetext($page->name));
				if($user->idkontrol===true) {
					if(isset($page->unreaded)) {
						if($page->unreaded>0 && $page->unreaded<100000) $tpl->setVariable("PAGEPOSTSNEW", $page->unreaded);
					}
				}

				//---show last item
				if(!empty($page->itemId)) {
					$item = new ItemVO($page->itemId,true,array('showPageLabel'=>true,'openPopup'=>false));
					$tpl->setVariable("ITEM", $item->render());
				}
				if(isset($options['inline'])) {
				$tpl->parse('itemlink');
				} else {
				$tpl->parse('item');
				}
			}
		}
		if(isset($options['inline'])) {
			$ret = $tpl->get('itemlink');
		} else {
			$ret = $tpl->get();
		}
		return $ret;
	}

	/**
	 * list of own, booked and new pages
	 *
	 * @param Boolean $xajax - if true return html without top div
	 * @return hmtl String
	 */
	function printBookedList($xajax=false) {
		$this->fetchmode = 1;
		
		$user = FUser::getInstance();
		$bookOrder = $user->userVO->getXMLVal('settings','bookedorder') * 1;
			
		if(!in_array($this->type,$this->availableTypeArr)) $this->type = $this->availableTypeArr[0];
			
		//---template init
		$tpl = FSystem::tpl('forums.booked.tpl.html');

		//---srovnani klubu
		FForum::clearUnreadedMess();
		FItems::afavAll($user->userVO->userId,$this->type);

		$userId=$user->userVO->userId;

		$this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess,p.typeId');
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
				$newSum += $forum['newMess'];
			}
			if($newSum>0) {
				$tpl->setVariable('OWNERNEW',$newSum);
			}
		}
    
		//vypis oblibenych
		$this->queryReset();
		$this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess,p.typeId');
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
				$newSum += $forum['newMess'];
			}
			if($newSum>0) {
				$tpl->setVariable('BOOKEDNEW',$newSum);
			}
		}

		//vypis novych
		
			$this->queryReset();
			$this->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess,p.typeId');
			$this->addJoin('left join sys_pages_favorites as f on f.userId="'.$userId.'"');
			$this->setWhere('f.pageId=p.pageId and f.book="0" and f.userId!=p.userIdOwner and p.userIdOwner!="'.$userId.'" and p.locked < 2');
			$this->setOrder('p.dateCreated desc');
			$this->setGroup('p.pageId');
			$this->setLimit(0,6);
			$arraudit = $this->getContent();

			if(count($arraudit)>0) {

				$tpl->setVariable('PAGELINKSNEW',$this->printPagelinkList($arraudit));

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
			file_put_contents(ROOT_PAGE_AVATAR.$filename,$file);
			$resizeParams = array('quality'=>80,'crop'=>1,'width'=>PAGE_AVATAR_WIDTH_PX,'height'=>PAGE_AVATAR_HEIGHT_PX);
			$iProc = new FImgProcess(ROOT_PAGE_AVATAR.$filename,ROOT_PAGE_AVATAR.$filename,$resizeParams);
		}
		return $filename;
	}

	//---load from upload
	function avatarUpload($pageId, $filesData) {
		$filesData['name'] = "pageAvatar-".$pageId.'.jpg';
		if($up = FSystem::upload($filesData, ROOT_PAGE_AVATAR, 40000)) {
			//---resize and crop if needed
			list($width,$height,$type) = getimagesize(ROOT_PAGE_AVATAR.$up['name']);
			if($width != PAGE_AVATAR_WIDTH_PX || $height!= PAGE_AVATAR_HEIGHT_PX) {
				//---RESIZE
				$resizeParams = array('quality'=>80,'crop'=>1,'width'=>PAGE_AVATAR_WIDTH_PX,'height'=>PAGE_AVATAR_HEIGHT_PX);
				$iProc = new FImgProcess(ROOT_PAGE_AVATAR.$filesData['name'],ROOT_PAGE_AVATAR.$up['name'],$resizeParams);
			}
			return $up['name'];
		}
		return '';
	}

	//---delete
	function avatarDelete( $pageId ) {
		$filename = ROOT_PAGE_AVATAR.'pageAvatar-'.$pageId.'.jpg';
		if(file_exists($filename)) {
			unlink($filename);
			return '';
		}
	}

}