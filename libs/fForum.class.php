<?php
class fForum {
	var $aMessCount;
	var $aMessNewCount;
	var $aId;
	var $aName;
	var $aInfo;
	var $aOwner;
	var $aLocked = 0;
	var $aOwnerUsername;
	var $aIdLastVisit;
	
	function __construct() {
		
	}
	static function setBooked($auditId,$userId,$book) {
		global $db;
		$db->query("update sys_pages_favorites set book='".$book."' where pageId='".($auditId)."' AND userId='" . $userId."'");
	}
	static function messDel($id,$auditId=0) {
		global $db,$user;
		if(!is_array($id)) $id= array($id);
		if(!empty($id)) {
			if(empty($auditId)) $auditId = $db->getOne("select pageId from sys_pages_items where itemId='".$id[0]."'");
			$fItems = new fItems();
			foreach ($id as $delaud) {
					if(fRules::get($user->gid,$user->currentPageId,2) 
					|| $user->gid == $db->getOne("SELECT userId FROM sys_pages_items WHERE itemId='".$delaud."'")) {
					$fItems->deleteItem($delaud);
        }
			}
			$db->query("update sys_pages set cnt=".$db->getOne("select count(1) from sys_pages_items where pageId='".$auditId."'")." where pageId='".$auditId."'");
			$user->statAudit($auditId);
		}
	}
	static function messWrite($arr) {
			global $db,$user;
			$fSave = new fSqlSaveTool('sys_pages_items','itemId');
			$arrNotQuoted = array('dateCreated');
			$arr['dateCreated'] = 'NOW()';
			if(empty($arr['itemIdTop'])) {
			 $arr['itemIdTop'] = 'null';
			 $arrNotQuoted[] = 'itemIdTop';
      }
      if(empty($arr['userId'])) {
			 $arr['userId'] = 'null';
			 $arrNotQuoted[] = 'userId';
      }
      if(empty($arr['enclosure'])) {
			 $arr['enclosure'] = 'null';
			 $arrNotQuoted[] = 'enclosure';
      }
      if(empty($arr['addon'])) {
			 $arr['addon'] = 'null';
			 $arrNotQuoted[] = 'addon';
      }
      //$fSave->debug = 1;
      $arr['typeId'] = 'forum';
      
			$ret = $fSave->save($arr,$arrNotQuoted);
			
			if(!empty($arr['itemIdTop'])) fForum::incrementReactionCount($arr['itemIdTop']);
			else $db->query("update sys_pages set cnt=cnt+1 where pageId='".$arr['pageId']."'");
			
			if($user->gid>0) $user->statAudit($arr['pageId'],false);
			
			return $ret;
	}
	static function updateReadedReactions($itemId,$userId) {
	    global $db;
	    return $db->query("insert into sys_pages_items_readed_reactions (itemId,userId,cnt,dateCreated) values ('".$itemId."','".$userId."',(select cnt from sys_pages_items where itemId='".$itemId."'),now()) on duplicate key update cnt=(select cnt from sys_pages_items where itemId='".$itemId."')");
	}
  
	static function setUnreadedMess($arrMessId){
		if(empty($_SESSION['aNotReadedMess'])) $_SESSION['aNotReadedMess']=array();
		if(is_array($arrMessId)) $_SESSION['aNotReadedMess']=array_merge($_SESSION['aNotReadedMess'],$arrMessId);
	}
	static function isUnreadedMess($messId,$unset=true){
		$ret=false;
		if(empty($_SESSION['aNotReadedMess'])) $_SESSION['aNotReadedMess'] = array();
		if(in_array($messId,$_SESSION['aNotReadedMess'])) {
			$ret=true;
			if($unset) {
				unset($_SESSION['aNotReadedMess'][array_search($messId, $_SESSION['aNotReadedMess'])]);
			}
		}
		return $ret;
	}
	static function clearUnreadedMess() {
		$_SESSION['aNotReadedMess'] = array();
	}
	static function getSetUnreadedForum($id,$itemId){
		Global $db,$user;
		if($itemId==0) $unreadedCnt = $user->currentPage['cnt'] - $user->favoriteCnt;
		else {
		    $dot = 'select i.cnt-r.cnt from sys_pages_items as i join sys_pages_items_readed_reactions as r on i.itemId=r.itemId and r.userId="'.$user->gid.'" and i.itemId="'.$itemId.'"';
		    
		    $unreadedCnt = $db->getOne($dot);
		}
		if($unreadedCnt > 0 && $user->idkontrol) {
			$arrIds = $db->getCol("select itemId from sys_pages_items 
			where pageId='".$id."'".(($itemId>0)?(" and itemIdTop='".$itemId."'"):(''))." order by itemId desc limit 0,".$unreadedCnt);
			if(!empty($arrIds)){
				if(empty($_SESSION['aNotReadedMess'])) $_SESSION['aNotReadedMess']=array();
				foreach ($arrIds as $messId) if(!in_array($messId,$_SESSION['aNotReadedMess']))$arrTmp[]=$messId;
				if(!empty($arrTmp)) fForum::setUnreadedMess($arrTmp);
			}
		}
		return $unreadedCnt;
	}
	//---aktualizace oblibenych
	/*.......aktualizace FAV KLUBU............*/
	static function aFavAll($usrId,$typeId='forum') {
		Global  $db;
		if(!empty($usrId)){
			$klo=$db->getCol("SELECT f.pageId FROM sys_pages_favorites as f join sys_pages as p on p.pageId=f.pageId WHERE p.typeId='".$typeId."' and f.userId = '".$usrId."'");
			$kls=$db->getCol("SELECT pageId FROM sys_pages where typeId = '".$typeId."'");
			if(!isset($klo[0])) $res=$kls;
			else $res = array_diff($kls,$klo);
			foreach($res as $r) $db->query('insert into sys_pages_favorites (userId,pageId,cnt) values ("'.$usrId.'","'.$r.'","0")');
		}
	}
	static function aFav($pageId,$userId,$cnt,$booked=0) {
		global $db;
		if(!empty($userId)){
		    $dot = "insert into sys_pages_favorites values ('".$userId."','".$pageId."','".$cnt."','".$booked."') on duplicate key update cnt='".$cnt."'";
		    $db->query($dot);
		}
	}
	static function incrementReactionCount($itemId) {
	    global $db;
	    $dot = "update sys_pages_items set cnt=cnt+1 where itemId='".$itemId."'";
	    return $db->query($dot);
	}
	function process($itemId = 0,$callbackFunction=false) {
	    global $user,$db;
        $redirect = false;
        
        if(isset($_POST["send"])) {
            $user->filterClean();
        	if (!empty($_POST["del"])) {
        		fForum::messDel($_POST['del'],$user->currentPageId);
        		$redirect = true;
        	}
        	if(!$user->idkontrol) {
        	    $captcha = fCaptcha::init();
        		if($captcha->validate_submit($_POST['captchaimage'],$_POST['pcaptcha'])) $cap = true; else $cap = false;
        	} else $cap = true;
        	
        	if($cap) {
        	    if($user->idkontrol) $jmeno = $user->gidname;
        	    elseif(isset($_POST["jmeno"])) $jmeno = trim($_POST["jmeno"]);
        	    
        	    if(isset($_POST["zprava"])) $zprava = trim($_POST["zprava"]);
                
                if(isset($_POST["objekt"]))
                {
                  $objekt = trim($_POST["objekt"]);
                  
                  if(preg_match("/^link:([0-9a-zA-Z]*)$/" , $objekt)) {
                      $itemIdBottom = str_replace('link:','',$objekt);
                      $objekt = '';
                      //check $itemIdBottom
                      $pageIdBottom = '';
                      if(strlen($itemIdBottom)==5) {
                        //check if it is page
                        if(fPages::page_exist('pageId',$itemIdBottom)) {
                          $pageIdBottom  = $itemIdBottom;
                          $itemIdBottom = '';
                        }
                      }
                      if($pageIdBottom=='') {
                        //check if it is item
                        if(!fItems::itemExists($itemIdBottom)) {
                          $itemIdBottom = '';
                        }
                      }
                      
                  }
                }
        		if((!empty($zprava) || !empty($objekt))) {
        			if(empty($jmeno)){
        				fError::addError("Nezadali jste jmeno");
        				$redirect = true;
        			}
        			if ($user->isUsernameRegistered($jmeno) && !$user->idkontrol){
        				fError::addError("Jmeno uz nekdo pouziva");
        				$redirect = true;
        			}
        			if(!fError::isError()) {
            			$jmeno = fSystem::textins($jmeno,array('plainText'=>1));
            			if($user->idkontrol)
            			 $zprava = fSystem::textins($zprava);
            			else 
            			 $zprava = fSystem::textins($zprava,array('formatOption'=>0));
            			$objekt = fSystem::textins($objekt,array('plainText'=>1));
            		
            		//---insert
            		    $arrSave = array('pageId'=>$user->currentPageId,'userId'=>$user->gid,'name'=>$jmeno,'text'=>$zprava,'enclosure'=>$objekt);
            		    if(!empty($itemIdBottom)) $arrSave['itemIdBottom'] = $itemIdBottom;
            		    if(!empty($pageIdBottom)) $arrSave['pageIdBottom'] = $pageIdBottom;
            		    if($itemId > 0) $arrSave['itemIdTop'] = $itemId;
            		    
                        fForum::messWrite($arrSave);
                        
                        $_SESSION["cache_audit"] = array();
                  
                      //---on success
                        if($user->idkontrol) fUserDraft::clear('forum'.$user->currentPageId);
                			$redirect = true;
                    }
                        
        		  }
        	} else {
        		fError::adderror(ERROR_CAPTCHA);
        	}
        	if(fError::isError()) {
              $_SESSION["cache_audit"] = array("zprava"=>$zprava,"objekt"=>$objekt,"name"=>$jmeno);
            }
        }
        //---filtrovani
        if(isset($_POST["filtr"])) {
        	$user->filterSet($user->currentPageId,'text',fSystem::textins($_POST["zprava"],0,0));
        }
        //---per page
        if (isset($_POST["perpage"]) && $_POST["perpage"] != $user->auditPerPage) { 
        	$user->auditPerPage = $_POST["perpage"]*1;
        }
        //---redirect
        if($redirect==true) {
            $user->cacheRemove('lastForumPost');
            if($callbackFunction) call_user_func($callbackFunction);
            fHTTP::redirect($user->getUri());
        }
	}
	
	/*
	 * forum Print
	 */
	function show($itemId = 0,$publicWrite=true,$itemIdInside=0) {
	    global $user,$db;
	    $zprava = '';
	    
	    if(!$user->idkontrol && $publicWrite==true) { $captcha = fCaptcha::init(); }
	    
	    if ($user->auditPerPage < 2) $user->auditPerPage = 10;
        
        if($user->idkontrol == true) {
            $unreadedCnt = fForum::getSetUnreadedForum($user->currentPageId,$itemId);
            if($unreadedCnt > 0) {
                $unreadedCnt += 5;
                if($unreadedCnt < 20) $user->auditPerPage = 20;
        	    elseif($unreadedCnt > 100) $user->auditPerPage=100;
        	    else $user->auditPerPage = $unreadedCnt;    
            }
        }
        
        $perpage = $user->auditPerPage;
        /* ........ vypis nazvu auditka .........*/
        //--FORM
        $tpl = new fTemplateIT('forum.view.tpl.html');
        
        $desc = $user->currentPage['content'];
        if(!empty($desc)) $tpl->setVariable('PAGEDESC',$desc);
        if($user->currentPage['locked'] == 0 && $publicWrite==true) {
            $tpl->setVariable('FORMACTION',$user->getUri());
        	$name = "";
        	if($user->idkontrol) $zprava = fUserDraft::get('forum'.$user->currentPageId);
        	if(!empty($_SESSION["cache_audit"])) {
        	   $zprava = $_SESSION["cache_audit"]['zprava'];
        	   $objekt = $_SESSION["cache_audit"]['objekt'];
        	   $name = $_SESSION["cache_audit"]['name'];
        		unset($_SESSION["cache_audit"]);
        	}
        	if ($user->idkontrol) {
        	   $tpl->setVariable('USERNAMELOGGED',$user->gidname);
        	} else {
          	$tpl->setVariable('USERNAMENOTLOGGED',$name);
        		$src = $captcha->get_b2evo_captcha();
        		$tpl->setVariable('CAPTCHASRC',$src);
        		
        	}
        	$tpl->setVariable('TEXTAREAID','forum'.$user->currentPageId);
        	$tpl->addTextareaToolbox('TEXTAREATOOLBOX','forum'.$user->currentPageId);
       	
        	$tpl->setVariable('TEXTAREACONTENT',(($filterTxt = $user->filterGet($user->currentPageId,'text'))?($filterTxt):($zprava)));

        	if ($user->idkontrol) {
            $tpl->touchBlock('userlogged');
        	    $tpl->touchBlock('userlogged2');
        	    $tpl->setVariable('PERPAGE',$perpage);
        	}
        } elseif($publicWrite==false) $tpl->setVariable('READONLY',MESSAGE_FORUM_REGISTEREDONLY);
        else $tpl->setVariable('READONLY',MESSAGE_FORUM_READONLY);
        //---END FORM
        $fItems = new fItems();  
        $fItems->initData('forum');
        $fItems->addWhere("i.pageId='".$user->currentPageId."'");
        
        if(!empty($itemId)) $fItems->addWhere("i.itemIdTop='".$itemId."'");
        
        if(!empty($filterTxt)) {
        	$fItems->addWhereSearch(array('i.name','i.text','i.enclosure','i.dateCreated'),$filterTxt,'or');
        }
        $fItems->setOrder("i.dateCreated DESC");
        
        fItems::setQueryTool(&$fItems);
        
        $manualCurrentPage = 0;
        if($user->currentItemId > 0 || $itemIdInside > 0) {
            if($user->currentItemId > 0) $itemIdInside = $user->currentItemId;
            //---find a page of this item to have link to it
            if($itemIdInside > 0) $manualCurrentPage = fForum::getItemPage($itemIdInside,$user->currentPageId,$perpage);
        }
        
        if(!empty($user->whoIs)) $arrPagerExtraVars = array('who'=>$who); else $arrPagerExtraVars = array();
        $pager = fSystem::initPager(0,$perpage,array('extraVars'=>$arrPagerExtraVars,'noAutoparse'=>1,'bannvars'=>array('i'),'manualCurrentPage'=>$manualCurrentPage));
        $from = ($pager->getCurrentPageID()-1) * $perpage;
        
        $fItems->setLimit($from,$perpage+1);
        $fItems->getData();
        
        $total = count($fItems->arrData);
        
        $maybeMore = false;
        if($total > $perpage) {
            $maybeMore = true;
            unset($fItems->arrData[(count($fItems->arrData)-1)]);
        }
        
        if($from > 0) {
            $total += $from;
        }
        
        if($total > 0) {
        	/*.........zacina vypis prispevku.........*/
        	$pager->totalItems = $total;
        	$pager->maybeMore = $maybeMore;
        	$pager->getPager();
        	if ($total > $perpage) {
        	 $tpl->setVariable('TOPPAGER',$pager->links);
        	 $tpl->setVariable('BOTTOMPAGER',$pager->links);
          }
          $mess = '';
        	while ($fItems->arrData) {
        	    $fItems->parse();
        	}
        	
        	$tpl->setVariable('MESSAGES',$fItems->show());
        	/*......aktualizace novych a prectenych......*/
        	if($itemId>0) fForum::updateReadedReactions($itemId,$user->gid);
        	else fForum::aFav($user->currentPageId,$user->gid,$user->currentPage['cnt']);
        } else $tpl->touchBlock('messno');

        return $tpl->get();
	}
	private function getItemPage($itemId,$pageId,$perpage) {
	    global $db;
	    $ret = 0;
	    $page = 0;
	    $k = 0;
	    while($ret==0) {
	        $k++;
	        $arr =$db->getCol("select itemId from sys_pages_items where pageId='".$pageId."' order by dateCreated desc limit ".$page.",".$perpage."");
	        if(in_array($itemId,$arr)) $ret = $k;
	        $page += $perpage; 
	    }
	    return $ret;
	}
}